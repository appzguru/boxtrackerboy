<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pincode-identificatie (geen beveiliging, zie handoff.md §2): zonder account-cookie
 * ga je naar /login. Na een geslaagde pincode kom je terug op de pagina die je wilde.
 */
class AccountFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('account');

        if (current_account()) {
            return;
        }

        $next = $request->getUri()->getPath();
        if ($request->getMethod() === 'get' && $next !== '' && $next !== 'login') {
            return redirect()->to('/login?next=' . urlencode('/' . ltrim($next, '/')));
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
