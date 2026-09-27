<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Throwable;

/**
 * GET /health (whitelabel-plan.md stap 6) voor de externe uptimecheck: database, schrijfbare
 * writable/ en of er een mailserver is ingesteld. 200 als alles goed is, anders 503. Geeft
 * alleen ok/fout per onderdeel — geen hostnamen, paden of foutmeldingen.
 */
class Health extends Controller
{
    public function index()
    {
        $checks = [
            'database' => $this->database(),
            'opslag'   => $this->opslag(),
            'mail'     => config('Boxtracker')->isDev() || config('Email')->SMTPHost !== '',
        ];
        $ok = ! in_array(false, $checks, true);

        return $this->response
            ->setStatusCode($ok ? 200 : 503)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['ok' => $ok, 'checks' => array_map(static fn ($c) => $c ? 'ok' : 'fout', $checks)]);
    }

    private function database(): bool
    {
        try {
            return (int) db_connect()->query('SELECT 1 AS een')->getRow()->een === 1;
        } catch (Throwable) {
            return false;
        }
    }

    private function opslag(): bool
    {
        $file = WRITEPATH . 'uploads/.health-' . bin2hex(random_bytes(4));
        if (! is_dir(WRITEPATH . 'uploads')) {
            @mkdir(WRITEPATH . 'uploads', 0755, true);
        }
        $ok = @file_put_contents($file, 'ok') === 2;
        @unlink($file);

        return $ok;
    }
}
