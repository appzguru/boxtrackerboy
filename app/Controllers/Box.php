<?php

namespace App\Controllers;

use App\Models\BoxModel;
use App\Models\LocationModel;
use App\Models\MovementModel;
use App\Models\PhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Box extends BaseController
{
    private BoxModel $boxes;
    private MovementModel $movements;
    private PhotoModel $photos;
    private LocationModel $locations;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->boxes     = new BoxModel();
        $this->movements = new MovementModel();
        $this->photos    = new PhotoModel();
        $this->locations = new LocationModel();
    }

    /** Zoekt de doos op nummer en controleert het token. Maakt een lege doos aan bij een nieuw nummer. */
    private function loadBox(int $nummer, string $token): array|ResponseInterface
    {
        $box = $this->boxes->findByNummer($nummer);

        if ($box) {
            if (! hash_equals($box['token'], $token)) {
                return $this->unknown($token);
            }

            return $box;
        }

        $id = $this->boxes->insert([
            'nummer' => $nummer,
            'token'  => $token,
            'status' => 'leeg',
        ], true);

        return $this->boxes->find($id);
    }

    private function unknown(string $badCode = ''): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setBody(
            $this->view('errors/unknown', ['title' => 'Onbekende sticker — Boxtracker', 'badCode' => $badCode])
        );
    }

    public function show(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        if ($box['status'] === 'leeg') {
            return $this->renderForm($box, isNew: true);
        }

        if ($this->request->getGet('edit')) {
            return $this->renderForm($box, isNew: false);
        }

        if ($this->request->getGet('saved')) {
            return $this->view('box_saved', [
                'title' => '#' . box_nr($box['nummer']) . ' opgeslagen — Boxtracker',
                'nummer' => box_nr($box['nummer']),
                'sub'    => $box['huidige_locatie'] ? 'Staat nu in ' . $box['huidige_locatie'] . '.' : 'Klaar om verplaatst te worden.',
            ]);
        }

        $journey = array_map(function ($m) {
            return [
                'titel' => $m['van_locatie'] ? $m['naar_locatie'] : 'Ingepakt in ' . $m['naar_locatie'],
                'sub'   => ($m['van_locatie'] ? 'Verplaatst door ' : 'Door ') . $m['door'] . ' · ' . nl_datetime($m['op']),
            ];
        }, $this->movements->journeyFor((int) $box['id']));

        $removed = [];
        if (preg_match_all('/^\[(\d{2}-\d{2}-\d{4})] Eruit gehaald: (.+)$/m', (string) $box['omschrijving'], $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $removed[] = ['tekst' => $row[2], 'wanneer' => $row[1]];
            }
        }

        return $this->view('box_view', [
            'title'   => '#' . box_nr($box['nummer']) . ' — Boxtracker',
            'box'     => $box,
            'pill'    => status_pill($box['status']),
            'photos'  => $this->photos->forBox((int) $box['id']),
            'journey' => $journey,
            'removed' => $removed,
        ]);
    }

    private function renderForm(array $box, bool $isNew)
    {
        $session = session();

        return $this->view('box_form', [
            'title'          => $isNew ? 'Nieuwe doos #' . box_nr($box['nummer']) . ' — Boxtracker' : '#' . box_nr($box['nummer']) . ' bewerken — Boxtracker',
            'box'            => $box,
            'isNew'          => $isNew,
            'ownerChips'     => $this->boxes->ownerSuggestions(),
            'destChips'      => $this->locations->suggestions('nieuw huis'),
            'prefillOwner'   => $isNew ? (string) $session->get('last_owner') : (string) $box['eigenaar'],
            'prefillDest'    => $isNew ? (string) $session->get('last_dest') : (string) $box['einddoel'],
            'prefillPlace'   => $isNew ? (string) ($session->get('last_place') ?: '') : '',
        ]);
    }

    public function store(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        $omschrijving = trim((string) $this->request->getPost('omschrijving'));
        $eigenaar     = trim((string) $this->request->getPost('eigenaar'));
        $einddoel     = trim((string) $this->request->getPost('einddoel'));
        $fragiel      = (bool) $this->request->getPost('fragiel');
        $eerstOpenen  = (bool) $this->request->getPost('eerst_openen');
        $naam         = current_account_naam();

        if ($einddoel !== '') {
            $this->locations->remember($einddoel, 'nieuw huis');
        }

        if ($box['status'] === 'leeg') {
            $plek = trim((string) $this->request->getPost('huidige_locatie'));
            if ($plek !== '') {
                $this->locations->remember($plek, 'huis');
            }

            $this->boxes->update((int) $box['id'], [
                'omschrijving'    => $omschrijving,
                'eigenaar'        => $eigenaar,
                'einddoel'        => $einddoel,
                'huidige_locatie' => $plek,
                'status'          => 'ingepakt',
                'fragiel'         => $fragiel,
                'eerst_openen'    => $eerstOpenen,
                'ingepakt_door'   => $naam,
                'ingepakt_op'     => date('Y-m-d H:i:s'),
            ]);

            $this->movements->insert([
                'box_id'       => (int) $box['id'],
                'van_locatie'  => null,
                'naar_locatie' => $plek,
                'door'         => $naam,
                'op'           => date('Y-m-d H:i:s'),
                'batch_id'     => null,
            ]);

            $session = session();
            $session->set('last_owner', $eigenaar);
            $session->set('last_dest', $einddoel);
            $session->set('last_place', $plek);

            return redirect()->to('/d/' . $nummer . '-' . $token . '?saved=1');
        }

        $this->boxes->update((int) $box['id'], [
            'omschrijving' => $omschrijving,
            'eigenaar'     => $eigenaar,
            'einddoel'     => $einddoel,
            'fragiel'      => $fragiel,
            'eerst_openen' => $eerstOpenen,
        ]);

        return redirect()->to('/d/' . $nummer . '-' . $token);
    }

    public function photo(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Geen geldig bestand']);
        }

        $dir = WRITEPATH . 'uploads/boxes/' . $box['id'];
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = $file->getRandomName();
        $file->move($dir, $name);

        $id = $this->photos->insert([
            'box_id'       => (int) $box['id'],
            'bestandsnaam' => $name,
            'op'           => date('Y-m-d H:i:s'),
        ], true);

        return $this->response->setJSON(['ok' => true, 'id' => $id, 'url' => site_url('foto/' . $id)]);
    }

    public function status(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        $status = $this->request->getPost('status');
        $data   = [];

        if ($status === 'geopend') {
            $notitie = trim((string) $this->request->getPost('notitie'));
            $data['status'] = 'geopend';
            if ($notitie !== '') {
                $regel = '[' . date('d-m-Y') . '] Eruit gehaald: ' . $notitie;
                $data['omschrijving'] = trim($box['omschrijving'] . "\n\n" . $regel);
            }
        } elseif ($status === 'uitgepakt') {
            $data['status'] = 'uitgepakt';
        }

        if ($data) {
            $this->boxes->update((int) $box['id'], $data);
        }

        return redirect()->to('/d/' . $nummer . '-' . $token);
    }

    public function move(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        $naar = trim((string) $this->request->getPost('naar_locatie'));
        if ($naar === '') {
            return redirect()->to('/d/' . $nummer . '-' . $token);
        }

        $naam = current_account_naam();
        $this->movements->insert([
            'box_id'       => (int) $box['id'],
            'van_locatie'  => $box['huidige_locatie'],
            'naar_locatie' => $naar,
            'door'         => $naam,
            'op'           => date('Y-m-d H:i:s'),
            'batch_id'     => null,
        ]);

        $update = ['huidige_locatie' => $naar];
        if ($box['status'] === 'ingepakt') {
            $update['status'] = 'opgeslagen';
        }
        $this->boxes->update((int) $box['id'], $update);
        $this->locations->remember($naar);

        return redirect()->to('/d/' . $nummer . '-' . $token);
    }
}
