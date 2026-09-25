<?php

namespace App\Controllers;

use App\Models\BoxModel;
use App\Models\LocationModel;
use App\Models\MovementModel;

class Move extends BaseController
{
    public function start()
    {
        $locations = new LocationModel();

        return $this->view('move_dest', [
            'title' => 'Dozen verplaatsen — Boxtracker',
            'recent' => (new MovementModel())->recentDestinations(8, $locations->hiddenNames()),
        ]);
    }

    public function hideDestination()
    {
        $naam = trim((string) $this->request->getPost('naam'));
        if ($naam !== '') {
            (new LocationModel())->hide($naam);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function go()
    {
        $bestemming = trim((string) $this->request->getPost('bestemming'));
        if ($bestemming === '') {
            return redirect()->to('/verplaats');
        }

        $batch = bin2hex(random_bytes(16));
        session()->set('move_batch', [
            'id'            => $batch,
            'verhuizing_id' => access()->verhuizingId(),
            'bestemming'    => $bestemming,
            'items'         => [],
        ]);

        return redirect()->to('/verplaats/' . $batch . '/scan');
    }

    /** De batch uit de sessie — alleen als hij bij de actieve verhuizing hoort. */
    private function activeBatch(string $batch): ?array
    {
        $b = session()->get('move_batch');

        return ($b && $b['id'] === $batch && ($b['verhuizing_id'] ?? null) === access()->verhuizingId()) ? $b : null;
    }

    /** Regel onder het nummer in de scanlijst: inhoud voor helpers, bestemming voor sjouwers. */
    private function lineFor(array $box): string
    {
        if (access()->can('helper')) {
            return first_line($box['omschrijving']) ?: '(nog geen inhoud ingevuld)';
        }

        return $box['einddoel'] ? 'Moet naar ' . $box['einddoel'] : '';
    }

    public function scan(string $batch)
    {
        $b = $this->activeBatch($batch);
        if (! $b) {
            return redirect()->to('/verplaats');
        }

        return $this->view('move_scan', [
            'title'      => 'Dozen scannen — Boxtracker',
            'batch'      => $batch,
            'bestemming' => $b['bestemming'],
            'items'      => array_values($b['items']),
        ]);
    }

    public function doScan(string $batch)
    {
        $b = $this->activeBatch($batch);
        if (! $b) {
            return $this->response->setStatusCode(410)->setJSON(['ok' => false, 'reason' => 'batch_weg']);
        }

        $code = trim((string) $this->request->getPost('code'));
        if (! preg_match('/^(\d{1,6})-([a-zA-Z0-9]{2,10})$/', $code, $m)) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'onbekend']);
        }
        $nummer = (int) $m[1];
        $token  = $m[2];

        $loc = BoxModel::locateToken($token);
        if (! $loc || (int) $loc['nummer'] !== $nummer) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'onbekend']);
        }
        if ((int) $loc['verhuizing_id'] !== $b['verhuizing_id']) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'andere_verhuizing']);
        }

        $box = (new BoxModel())->find((int) $loc['id']);
        if (! $box) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'onbekend']);
        }

        if (isset($b['items'][$nummer])) {
            return $this->response->setJSON([
                'ok' => true, 'dubbel' => true, 'count' => count($b['items']),
                'nummer' => box_nr($nummer), 'line' => $b['items'][$nummer]['line'],
            ]);
        }

        $b['items'][$nummer] = [
            'box_id' => (int) $box['id'],
            'nummer' => $nummer,
            'line'   => $this->lineFor($box),
        ];
        session()->set('move_batch', $b);

        return $this->response->setJSON([
            'ok' => true, 'dubbel' => false, 'count' => count($b['items']),
            'nummer' => box_nr($nummer), 'line' => $b['items'][$nummer]['line'],
        ]);
    }

    public function finish(string $batch)
    {
        $b = $this->activeBatch($batch);
        if (! $b || ! $b['items']) {
            session()->remove('move_batch');

            return redirect()->to('/verplaats');
        }

        $boxes     = new BoxModel();
        $movements = new MovementModel();
        $naam      = access()->naam();
        $now       = date('Y-m-d H:i:s');

        $db = db_connect();
        $db->transStart();
        foreach ($b['items'] as $item) {
            $current = $boxes->find($item['box_id']);
            if (! $current) {
                continue;
            }

            $movements->insert([
                'box_id'       => $item['box_id'],
                'van_locatie'  => $current['huidige_locatie'],
                'naar_locatie' => $b['bestemming'],
                'door'         => $naam,
                'op'           => $now,
                'batch_id'     => $b['id'],
            ]);

            $update = ['huidige_locatie' => $b['bestemming']];
            if ($current['status'] === 'ingepakt') {
                $update['status'] = 'opgeslagen';
            }
            $boxes->update($item['box_id'], $update);
        }
        $db->transComplete();

        (new LocationModel())->remember($b['bestemming']);
        session()->remove('move_batch');

        return $this->view('move_done', [
            'title'      => 'Verplaatst — Boxtracker',
            'bestemming' => $b['bestemming'],
            'items'      => array_values($b['items']),
        ]);
    }
}
