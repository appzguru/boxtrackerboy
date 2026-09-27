<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Globaal: een global-admin die meekijkt in een bedrijfsverhuizing kan alleen lezen.
 * Elk verzoek dat iets zou kunnen wijzigen (alles behalve GET/HEAD) wordt geweigerd,
 * behalve meekijken stoppen en uitloggen. Alle schrijfacties in de app zitten achter POST.
 */
class MeekijkFilter implements FilterInterface
{
    private const TOEGESTAAN = ['beheer/meekijken/stop', 'logout'];

    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['access', 'url', 'icon', 'csrf']);

        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true) || ! access()->meekijken()) {
            return null;
        }

        if (in_array(trim($request->getUri()->getPath(), '/'), self::TOEGESTAAN, true)) {
            return null;
        }

        return service('response')->setStatusCode(403)->setBody(view('auth_message', [
            'title' => 'Alleen meekijken — Boxtracker',
            'kop'   => 'Je kijkt alleen mee',
            'tekst' => 'Tijdens meekijken kun je niets wijzigen. Stop met meekijken via het beheer.',
            'knop'  => ['url' => '/beheer', 'label' => 'Naar beheer'],
        ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
