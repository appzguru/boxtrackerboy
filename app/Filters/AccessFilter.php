<?php

namespace App\Filters;

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
        $access = access();

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

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
