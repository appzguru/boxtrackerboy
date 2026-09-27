<?php

namespace App\Controllers\Bedrijf;

use App\Controllers\BaseController;
use App\Models\OpnameFotoModel;
use App\Models\OpnameItemModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Foto-opname vooraf (whitelabel-plan.md stap 5), voor de bewoner en inpakkers (rol helper of
 * hoger; sjouwers niet). Vaste punten om af te vinken plus losse items met foto's. Alleen bij
 * een verhuizing van een bedrijf; alles gescoped op de actieve verhuizing.
 */
class Opname extends BaseController
{
    private OpnameItemModel $items;
    private OpnameFotoModel $fotos;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->items = new OpnameItemModel();
        $this->fotos = new OpnameFotoModel();
    }

    /** Opname hoort bij de betaalde versie: alleen verhuizingen van een bedrijf. */
    private function isBedrijfsverhuizing(): bool
    {
        return db_connect()->table('verhuizingen')
            ->where('id', access()->verhuizingId())
            ->where('bedrijf_id IS NOT NULL')
            ->countAllResults() > 0;
    }

    private function itemOf404(int $id): array
    {
        $item = $this->items->where('soort', 'item')->find($id);
        if (! $item) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $item;
    }

    public function index()
    {
        if (! $this->isBedrijfsverhuizing()) {
            return redirect()->to('/');
        }

        return $this->view('bedrijf/opname', [
            'title'     => 'Opname — Boxtracker',
            'opname'    => $this->items->overzicht(),
            'fotos'     => $this->fotos->perItem(),
            'maxFotos'  => OpnameFotoModel::MAX_PER_ITEM,
            'message'   => session()->getFlashdata('message'),
        ]);
    }

    /** Foutmelding of null. */
    private function itemVelden(?array &$data): ?string
    {
        $data = [
            'omschrijving' => trim((string) $this->request->getPost('omschrijving')),
            'kamer'        => trim((string) $this->request->getPost('kamer')) ?: null,
        ];
        if ($data['omschrijving'] === '' || mb_strlen($data['omschrijving']) > 200) {
            return 'Beschrijf het item (max. 200 tekens).';
        }
        if ($data['kamer'] !== null && mb_strlen($data['kamer']) > 80) {
            return 'De kamer is te lang (max. 80 tekens).';
        }

        return null;
    }

    public function createItem()
    {
        if (! $this->isBedrijfsverhuizing()) {
            return redirect()->to('/');
        }
        if ($fout = $this->itemVelden($data)) {
            return redirect()->to('/opname')->with('message', $fout);
        }

        $id = $this->items->insert($data + ['soort' => 'item', 'created_by' => access()->naam(), 'created_at' => date('Y-m-d H:i:s')], true);

        return redirect()->to('/opname/items/' . $id);
    }

    public function item(int $id)
    {
        $item = $this->itemOf404($id);

        return $this->view('bedrijf/opname_item', [
            'title'    => $item['omschrijving'] . ' — Boxtracker',
            'item'     => $item,
            'fotos'    => $this->fotos->forItem($id),
            'maxFotos' => OpnameFotoModel::MAX_PER_ITEM,
            'message'  => session()->getFlashdata('message'),
        ]);
    }

    public function updateItem(int $id)
    {
        $this->itemOf404($id);
        if ($fout = $this->itemVelden($data)) {
            return redirect()->to('/opname/items/' . $id)->with('message', $fout);
        }
        $this->items->update($id, $data);

        return redirect()->to('/opname')->with('message', 'Opgeslagen.');
    }

    public function deleteItem(int $id)
    {
        $item = $this->itemOf404($id);
        $this->verwijderMap($item);
        $this->items->delete($id);

        return redirect()->to('/opname')->with('message', 'Verwijderd.');
    }

    public function fotoPunt(string $punt)
    {
        if (! isset(OpnameItemModel::PUNTEN[$punt]) || ! $this->isBedrijfsverhuizing()) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        return $this->upload($this->items->voorPunt($punt, access()->naam()));
    }

    public function fotoItem(int $id)
    {
        $item = $this->items->where('soort', 'item')->find($id);
        if (! $item) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        return $this->upload($item);
    }

    /** Zelfde regels als foto's bij dozen: client-side verkleind, alleen JPEG, max 1 MB. */
    private function upload(array $item): ResponseInterface
    {
        if (count($this->fotos->forItem((int) $item['id'])) >= OpnameFotoModel::MAX_PER_ITEM) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Maximaal ' . OpnameFotoModel::MAX_PER_ITEM . " foto's"]);
        }

        $file = $this->request->getFile('foto');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Geen geldig bestand']);
        }
        if ($file->getSize() > 1024 * 1024 || $file->getMimeType() !== 'image/jpeg') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Alleen JPEG, maximaal 1 MB']);
        }

        $dir = OpnameFotoModel::dirFor($item);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);

        $id = $this->fotos->insert(['item_id' => (int) $item['id'], 'bestandsnaam' => $name, 'op' => date('Y-m-d H:i:s')], true);

        return $this->response->setJSON(['ok' => true, 'id' => $id, 'url' => site_url('opname/foto/' . $id)]);
    }

    /** Foto uitserveren — gescoped op de actieve verhuizing. */
    public function foto(int $id)
    {
        $foto = $this->fotos->find($id);
        $item = $foto ? $this->items->find((int) $foto['item_id']) : null;
        $path = $item ? OpnameFotoModel::dirFor($item) . '/' . basename($foto['bestandsnaam']) : null;
        if (! $path || ! is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setHeader('Content-Type', (new File($path))->getMimeType())
            ->setHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->setBody(file_get_contents($path));
    }

    public function deleteFoto(int $id)
    {
        $foto = $this->fotos->find($id);
        $item = $foto ? $this->items->find((int) $foto['item_id']) : null;
        if ($item) {
            @unlink(OpnameFotoModel::dirFor($item) . '/' . basename($foto['bestandsnaam']));
            $this->fotos->delete($id);
        }

        return redirect()->to($item && $item['soort'] === 'item' ? '/opname/items/' . $item['id'] : '/opname');
    }

    private function verwijderMap(array $item): void
    {
        $dir = OpnameFotoModel::dirFor($item);
        if (is_dir($dir)) {
            delete_files($dir, true);
            @rmdir($dir);
        }
    }
}
