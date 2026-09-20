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
        $db     = db_connect();
        $eigenaar = trim((string) $this->request->getGet('eigenaar'));

        $builder = $db->table('boxes')->where('status !=', 'leeg');
        if ($eigenaar !== '') {
            $builder->where('eigenaar', $eigenaar);
        }
        $rows = $builder->get()->getResultArray();

        $eigenaars = array_column(
            $db->table('boxes')->distinct()->select('eigenaar')->where('eigenaar IS NOT NULL')->where('eigenaar !=', '')->orderBy('eigenaar')->get()->getResultArray(),
            'eigenaar'
        );

        $perStatus = $this->groupBy($rows, 'status');
        $perPlek   = $this->groupBy($rows, 'huidige_locatie');
        $perDoel   = $this->groupBy($rows, 'einddoel');

        return $this->view('overview', [
            'title'     => 'Overzicht — Boxtracker',
            'totaal'    => count($rows),
            'eigenaar'  => $eigenaar,
            'eigenaars' => $eigenaars,
            'perStatus' => $perStatus,
            'perPlek'   => $perPlek,
            'perDoel'   => $perDoel,
        ]);
    }

    public function list()
    {
        $kind = $this->request->getGet('kind');
        $val  = $this->request->getGet('val');
        $sort = $this->request->getGet('sort') === 'recent' ? 'recent' : 'nr';

        $boxes = new BoxModel();
        $builder = $boxes->where('status !=', 'leeg');

        $titel = 'Alle dozen';
        if ($kind === 'status' && $val) {
            $builder->where('status', $val);
            $titel = status_pill($val)['label'];
        } elseif ($kind === 'plek' && $val) {
            $builder->where('huidige_locatie', $val === 'Nog niet bepaald' ? null : $val);
            $titel = $val;
        } elseif ($kind === 'doel' && $val) {
            $builder->where('einddoel', $val === 'Nog niet bepaald' ? null : $val);
            $titel = $val;
        } elseif ($kind === 'eigenaar' && $val) {
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
