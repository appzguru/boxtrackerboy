<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Overview extends BaseController
{
    private function groupBy(array $rows, string $field): array
    {
        $counts = [];
        foreach ($rows as $r) {
            $key = $r[$field] !== '' && $r[$field] !== null ? $r[$field] : 'Nog niet bepaald';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        arsort($counts);

        return $counts;
    }

    public function index()
    {
        // Eigenaar is inhoud-informatie: sjouwers filteren er niet op (handoff.md §2).
        $magEigenaar = access()->can('helper');
        $eigenaar    = $magEigenaar ? trim((string) $this->request->getGet('eigenaar')) : '';

        $boxes = new BoxModel();
        $boxes->where('status !=', 'leeg');
        if ($eigenaar !== '') {
            $boxes->where('eigenaar', $eigenaar);
        }
        $rows = $boxes->findAll();

        $eigenaars = $magEigenaar ? array_column(
            (new BoxModel())->distinct()->select('eigenaar')->where('eigenaar IS NOT NULL')->where('eigenaar !=', '')->orderBy('eigenaar')->findAll(),
            'eigenaar'
        ) : [];

        return $this->view('overview', [
            'title'     => 'Overzicht — Boxtracker',
            'totaal'    => count($rows),
            'eigenaar'  => $eigenaar,
            'eigenaars' => $eigenaars,
            'perStatus' => $this->groupBy($rows, 'status'),
            'perPlek'   => $this->groupBy($rows, 'huidige_locatie'),
            'perDoel'   => $this->groupBy($rows, 'einddoel'),
        ]);
    }

    public function list()
    {
        $kind = $this->request->getGet('kind');
        $val  = $this->request->getGet('val');
        $sort = $this->request->getGet('sort') === 'recent' ? 'recent' : 'nr';

        $builder = (new BoxModel())->where('status !=', 'leeg');

        $titel = 'Alle dozen';
        if ($kind === 'status' && $val) {
            $vals = array_filter(explode(',', $val));
            if (count($vals) > 1) {
                $builder->whereIn('status', $vals);
                $titel = 'Open doos';
            } else {
                $builder->where('status', $val);
                $titel = status_pill($val)['label'];
            }
        } elseif ($kind === 'plek' && $val) {
            $builder->where('huidige_locatie', $val === 'Nog niet bepaald' ? null : $val);
            $titel = $val;
        } elseif ($kind === 'doel' && $val) {
            $builder->where('einddoel', $val === 'Nog niet bepaald' ? null : $val);
            $titel = $val;
        } elseif ($kind === 'eigenaar' && $val && access()->can('helper')) {
            $builder->where('eigenaar', $val);
            $titel = $val;
        }

        $rows = $builder->orderBy($sort === 'recent' ? 'updated_at' : 'nummer', $sort === 'recent' ? 'DESC' : 'ASC')->findAll();

        return $this->view('box_list', [
            'title' => $titel . ' — Boxtracker',
            'titel' => $titel,
            'rows'  => $rows,
            'sort'  => $sort,
            'kind'  => $kind,
            'val'   => $val,
        ]);
    }
}
