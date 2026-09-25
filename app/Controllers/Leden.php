<?php

namespace App\Controllers;

use App\Models\InviteModel;
use App\Models\VerhuizingModel;

/** Admin: leden en uitnodigingslinks van de actieve verhuizing (handoff.md §3.2). */
class Leden extends BaseController
{
    public function index()
    {
        $access = access();
        $vid    = $access->verhuizingId();
        $new    = session()->getFlashdata('invite_token');

        return $this->view('leden', [
            'title'     => 'Leden — Boxtracker',
            'members'   => (new VerhuizingModel())->members($vid),
            'invites'   => (new InviteModel())->openFor($vid),
            'newLink'   => $new ? site_url('uitnodiging/' . $new) : null,
            'me'        => $access->user()['id'] ?? null,
            'verified'  => ! empty($access->user()['email_verified_at']),
            'message'   => session()->getFlashdata('message'),
        ]);
    }

    public function invite()
    {
        $user = access()->user();
        if (! $user || empty($user['email_verified_at'])) {
            return redirect()->to('/leden')->with('message', 'Bevestig eerst je e-mailadres, dan kun je anderen uitnodigen.');
        }

        $rol    = $this->request->getPost('rol') === 'admin' ? 'admin' : 'helper';
        $invite = (new InviteModel())->createFor(access()->verhuizingId(), $rol, (int) $user['id']);

        return redirect()->to('/leden')->with('invite_token', $invite['token']);
    }

    public function revokeInvite(int $id)
    {
        $invites = new InviteModel();
        $invite  = $invites->where('verhuizing_id', access()->verhuizingId())->find($id);
        if ($invite) {
            $invites->update($id, ['revoked_at' => date('Y-m-d H:i:s')]);
        }

        return redirect()->to('/leden');
    }

    /** Lidmaatschap van déze verhuizing, of null. */
    private function membership(int $id): ?array
    {
        return db_connect()->table('memberships')
            ->where('id', $id)
            ->where('verhuizing_id', access()->verhuizingId())
            ->get()->getRowArray() ?: null;
    }

    public function setRole(int $id)
    {
        $m   = $this->membership($id);
        $rol = $this->request->getPost('rol') === 'admin' ? 'admin' : 'helper';
        if (! $m) {
            return redirect()->to('/leden');
        }

        if ($m['rol'] === 'admin' && $rol === 'helper' && (new VerhuizingModel())->adminCount((int) $m['verhuizing_id']) <= 1) {
            return redirect()->to('/leden')->with('message', 'Er moet minstens één admin overblijven.');
        }

        db_connect()->table('memberships')->where('id', $id)->update(['rol' => $rol]);

        return redirect()->to('/leden');
    }

    public function remove(int $id)
    {
        $m = $this->membership($id);
        if (! $m) {
            return redirect()->to('/leden');
        }

        if ($m['rol'] === 'admin' && (new VerhuizingModel())->adminCount((int) $m['verhuizing_id']) <= 1) {
            return redirect()->to('/leden')->with('message', 'Er moet minstens één admin overblijven.');
        }

        db_connect()->table('memberships')->where('id', $id)->delete();
        // Wie eruit ligt, mag deze verhuizing ook niet meer als actieve hebben.
        db_connect()->table('sessions')
            ->where('user_id', $m['user_id'])
            ->where('active_verhuizing_id', $m['verhuizing_id'])
            ->update(['active_verhuizing_id' => null]);

        if ((int) $m['user_id'] === (int) (access()->user()['id'] ?? 0)) {
            return redirect()->to('/verhuizingen');
        }

        return redirect()->to('/leden');
    }
}
