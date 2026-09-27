<?php

namespace App\Controllers\Bedrijf;

use App\Controllers\BaseController;
use App\Models\MedewerkerUitnodigingModel;

/**
 * Uitnodiging als medewerker van een bedrijf openen en accepteren, op het subdomein van dat
 * bedrijf. Nog geen account → registreren met de uitnodiging erbij (Auth::register).
 */
class Medewerker extends BaseController
{
    private function nietGeldig(): string
    {
        return $this->view('auth_message', [
            'title' => 'Uitnodiging',
            'kop'   => 'Deze uitnodiging werkt niet meer',
            'tekst' => 'De link is al gebruikt, ingetrokken of verlopen (een uitnodiging is ' . MedewerkerUitnodigingModel::DAGEN . ' dagen geldig). Vraag om een nieuwe.',
        ]);
    }

    private function fout(string $tekst): string
    {
        return $this->view('auth_message', [
            'title' => 'Uitnodiging',
            'kop'   => 'Dat lukt zo niet',
            'tekst' => $tekst,
        ]);
    }

    public function show(string $token)
    {
        $invite = (new MedewerkerUitnodigingModel())->findUsable($token);
        if (! $invite) {
            return $this->nietGeldig();
        }

        $user = access()->user();
        if (! $user) {
            return redirect()->to('/registreren?medewerker=' . $token);
        }
        if (! MedewerkerUitnodigingModel::emailPast($invite, $user)) {
            return $this->fout('Deze uitnodiging is voor ' . $invite['email'] . ', maar je bent ingelogd als ' . $user['email'] . '. Log uit en log in met het juiste adres.');
        }

        return $this->view('bedrijf/medewerker_uitnodiging', [
            'title'  => 'Uitnodiging — ' . $invite['bedrijf_naam'],
            'invite' => $invite,
        ]);
    }

    public function accept(string $token)
    {
        $model  = new MedewerkerUitnodigingModel();
        $invite = $model->findUsable($token);
        $user   = access()->user();
        if (! $invite || ! $user) {
            return redirect()->to('/medewerker/' . $token);
        }

        if ($fout = $model->accept($invite, $user)) {
            return $this->fout($fout);
        }

        return redirect()->to('/verhuizingen');
    }
}
