<?php

use App\Libraries\Access;
use CodeIgniter\HTTP\ResponseInterface;

if (! function_exists('access')) {
    /** Wie, welke verhuizing, welke rol — zie App\Libraries\Access. */
    function access(): Access
    {
        return service('access');
    }
}

if (! function_exists('tenant')) {
    /** Klant-app of bedrijfssubdomein — zie App\Libraries\Tenant. */
    function tenant(): App\Libraries\Tenant
    {
        return service('tenant');
    }
}

if (! function_exists('random_code')) {
    /** Willekeurige code uit het overtypbare alfabet (zonder i, l, o, 0 en 1). */
    function random_code(int $length): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $max      = strlen($alphabet) - 1;
        $code     = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}

if (! function_exists('set_long_cookie')) {
    /** Httponly-cookie die een jaar blijft (sessietokens voor accounts en gasten). */
    function set_long_cookie(ResponseInterface $response, string $name, string $value, int $seconds = 31536000): void
    {
        $response->setCookie([
            'name'     => $name,
            'value'    => $value,
            'expire'   => $seconds,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => service('request')->isSecure(),
        ]);
    }
}

if (! function_exists('login_user')) {
    /**
     * Start een account-sessie: nieuw token in `sessions`, cookie op de response.
     * Een eventuele gast-cookie op dit toestel wordt weggehaald (account gaat voor).
     */
    function login_user(int $userId, ResponseInterface $response, ?int $activeVerhuizingId = null): void
    {
        $token = bin2hex(random_bytes(32));
        db_connect()->table('sessions')->insert([
            'user_id'              => $userId,
            'token'                => $token,
            'active_verhuizing_id' => $activeVerhuizingId,
            'created_at'           => date('Y-m-d H:i:s'),
            'last_used_at'         => date('Y-m-d H:i:s'),
        ]);

        set_long_cookie($response, Access::USER_COOKIE, $token);
        $response->deleteCookie(Access::GUEST_COOKIE);
    }
}
