<?php

namespace App\Controllers;

use App\Models\InviteModel;

/** Uitnodigingslink openen en accepteren (handoff.md §3.2). Geen login-filter: ook voor nieuwe mensen. */
class Uitnodiging extends BaseController
{
    public function show(string $token)
    {
        $invite = (new InviteModel())->findUsable($token);
        if (! $invite) {
            return $this->view('auth_message', [
                'title' => 'Uitnodiging — Boxtracker',
                'kop'   => 'Deze uitnodiging werkt niet meer',
                'tekst' => 'De link is al gebruikt, ingetrokken of verlopen (een uitnodiging is ' . InviteModel::DAGEN . ' dagen geldig). Vraag om een nieuwe link.',
                'knop'  => ['url' => '/', 'label' => 'Naar Boxtracker'],
            ]);
        }

        // Nog geen account: registreren met de uitnodiging erbij.
        if (! access()->user()) {
            return redirect()->to('/registreren?uitnodiging=' . $token);
        }

        return $this->view('uitnodiging', [
            'title'  => 'Uitnodiging — Boxtracker',
            'invite' => $invite,
        ]);
    }

    public function accept(string $token)
    {
        $user   = access()->user();
        $invite = (new InviteModel())->findUsable($token);
        if (! $user || ! $invite) {
            return redirect()->to('/uitnodiging/' . $token);
        }

        (new InviteModel())->accept($invite, (int) $user['id']);
        access()->switchTo((int) $invite['verhuizing_id']);

        return redirect()->to('/');
    }
}
