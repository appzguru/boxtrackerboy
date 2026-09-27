<?php

namespace App\Controllers;

use App\Libraries\Access;

/**
 * Handjes-QR (handoff.md §3.3): toegang zonder account voor sjouwers en inpakkers.
 * De QR-code is 15 minuten scanbaar (door meerdere mensen); wie scant krijgt een
 * gast-sessie van `access_days` dagen op dat toestel.
 */
class Handjes extends BaseController
{
    public const QR_MINUTEN = 15;

    /** Admin: actieve gasten + nieuwe QR maken. */
    public function index()
    {
        $guests = db_connect()->table('guest_sessions')
            ->where('verhuizing_id', access()->verhuizingId())
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        return $this->view('handjes', [
            'title'  => 'Handjes — Boxtracker',
            'guests' => $guests,
        ]);
    }

    public function create()
    {
        $rol   = $this->request->getPost('rol') === 'helper' ? 'helper' : 'sjouwer';
        $dagen = max(1, min(7, (int) $this->request->getPost('dagen')));

        $db = db_connect();
        $db->table('guest_passes')->insert([
            'verhuizing_id'   => access()->verhuizingId(),
            'code'            => bin2hex(random_bytes(16)),
            'rol'             => $rol,
            'access_days'     => $dagen,
            'code_expires_at' => date('Y-m-d H:i:s', time() + self::QR_MINUTEN * 60),
            'created_by'      => access()->user()['id'] ?? null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/handjes/' . $db->insertID() . '/qr');
    }

    /** Admin: schermvullende QR met aftellende geldigheid. */
    public function qr(int $id)
    {
        $pass = db_connect()->table('guest_passes')
            ->where('id', $id)
            ->where('verhuizing_id', access()->verhuizingId())
            ->get()->getRowArray();

        if (! $pass) {
            return redirect()->to('/handjes');
        }

        return $this->view('handjes_qr', [
            'title'    => 'Handjes-QR — Boxtracker',
            'pass'     => $pass,
            'url'      => site_url('h/' . $pass['code']),
            'secondsLeft' => max(0, strtotime($pass['code_expires_at']) - time()),
        ]);
    }

    public function revoke(int $id)
    {
        db_connect()->table('guest_sessions')
            ->where('id', $id)
            ->where('verhuizing_id', access()->verhuizingId())
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/handjes');
    }

    /** Geldige pass bij deze QR-code (nog binnen de 15 minuten), met verhuizingnaam — alleen bij deze ingang. */
    private function usablePass(string $code): ?array
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $code)) {
            return null;
        }

        return tenant()->scope(db_connect()->table('guest_passes'))
            ->select('guest_passes.*, verhuizingen.naam AS verhuizing_naam')
            ->join('verhuizingen', 'verhuizingen.id = guest_passes.verhuizing_id')
            ->where('guest_passes.code', $code)
            ->where('guest_passes.code_expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray() ?: null;
    }

    /** Publiek: QR gescand → "Hoe heet je?". */
    public function join(string $code)
    {
        $pass = $this->usablePass($code);
        if (! $pass) {
            return $this->view('auth_message', [
                'title' => 'QR-code verlopen — Boxtracker',
                'kop'   => 'Deze QR-code is verlopen',
                'tekst' => 'Een handjes-QR is ' . self::QR_MINUTEN . ' minuten geldig. Vraag de admin om een nieuwe te laten zien.',
            ]);
        }

        if (access()->user()) {
            return $this->view('auth_message', [
                'title' => 'Al ingelogd — Boxtracker',
                'kop'   => 'Je bent al ingelogd',
                'tekst' => 'Je gebruikt Boxtracker met een account. Vraag de admin van verhuizing ' . $pass['verhuizing_naam'] . ' om een uitnodigingslink, dan zie je die verhuizing gewoon in je account.',
                'knop'  => ['url' => '/', 'label' => 'Naar Boxtracker'],
            ]);
        }

        return $this->view('handjes_join', [
            'title' => 'Meehelpen — Boxtracker',
            'pass'  => $pass,
        ]);
    }

    public function doJoin(string $code)
    {
        $pass = $this->usablePass($code);
        $naam = trim((string) $this->request->getPost('naam'));
        if (! $pass || access()->user()) {
            return redirect()->to('/h/' . $code);
        }
        if ($naam === '' || mb_strlen($naam) > 60) {
            return $this->view('handjes_join', ['title' => 'Meehelpen — Boxtracker', 'pass' => $pass, 'fout' => 'Vul je naam in.']);
        }

        $token   = bin2hex(random_bytes(32));
        $seconds = (int) $pass['access_days'] * 86400;
        db_connect()->table('guest_sessions')->insert([
            'guest_pass_id' => $pass['id'],
            'verhuizing_id' => $pass['verhuizing_id'],
            'rol'           => $pass['rol'],
            'naam'          => $naam,
            'token'         => $token,
            'expires_at'    => date('Y-m-d H:i:s', time() + $seconds),
            'created_at'    => date('Y-m-d H:i:s'),
            'last_used_at'  => date('Y-m-d H:i:s'),
        ]);

        $response = redirect()->to('/');
        set_long_cookie($response, Access::GUEST_COOKIE, $token, $seconds);

        return $response;
    }
}
