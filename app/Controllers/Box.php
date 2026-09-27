<?php

namespace App\Controllers;

use App\Models\BoxModel;
use App\Models\LocationModel;
use App\Models\MovementModel;
use App\Models\PhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Doospagina's onder /d/{nummer}-{token}. De route-filter eist alleen dat je ingelogd
 * bent (account of gast); de verhuizing volgt uit het token, en de rol wordt hier per
 * actie gecontroleerd (handoff.md §2, §3.4).
 */
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

    /**
     * Zoekt de doos op token (globaal uniek) en controleert het nummer. Hoort hij bij een
     * andere verhuizing waar je lid van bent, dan wordt die de actieve. Geen toegang,
     * onbekend token of verkeerd nummer: allemaal dezelfde 404, zodat niets af te tasten is.
     */
    private function loadBox(int $nummer, string $token): array|ResponseInterface
    {
        $loc    = BoxModel::locateToken($token);
        $access = access();

        if (! $loc || (int) $loc['nummer'] !== $nummer) {
            return $this->unknown($token);
        }

        $verhuizingId = (int) $loc['verhuizing_id'];
        if ($access->verhuizingId() !== $verhuizingId && ! $access->switchTo($verhuizingId)) {
            return $this->unknown($token);
        }

        return $this->boxes->find((int) $loc['id']) ?? $this->unknown($token);
    }

    private function unknown(string $badCode = ''): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setBody(
            $this->view('errors/unknown', ['title' => 'Onbekende sticker — Boxtracker', 'badCode' => $badCode])
        );
    }

    private function url(array $box, string $suffix = ''): string
    {
        return '/d/' . $box['nummer'] . '-' . $box['token'] . $suffix;
    }

    public function show(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        if (! access()->can('helper')) {
            return $this->view('box_sjouwer', [
                'title' => '#' . box_nr($box['nummer']) . ' — Boxtracker',
                'box'   => $box,
                'pill'  => status_pill($box['status']),
            ]);
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

        $photos = $this->photos->forBox((int) $box['id']);

        return $this->view('box_view', [
            'title'     => '#' . box_nr($box['nummer']) . ' — Boxtracker',
            'box'       => $box,
            'pill'      => status_pill($box['status']),
            'photos'    => $photos,
            'photosMax' => PhotoModel::MAX_PER_BOX,
            'journey'   => $journey,
            'removed'   => $removed,
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
        if (! access()->can('helper')) {
            return $this->forbidden();
        }

        $omschrijving = trim((string) $this->request->getPost('omschrijving'));
        $eigenaar     = trim((string) $this->request->getPost('eigenaar'));
        $einddoel     = trim((string) $this->request->getPost('einddoel'));
        $fragiel      = (bool) $this->request->getPost('fragiel');
        $eerstOpenen  = (bool) $this->request->getPost('eerst_openen');
        $naam         = access()->naam();

        if ($einddoel !== '') {
            $this->locations->remember($einddoel, 'nieuw huis');
        }

        if ($box['status'] === 'leeg') {
            $plek = trim((string) $this->request->getPost('huidige_locatie'));
            if ($plek !== '') {
                $this->locations->remember($plek, 'huis');
            }

            $db = db_connect();
            $db->transStart();
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
            $db->transComplete();

            $session = session();
            $session->set('last_owner', $eigenaar);
            $session->set('last_dest', $einddoel);
            $session->set('last_place', $plek);

            return redirect()->to($this->url($box, '?saved=1'));
        }

        $this->boxes->update((int) $box['id'], [
            'omschrijving' => $omschrijving,
            'eigenaar'     => $eigenaar,
            'einddoel'     => $einddoel,
            'fragiel'      => $fragiel,
            'eerst_openen' => $eerstOpenen,
        ]);

        return redirect()->to($this->url($box));
    }

    public function photo(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface || ! access()->can('helper')) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        if (count($this->photos->forBox((int) $box['id'])) >= PhotoModel::MAX_PER_BOX) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Maximaal ' . PhotoModel::MAX_PER_BOX . " foto's per doos"]);
        }

        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Geen geldig bestand']);
        }
        if ($file->getSize() > 1024 * 1024 || $file->getMimeType() !== 'image/jpeg') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Alleen JPEG, maximaal 1 MB']);
        }

        $dir = PhotoModel::dirFor($box);
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
        if (! access()->can('helper')) {
            return $this->forbidden();
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

        return redirect()->to($this->url($box));
    }

    /** Losse verplaatsing — mag ook een sjouwer. */
    public function move(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }

        $naar = trim((string) $this->request->getPost('naar_locatie'));
        if ($naar === '' || $box['status'] === 'leeg') {
            return redirect()->to($this->url($box));
        }

        $db = db_connect();
        $db->transStart();
        $this->movements->insert([
            'box_id'       => (int) $box['id'],
            'van_locatie'  => $box['huidige_locatie'],
            'naar_locatie' => $naar,
            'door'         => access()->naam(),
            'op'           => date('Y-m-d H:i:s'),
            'batch_id'     => null,
        ]);

        $update = ['huidige_locatie' => $naar];
        if ($box['status'] === 'ingepakt') {
            $update['status'] = 'opgeslagen';
        }
        $this->boxes->update((int) $box['id'], $update);
        $db->transComplete();
        $this->locations->remember($naar);

        return redirect()->to($this->url($box));
    }

    /** Verwijdert een doos definitief. Alleen admin, en alleen als hij al uitgepakt is. */
    public function delete(int $nummer, string $token)
    {
        $box = $this->loadBox($nummer, $token);
        if ($box instanceof ResponseInterface) {
            return $box;
        }
        if (! access()->can('admin')) {
            return $this->forbidden();
        }

        if ($box['status'] !== 'uitgepakt') {
            return redirect()->to($this->url($box));
        }

        $dir = PhotoModel::dirFor($box);
        if (is_dir($dir)) {
            delete_files($dir, true);
            @rmdir($dir);
        }

        $this->boxes->delete((int) $box['id']);

        return redirect()->to('/')->with('message', 'Doos #' . box_nr($nummer) . ' is verwijderd.');
    }
}
