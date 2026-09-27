<?php

namespace App\Filters;

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

        // Vóór de inlogcheck: een onbekende sticker hoort bij een andere omgeving.
        if ($need === 'any' && ($fallback = $this->stickerFallback($request)) !== null) {
            return redirect()->to($fallback);
        }

        $access = access();

        // Hardblock: het bedrijf voelt het, zijn klanten niet (die zijn nooit "geblokkeerd").
        if ($access->isGeblokkeerd()) {
            return service('response')->setStatusCode(403)->setBody(view('auth_message', [
                'title' => 'Account geblokkeerd',
                'kop'   => 'Je account is geblokkeerd',
                'tekst' => 'De toegang van ' . (tenant()->bedrijf()['naam'] ?? 'dit bedrijf') . ' tot Boxtracker is tijdelijk geblokkeerd. Neem contact op met Boxtracker om dit op te lossen.',
            ]));
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
            return redirect()->to('/verhuizingen');
        }

        if (! $access->can($need)) {
            return service('response')->setStatusCode(403)->setBody(
                view('errors/forbidden', ['title' => 'Geen toegang — Boxtracker'])
            );
        }

        return null;
    }

    private function stickerFallback(RequestInterface $request): ?string
    {
        $base = rtrim(config('Boxtracker')->stickerFallbackURL, '/');
        if ($base === '' || strtolower($request->getMethod()) !== 'get'
            || ! preg_match('#^/?d/(\d+)-([^/]+)$#', $request->getUri()->getPath(), $m)) {
            return null;
        }

        $loc = BoxModel::locateToken($m[2]);
        if ($loc && (int) $loc['nummer'] === (int) $m[1]) {
            return null;
        }

        return $base . '/d/' . $m[1] . '-' . rawurlencode($m[2]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
