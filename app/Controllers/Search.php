<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Search extends BaseController
{
    public function index()
    {
        $q       = trim((string) $this->request->getGet('q'));
        $results = $q !== '' ? (new BoxModel())->search($q) : [];

        return $this->view('search', [
            'title'   => 'Zoeken — Boxtracker',
            'q'       => $q,
            'results' => $results,
        ]);
    }
}
