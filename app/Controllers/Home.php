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

        $ingepakt = $counts['ingepakt'] ?? 0;
        $opslag   = ($counts['opgeslagen'] ?? 0) + ($counts['geopend'] ?? 0);
        $uitgepakt = $counts['uitgepakt'] ?? 0;
        $hasBoxes = ($ingepakt + $opslag + $uitgepakt) > 0;

        return $this->view('home', [
            'title'      => 'Boxtracker',
            'hasAny'     => $totaalAlles > 0,
            'hasBoxes'   => $hasBoxes,
            'ingepakt'   => $ingepakt,
            'opslag'     => $opslag,
            'uitgepakt'  => $uitgepakt,
        ]);
    }
}
