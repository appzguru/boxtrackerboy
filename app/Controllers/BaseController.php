<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected $helpers = ['url', 'form', 'access', 'format', 'icon', 'filesystem', 'csrf'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);
    }

    /** Rendert een view met de naam van de bezoeker erbij. */
    protected function view(string $name, array $data = []): string
    {
        $data['account_naam'] = access()->naam();

        return view($name, $data);
    }

    /** 403 met uitleg: de rol in deze verhuizing is niet genoeg. */
    protected function forbidden(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setStatusCode(403)->setBody(
            $this->view('errors/forbidden', ['title' => 'Geen toegang — Boxtracker'])
        );
    }
}
