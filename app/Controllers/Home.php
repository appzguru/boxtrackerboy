<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Home extends BaseController
{
    public function index()
    {
        $boxes = new BoxModel();
        $totaalAlles = $boxes->countAll();
        $counts = $boxes->counts();

        $openDoos  = ($counts['ingepakt'] ?? 0) + ($counts['geopend'] ?? 0);
        $opslag    = $counts['opgeslagen'] ?? 0;
        $uitgepakt = $counts['uitgepakt'] ?? 0;
        $hasBoxes  = ($openDoos + $opslag + $uitgepakt) > 0;

        // Foto-opname (whitelabel): alleen bij een verhuizing van een bedrijf, niet voor sjouwers.
        $opname = null;
        if (access()->can('helper') && db_connect()->table('verhuizingen')->where('id', access()->verhuizingId())->where('bedrijf_id IS NOT NULL')->countAllResults()) {
            $opname = (new \App\Models\OpnameItemModel())->overzicht();
        }

        return $this->view('home', [
            'opname'     => $opname,
            'title'      => 'Boxtracker',
            'hasAny'     => $totaalAlles > 0,
            'hasBoxes'   => $hasBoxes,
            'openDoos'   => $openDoos,
            'opslag'     => $opslag,
            'uitgepakt'  => $uitgepakt,
        ]);
    }
}
