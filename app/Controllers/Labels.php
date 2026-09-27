<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Labels extends BaseController
{
    /** Eén vast stickervel: 2 kolommen x 6 rijen, maar links en rechts is dezelfde doos —
     *  zodat je dezelfde sticker op twee kanten van de doos kunt plakken. Dus 6 dozen (=
     *  6 unieke QR-codes) per vel, 12 fysieke stickers. Machinaal snijden, dus geen exacte
     *  commerciele labelmaat nodig. */
    public const PRESET = ['cols' => 2, 'rows' => 6, 'w' => 100, 'h' => 45, 'mt' => 13.5, 'ml' => 5, 'gx' => 0, 'gy' => 0];

    public function index()
    {
        return $this->view('labels', [
            'title' => 'Labels genereren — Boxtracker',
            'next'  => (new BoxModel())->nextNummer(),
            'max'   => config('Boxtracker')->maxLabelsPerKeer,
        ]);
    }

    public function generate()
    {
        $config = config('Boxtracker');
        $boxes  = new BoxModel();

        $ruimte = $config->maxDozenPerVerhuizing - $boxes->countAll();
        $aantal = (int) $this->request->getPost('aantal');
        $aantal = max(1, min($config->maxLabelsPerKeer, $ruimte, $aantal ?: 6));
        if ($ruimte <= 0) {
            return redirect()->to('/labels')->with('message', 'Deze verhuizing heeft het maximum van ' . $config->maxDozenPerVerhuizing . ' dozen bereikt.');
        }

        $start = $boxes->nextNummer();
        $batch = [];

        $db = db_connect();
        $db->transStart();
        for ($i = 0; $i < $aantal; $i++) {
            $nummer = $start + $i;
            $token  = BoxModel::newToken();
            $boxes->insert(['nummer' => $nummer, 'token' => $token, 'status' => 'leeg']);
            $batch[] = ['nummer' => $nummer, 'token' => $token];
        }
        $db->transComplete();

        session()->set('labels_batch', ['verhuizing_id' => access()->verhuizingId(), 'items' => $batch]);

        return redirect()->to('/labels/print');
    }

    private function batch(): array
    {
        $b = session()->get('labels_batch');

        return $b && ! empty($b['items']) && ($b['verhuizing_id'] ?? null) === access()->verhuizingId() ? $b : ['items' => []];
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
            'baseUrl' => tenant()->stickerBase(),
            'merk'    => tenant()->merk(),
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

        $base = tenant()->stickerBase();
        $out  = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['nummer', 'code', 'url'], ';');
        foreach ($batch['items'] as $b) {
            fputcsv($out, [box_nr($b['nummer']), $b['token'], $base . '/d/' . $b['nummer'] . '-' . $b['token']], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setBody($csv);
    }
}
