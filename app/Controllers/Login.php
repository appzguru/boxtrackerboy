<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\AccountSessionModel;

class Login extends BaseController
{
    private const COOKIE_YEARS = 10;

    public function index()
    {
        $next = $this->request->getGet('next') ?: '/';
        if (current_account()) {
            return redirect()->to($next);
        }

        return $this->view('login', [
            'title' => 'Inloggen — Boxtracker',
            'next'  => $next,
            'fout'  => (bool) session()->getFlashdata('login_fout'),
        ]);
    }

    public function attempt()
    {
        $pincode = trim((string) $this->request->getPost('pincode'));
        $next    = $this->request->getPost('next') ?: '/';

        $account = (new AccountModel())->findByPincode($pincode);
        if (! $account) {
            session()->setFlashdata('login_fout', true);

            return redirect()->to('/login?next=' . urlencode($next));
        }

        $token = (new AccountSessionModel())->createFor((int) $account['id']);

        $response = redirect()->to($next);
        $response->setCookie([
            'name'     => 'account',
            'value'    => $token,
            'expire'   => 60 * 60 * 24 * 365 * self::COOKIE_YEARS,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $this->request->isSecure(),
        ]);

        return $response;
    }

    public function logout()
    {
        $token = $this->request->getCookie('account');
        if ($token) {
            (new AccountSessionModel())->deleteByToken($token);
        }

        return redirect()->to('/login')->setCookie('account', '', 1);
    }
}
