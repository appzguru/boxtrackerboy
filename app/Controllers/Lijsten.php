<?php

namespace App\Controllers;

use App\Models\BoxModel;

/**
 * Papieren lijsten voor wie geen telefoon (of geen zin) heeft om elke doos te scannen
 * (handoff.md §14): een lijst bij de voordeur (nummer → bestemming), een lijst per kamer
 * om op te hangen, en een controlelijst die verwacht tegen aangekomen afzet.
 *
 * Alleen nummer, bestemming en plek — geen inhoud — dus toegankelijk voor iedereen in de
 * verhuizing, ook sjouwers (net als de doospagina in de sjouwer-weergave).
 */
class Lijsten extends BaseController
{
    public function index()
    {
        return $this->view('lijsten', ['title' => 'Lijsten — Boxtracker']);
    }

    public function deur()
    {
        return $this->view('lijst_deur', [
            'title' => 'Bij de voordeur — Boxtracker',
            'rows'  => (new BoxModel())->forLists(),
        ]);
    }

    public function kamers()
    {
        $perKamer = [];
        foreach ((new BoxModel())->forLists() as $r) {
            $doel = trim((string) $r['einddoel']);
            $perKamer[$doel !== '' ? $doel : 'Nog niet bepaald'][] = $r['nummer'];
        }
        ksort($perKamer, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->view('lijst_kamers', [
            'title'    => 'Per kamer — Boxtracker',
            'perKamer' => $perKamer,
        ]);
    }

    /** Per bestemming: welke dozen zijn al aangekomen (huidige_locatie = einddoel) en welke nog niet. */
    public function controle()
    {
        $kamers = [];
        foreach ((new BoxModel())->forLists() as $r) {
            $doel = trim((string) $r['einddoel']);
            if ($doel === '') {
                continue;
            }
            $plek = trim((string) $r['huidige_locatie']);
            $kamers[$doel]['naam'] ??= $doel;
            $kamers[$doel][$plek === $doel ? 'klopt' : 'ontbreekt'][] = $r['nummer'];
        }
        ksort($kamers, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->view('lijst_controle', [
            'title'  => 'Controle — Boxtracker',
            'kamers' => $kamers,
        ]);
    }
}
