<?php

namespace App\Controllers\Bedrijf;

use App\Controllers\BaseController;
use App\Libraries\Mailer;
use App\Models\BedrijfModel;
use App\Models\InviteModel;
use App\Models\OpnameFotoModel;
use App\Models\OpnameItemModel;
use App\Models\UserModel;
use App\Models\VerhuizingModel;

/**
 * Planner en sales (whitelabel-plan.md stap 4): planningsoverzicht, verhuizing aanmaken en
 * bewerken, ploeg toewijzen en de bewoner uitnodigen. Alles alleen binnen het bedrijf van
 * deze ingang; route-filter `bedrijf` controleert de rol.
 */
class Planning extends BaseController
{
    private BedrijfModel $bedrijven;
    private int $bedrijfId;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->bedrijven = new BedrijfModel();
        $this->bedrijfId = (int) tenant()->bedrijfId();
    }

    /** Kantoorschermen zijn bureaubladschermen: brede indeling, geen verhuizing-balk. */
    protected function view(string $name, array $data = []): string
    {
        return parent::view($name, $data + ['beheer' => true, 'isPlanner' => access()->medewerker()['rol'] === 'planner']);
    }

    private function verhuizingOf404(int $id): array
    {
        $v = $this->bedrijven->verhuizing($this->bedrijfId, $id);
        if (! $v) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $v;
    }

    public function index()
    {
        return $this->view('bedrijf/planning', [
            'title'        => 'Planning — Boxtracker',
            'verhuizingen' => $this->bedrijven->planning($this->bedrijfId),
            'magNieuw'     => tenant()->magNieuweVerhuizingen(),
            'message'      => session()->getFlashdata('message'),
        ]);
    }

    public function nieuw()
    {
        if (! tenant()->magNieuweVerhuizingen()) {
            return redirect()->to('/bedrijf')->with('message', 'Nieuwe verhuizingen aanmaken kan nu niet. Neem contact op met Boxtracker.');
        }

        return $this->view('bedrijf/verhuizing_form', [
            'title'      => 'Nieuwe verhuizing — Boxtracker',
            'verhuizing' => null,
            'old'        => [],
        ]);
    }

    /** Foutmelding of null; vult $data met opgeschoonde velden. */
    private function valideer(?array &$data): ?string
    {
        $data = [
            'naam'         => trim((string) $this->request->getPost('naam')),
            'verhuisdatum' => trim((string) $this->request->getPost('verhuisdatum')) ?: null,
            'adres_van'    => trim((string) $this->request->getPost('adres_van')) ?: null,
            'adres_naar'   => trim((string) $this->request->getPost('adres_naar')) ?: null,
        ];
        if ($data['naam'] === '' || mb_strlen($data['naam']) > 80) {
            return 'Vul de naam van de klant in (max. 80 tekens).';
        }
        if ($data['verhuisdatum'] !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['verhuisdatum'])) {
            return 'De verhuisdatum klopt niet.';
        }
        foreach (['adres_van', 'adres_naar'] as $veld) {
            if ($data[$veld] !== null && mb_strlen($data[$veld]) > 160) {
                return 'Een adres is te lang (max. 160 tekens).';
            }
        }

        return null;
    }

    public function create()
    {
        if (! tenant()->magNieuweVerhuizingen()) {
            return redirect()->to('/bedrijf')->with('message', 'Nieuwe verhuizingen aanmaken kan nu niet. Neem contact op met Boxtracker.');
        }

        if ($fout = $this->valideer($data)) {
            return $this->view('bedrijf/verhuizing_form', ['title' => 'Nieuwe verhuizing — Boxtracker', 'verhuizing' => null, 'old' => $data, 'fout' => $fout]);
        }

        $id = (int) (new VerhuizingModel())->insert($data + ['bedrijf_id' => $this->bedrijfId, 'created_by' => access()->user()['id']], true);

        return redirect()->to('/bedrijf/verhuizingen/' . $id)->with('message', 'Verhuizing aangemaakt. Wijs nu de ploeg toe en nodig de bewoner uit.');
    }

    public function show(int $id)
    {
        $v = $this->verhuizingOf404($id);

        return $this->view('bedrijf/verhuizing', [
            'title'      => $v['naam'] . ' — Boxtracker',
            'verhuizing' => $v,
            'leden'      => $this->bedrijven->leden($this->bedrijfId, $id),
            'kandidaten' => $this->bedrijven->ploegKandidaten($this->bedrijfId),
            'invites'    => (new InviteModel())->openFor($id),
            'message'    => session()->getFlashdata('message'),
            'fout'       => session()->getFlashdata('fout'),
            'nieuweLink' => session()->getFlashdata('nieuweLink'),
        ]);
    }

    public function edit(int $id)
    {
        $v = $this->verhuizingOf404($id);

        return $this->view('bedrijf/verhuizing_form', ['title' => $v['naam'] . ' — Boxtracker', 'verhuizing' => $v, 'old' => $v]);
    }

    public function update(int $id)
    {
        $v = $this->verhuizingOf404($id);
        if ($fout = $this->valideer($data)) {
            return $this->view('bedrijf/verhuizing_form', ['title' => $v['naam'] . ' — Boxtracker', 'verhuizing' => $v, 'old' => $data, 'fout' => $fout]);
        }

        (new VerhuizingModel())->update($id, $data);

        return redirect()->to('/bedrijf/verhuizingen/' . $id)->with('message', 'Opgeslagen.');
    }

    public function ploeg(int $id)
    {
        $this->verhuizingOf404($id);
        $this->bedrijven->zetPloeg($this->bedrijfId, $id, (array) $this->request->getPost('ploeg'));

        return redirect()->to('/bedrijf/verhuizingen/' . $id)->with('message', 'Ploeg opgeslagen.');
    }

    /** Bewoner uitnodigen met de gewone uitnodigingslink; met e-mailadres ook per mail. */
    public function bewoner(int $id)
    {
        $v     = $this->verhuizingOf404($id);
        $rol   = $this->request->getPost('rol') === 'helper' ? 'helper' : 'admin';
        $email = UserModel::normalizeEmail((string) $this->request->getPost('email'));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/bedrijf/verhuizingen/' . $id)->with('fout', 'Dat e-mailadres klopt niet.');
        }

        $invite = (new InviteModel())->createFor($id, $rol, (int) access()->user()['id']);
        $link   = site_url('uitnodiging/' . $invite['token']);
        $melding = 'Uitnodigingslink klaar. Stuur hem door naar de bewoner.';
        if ($email !== '') {
            $naam    = tenant()->merk()['naam'];
            $gelukt  = (new Mailer())->sendAction(
                $email,
                '',
                'Je verhuizing met ' . $naam,
                $naam . ' gebruikt Boxtracker voor je verhuizing (' . $v['naam'] . '). Hiermee houd je bij wat er in elke doos zit en waar hij heen moet.',
                $link,
                'Meedoen',
                'De link is ' . InviteModel::DAGEN . ' dagen geldig en werkt één keer.'
            );
            $melding = $gelukt ? 'Uitnodiging verstuurd naar ' . $email . '.' : 'Mail versturen mislukt — stuur de link hieronder zelf door.';
        }

        return redirect()->to('/bedrijf/verhuizingen/' . $id)->with('message', $melding)->with('nieuweLink', $link);
    }

    /**
     * Opname voor sales (stap 5): alle foto's van de verhuizing op één scherm, items als risico
     * markeren. Maakt de verhuizing actief, zodat foto's via de gewone gescopede route komen.
     */
    public function opname(int $id)
    {
        $v = $this->verhuizingOf404($id);
        access()->switchTo($id);

        return $this->view('bedrijf/opname_kantoor', [
            'title'      => 'Opname ' . $v['naam'] . ' — Boxtracker',
            'verhuizing' => $v,
            'opname'     => (new OpnameItemModel())->overzicht(),
            'fotos'      => (new OpnameFotoModel())->perItem(),
            'message'    => session()->getFlashdata('message'),
        ]);
    }

    public function risico(int $id, int $itemId)
    {
        $this->verhuizingOf404($id);
        access()->switchTo($id);

        $items   = new OpnameItemModel();
        $risico  = $this->request->getPost('risico') === '1' ? 1 : 0;
        $notitie = mb_substr(trim((string) $this->request->getPost('risico_notitie')), 0, 200);
        if ($items->find($itemId)) {
            $items->update($itemId, ['risico' => $risico, 'risico_notitie' => $risico && $notitie !== '' ? $notitie : null]);
        }

        return redirect()->to('/bedrijf/verhuizingen/' . $id . '/opname#item-' . $itemId)->with('message', 'Opgeslagen.');
    }
}
