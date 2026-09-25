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

        return $this->view('home', [
            'title'      => 'Boxtracker',
            'hasAny'     => $totaalAlles > 0,
            'hasBoxes'   => $hasBoxes,
            'openDoos'   => $openDoos,
            'opslag'     => $opslag,
            'uitgepakt'  => $uitgepakt,
        ]);
    }
}
