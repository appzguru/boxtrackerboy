<?php

if (! function_exists('current_account')) {
    /**
     * Het ingelogde account (pincode-identificatie, geen beveiliging), of null.
     * Gememoized per request.
     */
    function current_account(): ?array
    {
        static $account = null;
        static $checked = false;

        if ($checked) {
            return $account;
        }
        $checked = true;

        $token = service('request')->getCookie('account');
        if (! $token) {
            return null;
        }

        $row = db_connect()->table('account_sessions')
            ->select('accounts.id, accounts.naam')
            ->join('accounts', 'accounts.id = account_sessions.account_id')
            ->where('account_sessions.token', $token)
            ->get()
            ->getRowArray();

        return $account = $row ?: null;
    }
}

if (! function_exists('current_account_naam')) {
    function current_account_naam(): string
    {
        return current_account()['naam'] ?? '';
    }
}
