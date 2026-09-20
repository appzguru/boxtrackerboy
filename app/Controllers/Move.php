<?php

namespace App\Controllers;

use App\Models\BoxModel;
use App\Models\MovementModel;
use App\Models\LocationModel;

class Move extends BaseController
{
    public function start()
    {
        return $this->view('move_dest', [
            'title' => 'Dozen verplaatsen — Boxtracker',
            'recent' => (new MovementModel())->recentDestinations(),
        ]);
    }

    public function go()
    {
        $bestemming = trim((string) $this->request->getPost('bestemming'));
        if ($bestemming === '') {
            return redirect()->to('/verplaats');
        }

        $batch = bin2hex(random_bytes(16));
        session()->set('move_batch', [
            'id'         => $batch,
            'bestemming' => $bestemming,
            'items'      => [],
        ]);

        return redirect()->to('/verplaats/' . $batch . '/scan');
    }

    private function activeBatch(string $batch): ?array
    {
        $b = session()->get('move_batch');

        return ($b && $b['id'] === $batch) ? $b : null;
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

        $box = (new BoxModel())->findByNummer($nummer);
        if (! $box || ! hash_equals($box['token'], $token)) {
            return $this->response->setJSON(['ok' => false, 'reason' => 'onbekend']);
        }

        if (isset($b['items'][$nummer])) {
            return $this->response->setJSON([
                'ok' => true, 'dubbel' => true, 'count' => count($b['items']),
                'nummer' => box_nr($nummer), 'line' => first_line($box['omschrijving']),
            ]);
        }

        $b['items'][$nummer] = [
            'box_id' => (int) $box['id'],
            'nummer' => $nummer,
            'line'   => first_line($box['omschrijving']) ?: '(nog geen inhoud ingevuld)',
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
        $naam      = current_account_naam();
        $now       = date('Y-m-d H:i:s');

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

        (new LocationModel())->remember($b['bestemming']);
        session()->remove('move_batch');

        return $this->view('move_done', [
            'title'      => 'Verplaatst — Boxtracker',
            'bestemming' => $b['bestemming'],
            'items'      => array_values($b['items']),
        ]);
    }
}
