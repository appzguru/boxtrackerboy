<?php

namespace App\Controllers\Beheer;

use App\Controllers\BaseController;
use App\Libraries\BeheerLog;
use App\Libraries\Mailer;
use App\Libraries\Tenant;
use App\Models\BedrijfModel;
use App\Models\MedewerkerUitnodigingModel;
use App\Models\UserModel;

/**
 * Global-admin (whitelabel-plan.md stap 2): bedrijven aanmaken, activeren/blokkeren en de
 * eerste planner uitnodigen. Een bedrijf aanmaken = het subdomein aanmaken; door het
 * wildcard-subdomein werkt het meteen. Route-filter `beheer` doet de toegangscontrole.
 */
class Bedrijven extends BaseController
{
    private BedrijfModel $bedrijven;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->bedrijven = new BedrijfModel();
    }

    public function index()
    {
        return $this->view('beheer/index', [
            'title'     => 'Beheer — Boxtracker',
            'bedrijven' => $this->bedrijven->overzicht(),
            'message'   => session()->getFlashdata('message'),
            'fout'      => session()->getFlashdata('fout'),
            'old'       => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function create()
    {
        $naam      = trim((string) $this->request->getPost('naam'));
        $subdomein = strtolower(trim((string) $this->request->getPost('subdomein')));

        if ($fout = $this->bedrijven->validateNew($naam, $subdomein)) {
            return redirect()->to('/beheer')->with('fout', $fout)->with('old', ['naam' => $naam, 'subdomein' => $subdomein]);
        }

        $id = (int) $this->bedrijven->insert(['naam' => $naam, 'subdomein' => $subdomein, 'status' => 'actief'], true);
        BeheerLog::schrijf('bedrijf_aangemaakt', $id, null, $naam . ' (' . $subdomein . ')');

        return redirect()->to('/beheer/bedrijven/' . $id)->with('message', 'Bedrijf aangemaakt. Nodig nu de eerste planner uit.');
    }

    public function show(int $id)
    {
        $bedrijf = $this->bedrijven->find($id);
        if (! $bedrijf) {
            return redirect()->to('/beheer');
        }

        return $this->view('beheer/bedrijf', [
            'title'        => $bedrijf['naam'] . ' — Beheer',
            'bedrijf'      => $bedrijf,
            'url'          => Tenant::urlVoor($bedrijf['subdomein']),
            'medewerkers'  => $this->bedrijven->medewerkers($id),
            'uitnodigingen' => (new MedewerkerUitnodigingModel())->openFor($id),
            'verhuizingen' => $this->bedrijven->verhuizingen($id),
            'log'          => BeheerLog::voorBedrijf($id),
            'message'      => session()->getFlashdata('message'),
            'fout'         => session()->getFlashdata('fout'),
            'nieuweLink'   => session()->getFlashdata('nieuweLink'),
        ]);
    }

    public function setStatus(int $id)
    {
        $bedrijf = $this->bedrijven->find($id);
        $status  = $this->request->getPost('status') === 'geblokkeerd' ? 'geblokkeerd' : 'actief';
        if ($bedrijf && $bedrijf['status'] !== $status) {
            $this->bedrijven->update($id, ['status' => $status]);
            BeheerLog::schrijf($status === 'actief' ? 'bedrijf_geactiveerd' : 'bedrijf_geblokkeerd', $id);
        }

        return redirect()->to('/beheer/bedrijven/' . $id);
    }

    /** Medewerker uitnodigen per mail (voor de eerste planner; daarna doet de planner dat zelf). */
    public function invite(int $id)
    {
        $bedrijf = $this->bedrijven->find($id);
        if (! $bedrijf) {
            return redirect()->to('/beheer');
        }

        $email = UserModel::normalizeEmail((string) $this->request->getPost('email'));
        $rol   = in_array($this->request->getPost('rol'), ['planner', 'sales'], true) ? $this->request->getPost('rol') : 'planner';
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/beheer/bedrijven/' . $id)->with('fout', 'Vul een geldig e-mailadres in.');
        }

        $invite = (new MedewerkerUitnodigingModel())->createFor($id, $email, $rol, access()->user()['id']);
        $link   = Tenant::urlVoor($bedrijf['subdomein'], 'medewerker/' . $invite['token']);

        $verstuurd = (new Mailer())->sendAction(
            $email,
            '',
            'Uitnodiging: ' . $bedrijf['naam'] . ' op Boxtracker',
            'Je bent uitgenodigd als ' . $rol . ' bij ' . $bedrijf['naam'] . '. Maak een account aan (of log in) om te beginnen.',
            $link,
            'Uitnodiging openen',
            'De link is ' . MedewerkerUitnodigingModel::DAGEN . ' dagen geldig en werkt alleen voor ' . $email . '.'
        );
        BeheerLog::schrijf('medewerker_uitgenodigd', $id, null, $rol . ' ' . $email);

        return redirect()->to('/beheer/bedrijven/' . $id)
            ->with($verstuurd ? 'message' : 'fout', $verstuurd ? 'Uitnodiging verstuurd naar ' . $email . '.' : 'Mail versturen mislukt — stuur de link hieronder zelf door.')
            ->with('nieuweLink', $link);
    }

    public function revokeInvite(int $id)
    {
        $model  = new MedewerkerUitnodigingModel();
        $invite = $model->find($id);
        if ($invite && ! $invite['used_at']) {
            $model->update($id, ['revoked_at' => date('Y-m-d H:i:s')]);
            BeheerLog::schrijf('uitnodiging_ingetrokken', (int) $invite['bedrijf_id'], null, $invite['email']);
        }

        return redirect()->to('/beheer/bedrijven/' . ($invite['bedrijf_id'] ?? ''));
    }
}
