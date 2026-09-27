<?php

namespace App\Filters;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * /beheer (whitelabel-plan.md stap 2): alleen op de klant-app (app.boxtracker.nl) en alleen
 * voor accounts in platform_admins. Niet ingelogd → inloggen; iemand anders of een
 * bedrijfssubdomein → gewoon 404, alsof het niet bestaat.
 */
class BeheerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf', 'format']);

        if (! tenant()->isKlant()) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! access()->user()) {
            return redirect()->to('/login?next=' . urlencode('/beheer'));
        }

        if (! access()->isPlatformAdmin()) {
            throw PageNotFoundException::forPageNotFound();
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
