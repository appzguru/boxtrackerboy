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
 * Alles wat verhuizing-data raakt, vraagt hier de verhuizing en rol op. Eén instantie per
 * request (shared service), lui opgebouwd bij het eerste gebruik.
 */
class Access
{
    public const USER_COOKIE  = 'bt_session';
    public const GUEST_COOKIE = 'bt_guest';

    /** Rangorde: een rol mag alles wat een lagere rol mag. */
    public const RANK = ['sjouwer' => 1, 'helper' => 2, 'admin' => 3];

    private bool $resolved      = false;
    private ?array $user        = null;
    private ?array $session     = null;
    private ?array $guest       = null;
    private ?array $verhuizing  = null;
    private ?string $rol        = null;

    public function __construct(private ?IncomingRequest $request = null)
    {
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
            ->select('sessions.id AS session_id, sessions.active_verhuizing_id, sessions.last_used_at, users.id, users.naam, users.email, users.email_verified_at')
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

    private function loadMembership(int $verhuizingId): bool
    {
        $row = db_connect()->table('memberships')
            ->select('memberships.rol, verhuizingen.id, verhuizingen.naam')
            ->join('verhuizingen', 'verhuizingen.id = memberships.verhuizing_id')
            ->where('memberships.user_id', $this->user['id'])
            ->where('memberships.verhuizing_id', $verhuizingId)
            ->get()->getRowArray();

        if (! $row) {
            return false;
        }

        $this->verhuizing = ['id' => (int) $row['id'], 'naam' => $row['naam']];
        $this->rol        = $row['rol'];

        return true;
    }

    private function resolveGuest(string $token): void
    {
        $db  = db_connect();
        $row = $db->table('guest_sessions')
            ->select('guest_sessions.*, verhuizingen.naam AS verhuizing_naam')
            ->join('verhuizingen', 'verhuizingen.id = guest_sessions.verhuizing_id')
            ->where('guest_sessions.token', $token)
            ->where('guest_sessions.revoked_at IS NULL')
            ->where('guest_sessions.expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray();

        if (! $row) {
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

    /** Verhuizingen waar het account lid van is, met rol en aantal dozen. */
    public function memberships(): array
    {
        if (! $this->user()) {
            return [];
        }

        return db_connect()->table('memberships')
            ->select('verhuizingen.id, verhuizingen.naam, memberships.rol, (SELECT COUNT(*) FROM boxes WHERE boxes.verhuizing_id = verhuizingen.id AND boxes.status != \'leeg\') AS dozen')
            ->join('verhuizingen', 'verhuizingen.id = memberships.verhuizing_id')
            ->where('memberships.user_id', $this->user['id'])
            ->orderBy('verhuizingen.naam', 'ASC')
            ->get()->getResultArray();
    }

    /** Maakt een verhuizing actief voor dit account. Faalt als het account er geen lid van is. */
    public function switchTo(int $verhuizingId): bool
    {
        if (! $this->user() || ! $this->loadMembership($verhuizingId)) {
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
        if (! $this->user()) {
            return false;
        }

        return db_connect()->table('memberships')
            ->where('user_id', $this->user['id'])
            ->where('verhuizing_id', $verhuizingId)
            ->countAllResults() > 0;
    }
}
