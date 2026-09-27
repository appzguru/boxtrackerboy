<?php

namespace App\Filters;

use App\Libraries\Tenant;
use App\Models\BoxModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rechten per route (handoff.md §2, §7). Argument:
 * - `user`    — ingelogd met een account; verhuizing niet nodig (keuzescherm, account);
 * - `any`     — account of gast; verhuizing niet nodig (sticker-routes /d/…);
 * - `sjouwer` / `helper` / `admin` — minstens die rol in de actieve verhuizing.
 *
 * Niet ingelogd → /login?next=…; account zonder actieve verhuizing → /verhuizingen;
 * te lage rol → 403.
 */
class AccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf', 'format']);
        $need   = $arguments[0] ?? 'sjouwer';

        // Vóór de inlogcheck: een sticker van een andere ingang (bedrijf ↔ klant-app) of een
        // onbekende sticker (andere omgeving) gaat door naar waar hij hoort.
        if ($need === 'any' && ($fallback = $this->stickerFallback($request)) !== null) {
            return redirect()->to($fallback);
        }

        $access = access();

        // Hardblock: het bedrijf voelt het, zijn klanten niet (die zijn nooit "geblokkeerd").
        if ($access->isGeblokkeerd()) {
            return self::geblokkeerd();
        }

        if (! $access->isAuthenticated() || ($need === 'user' && ! $access->user())) {
            $path = '/' . ltrim($request->getUri()->getPath(), '/');
            if (strtolower($request->getMethod()) === 'get' && $path !== '/') {
                return redirect()->to('/login?next=' . urlencode($path));
            }

            return redirect()->to('/login');
        }

        // `any`: sticker-routes — de controller bepaalt de verhuizing uit het token.
        if ($need === 'user' || $need === 'any') {
            return null;
        }

        if ($access->verhuizingId() === null) {
            // Planner en sales beginnen op hun planningsoverzicht.
            return redirect()->to($access->isBedrijfAdmin() ? '/bedrijf' : '/verhuizingen');
        }

        if (! $access->can($need)) {
            return service('response')->setStatusCode(403)->setBody(
                view('errors/forbidden', ['title' => 'Geen toegang — Boxtracker'])
            );
        }

        return null;
    }

    /** Blokkadepagina voor medewerkers van een bedrijf met een hardblock. */
    public static function geblokkeerd(): ResponseInterface
    {
        return service('response')->setStatusCode(403)->setBody(view('auth_message', [
            'title' => 'Account geblokkeerd',
            'kop'   => 'Je account is geblokkeerd',
            'tekst' => 'De toegang van ' . (tenant()->bedrijf()['naam'] ?? 'dit bedrijf') . ' tot Boxtracker is tijdelijk geblokkeerd. Neem contact op met Boxtracker om dit op te lossen.',
        ]));
    }

    private function stickerFallback(RequestInterface $request): ?string
    {
        $base = rtrim(config('Boxtracker')->stickerFallbackURL, '/');
        if (strtolower($request->getMethod()) !== 'get'
            || ! preg_match('#^/?d/(\d+)-([^/]+)$#', $request->getUri()->getPath(), $m)) {
            return null;
        }

        $loc = BoxModel::locateToken($m[2]);
        if ($loc && (int) $loc['nummer'] === (int) $m[1]) {
            return $this->andereIngang((int) $loc['verhuizing_id'], '/d/' . $m[1] . '-' . rawurlencode($m[2]));
        }

        return $base !== '' ? $base . '/d/' . $m[1] . '-' . rawurlencode($m[2]) : null;
    }

    /**
     * Hoort deze verhuizing bij een andere ingang, dan de URL daar (subdomein van het bedrijf,
     * of de klant-app). Anders null. Niet op dev met devBedrijf: daar is alles één host.
     */
    private function andereIngang(int $verhuizingId, string $path): ?string
    {
        $config = config('Boxtracker');
        if ($config->isDev() && $config->devBedrijf !== '') {
            return null;
        }

        $row = db_connect()->table('verhuizingen')
            ->select('verhuizingen.bedrijf_id, bedrijven.subdomein')
            ->join('bedrijven', 'bedrijven.id = verhuizingen.bedrijf_id', 'left')
            ->where('verhuizingen.id', $verhuizingId)
            ->get()->getRowArray();
        $bedrijfId = isset($row['bedrijf_id']) ? (int) $row['bedrijf_id'] : null;
        if (! $row || tenant()->owns($bedrijfId)) {
            return null;
        }
        // Global-admin die meekijkt: de doos opent gewoon hier (alleen-lezen).
        if (access()->meekijken() && access()->verhuizingId() === $verhuizingId) {
            return null;
        }

        $url = $bedrijfId === null
            ? rtrim(config('App')->baseURL, '/') . $path
            : rtrim(Tenant::urlVoor($row['subdomein']), '/') . $path;

        // Nooit naar dezelfde host terug (geen redirect-lus).
        [$host] = explode(':', (string) service('request')->getServer('HTTP_HOST'), 2);

        return strcasecmp((string) parse_url($url, PHP_URL_HOST), $host) === 0 ? null : $url;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
