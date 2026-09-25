<?php

namespace App\Controllers;

use App\Models\VerhuizingModel;

/** Keuzescherm, nieuwe verhuizing, wisselen, menu en verhuizing-instellingen (handoff.md §3.4). */
class Verhuizingen extends BaseController
{
    public function index()
    {
        $access = access();

        return $this->view('verhuizingen', [
            'title'       => 'Verhuizingen — Boxtracker',
            'memberships' => $access->memberships(),
            'activeId'    => $access->verhuizingId(),
            'magNieuw'    => count($access->memberships()) < config('Boxtracker')->maxVerhuizingenPerAccount,
        ]);
    }

    public function create()
    {
        $user = access()->user();
        $naam = trim((string) $this->request->getPost('naam'));

        if ($naam === '' || mb_strlen($naam) > 80) {
            return redirect()->to('/verhuizingen')->with('message', 'Geef de verhuizing een naam (max. 80 tekens).');
        }

        $model = new VerhuizingModel();
        if ($model->countForUser($user['id']) >= config('Boxtracker')->maxVerhuizingenPerAccount) {
            return redirect()->to('/verhuizingen')->with('message', 'Je zit aan het maximum aantal verhuizingen.');
        }

        $id = $model->createFor($user['id'], $naam);
        access()->switchTo($id);

        return redirect()->to('/');
    }

    public function choose(int $id)
    {
        if (! access()->switchTo($id)) {
            return redirect()->to('/verhuizingen');
        }

        return redirect()->to('/');
    }

    public function menu()
    {
        $access = access();

        return $this->view('menu', [
            'title'       => 'Menu — Boxtracker',
            'user'        => $access->user(),
            'guest'       => $access->guest(),
            'verhuizing'  => $access->verhuizing(),
            'rol'         => $access->rol(),
            'aantal'      => count($access->memberships()),
        ]);
    }

    /** Admin: naam wijzigen. */
    public function settings()
    {
        return $this->view('verhuizing_settings', [
            'title'      => 'Verhuizing — Boxtracker',
            'verhuizing' => access()->verhuizing(),
        ]);
    }

    public function rename()
    {
        $naam = trim((string) $this->request->getPost('naam'));
        if ($naam !== '' && mb_strlen($naam) <= 80) {
            (new VerhuizingModel())->update(access()->verhuizingId(), ['naam' => $naam]);
        }

        return redirect()->to('/verhuizing')->with('message', 'Opgeslagen.');
    }

    /** Admin: verhuizing met alles erin verwijderen. Bevestigen door de naam over te typen. */
    public function delete()
    {
        $verhuizing = access()->verhuizing();
        if (trim((string) $this->request->getPost('bevestig')) !== $verhuizing['naam']) {
            return redirect()->to('/verhuizing')->with('message', 'Typ de naam precies over om te verwijderen.');
        }

        $dir = WRITEPATH . 'uploads/' . (int) $verhuizing['id'];
        if (is_dir($dir)) {
            delete_files($dir, true);
            @rmdir($dir);
        }
        // Dozen, foto's, locaties, leden, uitnodigingen en gasten gaan mee via ON DELETE CASCADE.
        (new VerhuizingModel())->delete($verhuizing['id']);

        return redirect()->to('/verhuizingen')->with('message', 'Verhuizing ' . $verhuizing['naam'] . ' is verwijderd.');
    }
}
