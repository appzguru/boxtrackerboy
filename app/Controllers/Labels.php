<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Labels extends BaseController
{
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** Eén vast stickervel: 2 kolommen x 6 rijen, maar links en rechts is dezelfde doos —
     *  zodat je dezelfde sticker op twee kanten van de doos kunt plakken. Dus 6 dozen (=
     *  6 unieke QR-codes) per vel, 12 fysieke stickers. Machinaal snijden, dus geen exacte
     *  commerciele labelmaat nodig. */
    public const PRESET = ['cols' => 2, 'rows' => 6, 'w' => 100, 'h' => 45, 'mt' => 13.5, 'ml' => 5, 'gx' => 0, 'gy' => 0];

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
            'title' => 'Labels genereren — Boxtracker',
            'next'  => $this->nextNummer(new BoxModel()),
        ]);
    }

    public function generate()
    {
        $aantal = (int) $this->request->getPost('aantal');
        $aantal = max(1, min(300, $aantal ?: 6));

        $boxes = new BoxModel();
        $start = $this->nextNummer($boxes);
        $batch = [];

        for ($i = 0; $i < $aantal; $i++) {
            $nummer = $start + $i;
            $token  = $this->randomToken();
            $boxes->insert(['nummer' => $nummer, 'token' => $token, 'status' => 'leeg']);
            $batch[] = ['nummer' => $nummer, 'token' => $token];
        }

        session()->set('labels_batch', ['items' => $batch]);

        return redirect()->to('/labels/print');
    }

    private function batch(): array
    {
        $b = session()->get('labels_batch');

        return $b && ! empty($b['items']) ? $b : ['items' => []];
    }

    public function print()
    {
        $batch = $this->batch();
        if (! $batch['items']) {
            return redirect()->to('/labels');
        }

        $preset = self::PRESET;
        $sheets = array_chunk($batch['items'], $preset['rows']);

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
