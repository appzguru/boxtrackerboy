<?php

namespace App\Filters;

use App\Libraries\Tenant;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Globaal, vóór alles (whitelabel-plan.md §3): een subdomein zonder bedrijf geeft 404 —
 * geen inlogscherm en geen data. Een geblokkeerd bedrijf gaat hier gewoon door: de
 * blokkade treft alleen medewerkers (Access, AccessFilter), nooit hun klanten.
 */
class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf']);

        if (tenant()->status() !== Tenant::ONBEKEND) {
            return null;
        }

        return service('response')->setStatusCode(404)->setBody(view('auth_message', [
            'title' => 'Niet gevonden — Boxtracker',
            'kop'   => 'Dit adres bestaat niet',
            'tekst' => 'Controleer het adres, of ga naar boxtracker.nl.',
        ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
