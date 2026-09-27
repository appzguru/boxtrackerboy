<?php

namespace App\Filters;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bedrijfsschermen (whitelabel-plan.md stap 4), alleen op een bedrijfssubdomein en alleen
 * voor actieve medewerkers met de juiste rol. Argument:
 * - `planner` — alleen de planner (medewerkers, verhuizingen aanmaken en toewijzen);
 * - `kantoor` — planner en sales (planningsoverzicht, verhuizing bekijken, opname).
 */
class BedrijfFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf', 'format']);

        if (! tenant()->isBedrijf()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $access = access();
        if ($access->isGeblokkeerd()) {
            return AccessFilter::geblokkeerd();
        }
        if (! $access->user()) {
            return redirect()->to('/login?next=' . urlencode('/' . ltrim($request->getUri()->getPath(), '/')));
        }

        $rollen = ($arguments[0] ?? 'planner') === 'kantoor' ? ['planner', 'sales'] : ['planner'];
        $m      = $access->medewerker();
        if (! $m || ! $m['actief'] || ! in_array($m['rol'], $rollen, true)) {
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
