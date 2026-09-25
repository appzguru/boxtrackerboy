<?php

namespace App\Controllers;

use App\Libraries\Access;
use App\Models\UserModel;
use App\Models\VerhuizingModel;

/** Mijn account: naam, wachtwoord, account verwijderen (handoff.md §3.1, §9). */
class Account extends BaseController
{
    public function index()
    {
        return $this->view('account', [
            'title'   => 'Mijn account — Boxtracker',
            'user'    => access()->user(),
            'message' => session()->getFlashdata('message'),
            'fout'    => session()->getFlashdata('fout'),
        ]);
    }

    public function save()
    {
        $naam = trim((string) $this->request->getPost('naam'));
        if ($naam === '' || mb_strlen($naam) > 60) {
            return redirect()->to('/account')->with('fout', 'Vul je naam in.');
        }
        (new UserModel())->update(access()->user()['id'], ['naam' => $naam]);

        return redirect()->to('/account')->with('message', 'Opgeslagen.');
    }

    public function password()
    {
        $users   = new UserModel();
        $user    = $users->find(access()->user()['id']);
        $current = (string) $this->request->getPost('huidig');
        $new     = (string) $this->request->getPost('nieuw');

        if (! password_verify($current, $user['password_hash'])) {
            return redirect()->to('/account')->with('fout', 'Je huidige wachtwoord klopt niet.');
        }
        if (mb_strlen($new) < UserModel::MIN_PASSWORD) {
            return redirect()->to('/account')->with('fout', 'Kies een nieuw wachtwoord van minstens ' . UserModel::MIN_PASSWORD . ' tekens.');
        }

        $users->update($user['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        // Andere toestellen uitloggen, dit toestel ingelogd houden.
        db_connect()->table('sessions')
            ->where('user_id', $user['id'])
            ->where('token !=', (string) $this->request->getCookie(Access::USER_COOKIE))
            ->delete();

        return redirect()->to('/account')->with('message', 'Wachtwoord gewijzigd. Andere toestellen zijn uitgelogd.');
    }

    /**
     * Account verwijderen. Niet als je de enige admin bent van een verhuizing waar nog
     * anderen in zitten (eerst iemand admin maken). Verhuizingen waar je alleen in zit,
     * gaan mee.
     */
    public function delete()
    {
        $user  = access()->user();
        $users = new UserModel();
        $full  = $users->find($user['id']);

        if (! password_verify((string) $this->request->getPost('wachtwoord'), $full['password_hash'])) {
            return redirect()->to('/account')->with('fout', 'Wachtwoord klopt niet — account niet verwijderd.');
        }

        $db           = db_connect();
        $verhuizingen = new VerhuizingModel();
        $alone        = [];

        foreach ($db->table('memberships')->where('user_id', $user['id'])->get()->getResultArray() as $m) {
            $vid     = (int) $m['verhuizing_id'];
            $members = $db->table('memberships')->where('verhuizing_id', $vid)->countAllResults();
            if ($members === 1) {
                $alone[] = $vid;
            } elseif ($m['rol'] === 'admin' && $verhuizingen->adminCount($vid) === 1) {
                $naam = $verhuizingen->find($vid)['naam'] ?? '';

                return redirect()->to('/account')->with('fout', "Je bent de enige admin van verhuizing {$naam}. Maak eerst iemand anders admin, of verwijder die verhuizing.");
            }
        }

        foreach ($alone as $vid) {
            $dir = WRITEPATH . 'uploads/' . $vid;
            if (is_dir($dir)) {
                delete_files($dir, true);
                @rmdir($dir);
            }
            $verhuizingen->delete($vid);
        }
        $users->delete($user['id']);

        return redirect()->to('/login')->deleteCookie(Access::USER_COOKIE);
    }
}
