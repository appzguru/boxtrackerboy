<?php

namespace App\Controllers\Beheer;

use App\Controllers\BaseController;
use App\Libraries\BeheerLog;

/**
 * Meekijken in een bedrijfsverhuizing (whitelabel-plan.md stap 2), voor support. Alleen-lezen:
 * App\Filters\MeekijkFilter weigert elk verzoek dat iets kan wijzigen. Start en stop worden
 * gelogd in beheer_log. Nooit bij particuliere verhuizingen.
 */
class Meekijken extends BaseController
{
    public function start(int $verhuizingId)
    {
        $v = db_connect()->table('verhuizingen')
            ->select('id, bedrijf_id')
            ->where('id', $verhuizingId)
            ->where('bedrijf_id IS NOT NULL')
            ->get()->getRowArray();

        if (! $v || ! access()->meekijkNaar($verhuizingId)) {
            return redirect()->to('/beheer');
        }

        BeheerLog::schrijf('meekijken_start', (int) $v['bedrijf_id'], $verhuizingId);

        return redirect()->to('/');
    }

    public function stop()
    {
        $access = access();
        $vid    = $access->meekijken() ? $access->verhuizingId() : null;
        $access->meekijkNaar(null);

        if ($vid === null) {
            return redirect()->to('/beheer');
        }

        $bedrijfId = (int) db_connect()->table('verhuizingen')->select('bedrijf_id')->where('id', $vid)->get()->getRow()->bedrijf_id;
        BeheerLog::schrijf('meekijken_stop', $bedrijfId, $vid);

        return redirect()->to('/beheer/bedrijven/' . $bedrijfId);
    }
}
