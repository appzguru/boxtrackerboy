<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Labels extends BaseController
{
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** Stickervel-presets, overgenomen uit de eerdere "Doosetiketten"-opzet: kolommen/rijen,
     *  labelgrootte (mm), margin-top/-left en de tussenruimte tussen kolommen/rijen (mm). */
    public const PRESETS = [
        '24' => ['label' => '24 per vel — 70 × 37 mm', 'cols' => 3, 'rows' => 8, 'w' => 70, 'h' => 37, 'mt' => 12.7, 'ml' => 7, 'gx' => 2.5, 'gy' => 0],
        '21' => ['label' => '21 per vel — 63,5 × 38,1 mm', 'cols' => 3, 'rows' => 7, 'w' => 63.5, 'h' => 38.1, 'mt' => 15.1, 'ml' => 7.25, 'gx' => 2.5, 'gy' => 0],
        '12' => ['label' => '12 per vel — 100 × 45 mm (machinaal snijden)', 'cols' => 2, 'rows' => 6, 'w' => 100, 'h' => 45, 'mt' => 13.5, 'ml' => 5, 'gx' => 0, 'gy' => 0],
        '8'  => ['label' => '8 per vel — 99,1 × 67,7 mm', 'cols' => 2, 'rows' => 4, 'w' => 99.1, 'h' => 67.7, 'mt' => 13, 'ml' => 5, 'gx' => 5, 'gy' => 0],
    ];

    private function randomToken(int $length = 4): string
    {
        $token = '';
        $max   = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $max)];
        }

        return $token;
    }

    private function nextNummer(BoxModel $boxes): int
    {
        $row = $boxes->selectMax('nummer')->first();

        return ((int) ($row['nummer'] ?? 0)) + 1;
    }

    public function index()
    {
        return $this->view('labels', [
            'title'    => 'Labels genereren — Boxtracker',
            'next'     => $this->nextNummer(new BoxModel()),
            'presets'  => self::PRESETS,
        ]);
    }

    public function generate()
    {
        $aantal = (int) $this->request->getPost('aantal');
        $aantal = max(1, min(600, $aantal ?: 12));

        $preset = $this->request->getPost('preset');
        if (! isset(self::PRESETS[$preset])) {
            $preset = '12';
        }

        $boxes = new BoxModel();
        $start = $this->nextNummer($boxes);
        $batch = [];

        for ($i = 0; $i < $aantal; $i++) {
            $nummer = $start + $i;
            $token  = $this->randomToken();
            $boxes->insert(['nummer' => $nummer, 'token' => $token, 'status' => 'leeg']);
            $batch[] = ['nummer' => $nummer, 'token' => $token];
        }

        session()->set('labels_batch', ['preset' => $preset, 'items' => $batch]);

        return redirect()->to('/labels/print');
    }

    private function batch(): array
    {
        $b = session()->get('labels_batch');

        return $b && ! empty($b['items']) ? $b : ['preset' => '12', 'items' => []];
    }

    public function print()
    {
        $batch = $this->batch();
        if (! $batch['items']) {
            return redirect()->to('/labels');
        }

        $preset = self::PRESETS[$batch['preset']] ?? self::PRESETS['12'];
        $sheets = array_chunk($batch['items'], $preset['cols'] * $preset['rows']);

        return view('labels_print', [
            'title'   => 'Stickers printen — Boxtracker',
            'baseUrl' => rtrim(base_url(), '/'),
            'preset'  => $preset,
            'sheets'  => $sheets,
            'aantal'  => count($batch['items']),
        ]);
    }

    public function csv()
    {
        $batch = $this->batch();
        if (! $batch['items']) {
            return redirect()->to('/labels');
        }

        $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="boxtracker-labels-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['nummer', 'code', 'url'], ';');
        foreach ($batch['items'] as $b) {
            fputcsv($out, [box_nr($b['nummer']), $b['token'], base_url('d/' . $b['nummer'] . '-' . $b['token'])], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setBody($csv);
    }
}
