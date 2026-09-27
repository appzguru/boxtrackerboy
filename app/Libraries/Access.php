<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Wie doet dit request, in welke verhuizing, met welke rol (handoff.md §2, §6).
 *
 * Twee soorten bezoekers:
 * - een account (cookie `bt_session`) — lid van nul of meer verhuizingen, met één actieve;
 * - een gast via de handjes-QR (cookie `bt_guest`) — precies één verhuizing, vaste rol.
 *
 * Whitelabel (whitelabel-plan.md §3): alleen verhuizingen die bij de ingang horen
 * (Tenant::owns) tellen mee. Op een bedrijfssubdomein zijn planner en sales van dat bedrijf
 * admin in al zijn verhuizingen; inpakkers, sjouwers en bewoners alleen via memberships.
 * Een global-admin kan op de klant-app meekijken in een bedrijfsverhuizing (alleen-lezen,
 * afgedwongen door App\Filters\MeekijkFilter).
 *
 * Alles wat verhuizing-data raakt, vraagt hier de verhuizing en rol op. Eén instantie per
 * request (shared service), lui opgebouwd bij het eerste gebruik.
 */
class Access
{
    public const USER_COOKIE  = 'bt_session';
    public const GUEST_COOKIE = 'bt_guest';

    /** Rangorde: een rol mag alles wat een lagere rol mag. */
    public const RANK = ['sjouwer' => 1, 'helper' => 2, 'admin' => 3];

    /** Medewerkersrollen die admin zijn in alle verhuizingen van hun bedrijf. */
    public const BEDRIJF_ADMINS = ['planner', 'sales'];

    private bool $resolved      = false;
    private ?array $user        = null;
    private ?array $session     = null;
    private ?array $guest       = null;
    private ?array $verhuizing  = null;
    private ?string $rol        = null;
    private bool $meekijken     = false;

    /** false = nog niet opgezocht. */
    private array|false|null $medewerker = false;
    private ?bool $platformAdmin         = null;

    public function __construct(private ?IncomingRequest $request = null, private ?Tenant $tenant = null)
    {
    }

    private function tenant(): Tenant
    {
        return $this->tenant ??= service('tenant');
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        $request = $this->request ?? service('request');
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $token = (string) $request->getCookie(self::USER_COOKIE);
        if ($token !== '' && $this->resolveUser($token)) {
            return;
        }

        $token = (string) $request->getCookie(self::GUEST_COOKIE);
        if ($token !== '') {
            $this->resolveGuest($token);
        }
    }

    private function resolveUser(string $token): bool
    {
        $db  = db_connect();
        $row = $db->table('sessions')
            ->select('sessions.id AS session_id, sessions.active_verhuizing_id, sessions.meekijk_verhuizing_id, sessions.last_used_at, users.id, users.naam, users.email, users.email_verified_at')
            ->join('users', 'users.id = sessions.user_id')
            ->where('sessions.token', $token)
            ->get()->getRowArray();

        if (! $row) {
            return false;
        }

        $this->session = [
            'id'                   => (int) $row['session_id'],
            'active_verhuizing_id' => $row['active_verhuizing_id'] ? (int) $row['active_verhuizing_id'] : null,
        ];
        $this->user = [
            'id'                => (int) $row['id'],
            'naam'              => $row['naam'],
            'email'             => $row['email'],
            'email_verified_at' => $row['email_verified_at'],
        ];

        if (strtotime($row['last_used_at']) < time() - 3600) {
            $db->table('sessions')->where('id', $this->session['id'])->update(['last_used_at' => date('Y-m-d H:i:s')]);
        }

        if ($row['meekijk_verhuizing_id'] && $this->startMeekijken((int) $row['meekijk_verhuizing_id'])) {
            return true;
        }

        $active = $this->session['active_verhuizing_id'];
        if ($active && $this->loadMembership($active)) {
            return true;
        }

        // Geen (geldige) keuze: bij precies één lidmaatschap die meteen nemen.
        $memberships = $this->memberships();
        if (count($memberships) === 1) {
            $this->switchTo((int) $memberships[0]['id']);
        }

        return true;
    }

    /**
     * Meekijken: alleen een global-admin, alleen op de klant-app, alleen in een verhuizing van
     * een bedrijf (nooit bij particulieren). Anders wordt de markering genegeerd.
     */
    private function startMeekijken(int $verhuizingId): bool
    {
        if (! $this->tenant()->isKlant() || ! $this->isPlatformAdmin()) {
            return false;
        }

        $row = db_connect()->table('verhuizingen')
            ->select('id, naam')
            ->where('id', $verhuizingId)
            ->where('bedrijf_id IS NOT NULL')
            ->get()->getRowArray();

        if (! $row) {
            return false;
        }

        $this->verhuizing = ['id' => (int) $row['id'], 'naam' => $row['naam']];
        $this->rol        = 'admin';
        $this->meekijken  = true;

        return true;
    }

    /**
     * Rol van het account in deze verhuizing, of null als die niet bij deze ingang hoort of het
     * account er niets mag. Samen met memberships() de enige plek waar toegang wordt bepaald.
     */
    private function lookup(int $verhuizingId): ?array
    {
        $row = db_connect()->table('verhuizingen')
            ->select('verhuizingen.id, verhuizingen.naam, verhuizingen.bedrijf_id, memberships.rol')
            ->join('memberships', 'memberships.verhuizing_id = verhuizingen.id AND memberships.user_id = ' . (int) $this->user['id'], 'left')
            ->where('verhuizingen.id', $verhuizingId)
            ->get()->getRowArray();

        $bedrijfId = isset($row['bedrijf_id']) ? (int) $row['bedrijf_id'] : null;
        if (! $row || ! $this->tenant()->owns($bedrijfId) || $this->isInactieveMedewerker()) {
            return null;
        }

        $rol = $row['rol'];
        if ($bedrijfId !== null && $this->isBedrijfAdmin()) {
            $rol = 'admin';
        }

        return $rol ? ['id' => (int) $row['id'], 'naam' => $row['naam'], 'rol' => $rol] : null;
    }

    private function loadMembership(int $verhuizingId): bool
    {
        $row = $this->lookup($verhuizingId);
        if (! $row) {
            return false;
        }

        $this->verhuizing = ['id' => $row['id'], 'naam' => $row['naam']];
        $this->rol        = $row['rol'];

        return true;
    }

    private function resolveGuest(string $token): void
    {
        $db  = db_connect();
        $row = $db->table('guest_sessions')
            ->select('guest_sessions.*, verhuizingen.naam AS verhuizing_naam, verhuizingen.bedrijf_id')
            ->join('verhuizingen', 'verhuizingen.id = guest_sessions.verhuizing_id')
            ->where('guest_sessions.token', $token)
            ->where('guest_sessions.revoked_at IS NULL')
            ->where('guest_sessions.expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray();

        if (! $row || ! $this->tenant()->owns($row['bedrijf_id'] === null ? null : (int) $row['bedrijf_id'])) {
            return;
        }

        $this->guest = [
            'id'         => (int) $row['id'],
            'naam'       => $row['naam'],
            'expires_at' => $row['expires_at'],
        ];
        $this->verhuizing = ['id' => (int) $row['verhuizing_id'], 'naam' => $row['verhuizing_naam']];
        $this->rol        = $row['rol'];

        if (strtotime($row['last_used_at']) < time() - 3600) {
            $db->table('guest_sessions')->where('id', $row['id'])->update(['last_used_at' => date('Y-m-d H:i:s')]);
        }
    }

    /** Ingelogd met een account (niet: gast). */
    public function user(): ?array
    {
        $this->resolve();

        return $this->user;
    }

    public function guest(): ?array
    {
        $this->resolve();

        return $this->guest;
    }

    /** Account óf gast. */
    public function isAuthenticated(): bool
    {
        return $this->user() !== null || $this->guest() !== null;
    }

    /** Naam voor `door` / `ingepakt_door`. */
    public function naam(): string
    {
        return $this->user()['naam'] ?? $this->guest()['naam'] ?? '';
    }

    public function verhuizing(): ?array
    {
        $this->resolve();

        return $this->verhuizing;
    }

    public function verhuizingId(): ?int
    {
        return $this->verhuizing()['id'] ?? null;
    }

    public function rol(): ?string
    {
        $this->resolve();

        return $this->rol;
    }

    /** Heeft de bezoeker in de actieve verhuizing minstens deze rol? */
    public function can(string $minRol): bool
    {
        $rol = $this->rol();

        return $rol !== null && (self::RANK[$rol] ?? 0) >= (self::RANK[$minRol] ?? PHP_INT_MAX);
    }

    /** Global-admin die nu meekijkt in een bedrijfsverhuizing (alleen-lezen). */
    public function meekijken(): bool
    {
        $this->resolve();

        return $this->meekijken;
    }

    /**
     * Global-admin: meekijken starten (verhuizing-id) of stoppen (null) voor deze sessie.
     * Of de verhuizing mag (bedrijfsverhuizing, klant-app), controleert resolve bij elk request.
     */
    public function meekijkNaar(?int $verhuizingId): bool
    {
        if (! $this->isPlatformAdmin() || ! $this->tenant()->isKlant()) {
            return false;
        }

        db_connect()->table('sessions')->where('id', $this->session['id'])->update(['meekijk_verhuizing_id' => $verhuizingId]);

        return true;
    }

    /** Staat het account in platform_admins? */
    public function isPlatformAdmin(): bool
    {
        if ($this->platformAdmin === null) {
            $this->platformAdmin = $this->user() !== null && db_connect()->table('platform_admins')
                ->where('user_id', $this->user['id'])->countAllResults() > 0;
        }

        return $this->platformAdmin;
    }

    /**
     * Medewerkerschap van het account bij het bedrijf van deze ingang (['rol', 'actief']),
     * of null (klant-app, geen medewerker, of gast).
     */
    public function medewerker(): ?array
    {
        if ($this->medewerker === false) {
            $this->medewerker = null;
            $bedrijfId        = $this->tenant()->bedrijfId();
            if ($bedrijfId !== null && $this->user()) {
                $row = db_connect()->table('bedrijf_medewerkers')
                    ->select('rol, actief')
                    ->where('bedrijf_id', $bedrijfId)
                    ->where('user_id', $this->user['id'])
                    ->get()->getRowArray();
                $this->medewerker = $row ? ['rol' => $row['rol'], 'actief' => (bool) $row['actief']] : null;
            }
        }

        return $this->medewerker;
    }

    /** Planner of sales (actief) van het bedrijf van deze ingang. */
    public function isBedrijfAdmin(): bool
    {
        $m = $this->medewerker();

        return $m !== null && $m['actief'] && in_array($m['rol'], self::BEDRIJF_ADMINS, true);
    }

    /** Gedeactiveerde medewerker: bij dit bedrijf nergens meer toegang, ook niet via memberships. */
    private function isInactieveMedewerker(): bool
    {
        $m = $this->medewerker();

        return $m !== null && ! $m['actief'];
    }

    /** Verhuizingen bij deze ingang waar het account bij kan, met rol en aantal dozen. */
    public function memberships(): array
    {
        if (! $this->user() || $this->isInactieveMedewerker()) {
            return [];
        }

        $tenant  = $this->tenant();
        $builder = db_connect()->table('verhuizingen')
            ->select('verhuizingen.id, verhuizingen.naam, memberships.rol, (SELECT COUNT(*) FROM boxes WHERE boxes.verhuizing_id = verhuizingen.id AND boxes.status != \'leeg\') AS dozen')
            ->join('memberships', 'memberships.verhuizing_id = verhuizingen.id AND memberships.user_id = ' . (int) $this->user['id'], 'left')
            ->orderBy('verhuizingen.naam', 'ASC');

        if ($tenant->isKlant()) {
            $builder->where('verhuizingen.bedrijf_id IS NULL')->where('memberships.id IS NOT NULL');
        } elseif ($tenant->isBedrijf()) {
            $builder->where('verhuizingen.bedrijf_id', $tenant->bedrijfId());
            if (! $this->isBedrijfAdmin()) {
                $builder->where('memberships.id IS NOT NULL');
            }
        } else {
            return [];
        }

        $rows = $builder->get()->getResultArray();
        if ($tenant->isBedrijf() && $this->isBedrijfAdmin()) {
            foreach ($rows as &$r) {
                $r['rol'] = 'admin';
            }
            unset($r);
        }

        return $rows;
    }

    /** Maakt een verhuizing actief voor dit account. Faalt als die niet bij deze ingang hoort of het account er niets mag. */
    public function switchTo(int $verhuizingId): bool
    {
        if (! $this->user()) {
            return false;
        }
        if ($this->meekijken) {
            return $this->verhuizingId() === $verhuizingId;
        }
        if (! $this->loadMembership($verhuizingId)) {
            return false;
        }

        if ($this->session['active_verhuizing_id'] !== $verhuizingId) {
            db_connect()->table('sessions')->where('id', $this->session['id'])->update(['active_verhuizing_id' => $verhuizingId]);
            $this->session['active_verhuizing_id'] = $verhuizingId;
        }

        return true;
    }

    /** Kan deze bezoeker bij deze verhuizing (zonder te wisselen)? */
    public function hasAccessTo(int $verhuizingId): bool
    {
        if ($this->verhuizingId() === $verhuizingId) {
            return true;
        }
        if (! $this->user() || $this->meekijken) {
            return false;
        }

        return $this->lookup($verhuizingId) !== null;
    }
}
