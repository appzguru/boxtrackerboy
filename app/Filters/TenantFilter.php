<?php

namespace App\Filters;

use App\Libraries\Tenant;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Globaal, vóór alles (whitelabel-plan.md §3): een subdomein zonder bedrijf geeft 404,
 * een geblokkeerd bedrijf een nette "tijdelijk niet beschikbaar" — in beide gevallen
 * geen inlogscherm en geen data. De klant-app en actieve bedrijven gaan gewoon door.
 */
class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf']);

        return match (tenant()->status()) {
            Tenant::ONBEKEND    => service('response')->setStatusCode(404)->setBody(view('auth_message', [
                'title' => 'Niet gevonden — Boxtracker',
                'kop'   => 'Dit adres bestaat niet',
                'tekst' => 'Controleer het adres, of ga naar boxtracker.nl.',
            ])),
            Tenant::GEBLOKKEERD => service('response')->setStatusCode(503)->setBody(view('auth_message', [
                'title' => 'Tijdelijk niet beschikbaar',
                'kop'   => 'Tijdelijk niet beschikbaar',
                'tekst' => 'Deze omgeving is op dit moment niet beschikbaar. Neem contact op met je verhuizer.',
            ])),
            default             => null,
        };
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
