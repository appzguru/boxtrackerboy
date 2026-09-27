<?php

namespace App\Controllers\Bedrijf;

use App\Controllers\BaseController;
use App\Libraries\Mailer;
use App\Models\BedrijfModel;
use App\Models\MedewerkerUitnodigingModel;
use App\Models\UserModel;

/**
 * Planner beheert de medewerkers van zijn bedrijf (whitelabel-plan.md stap 4): uitnodigen,
 * rol kiezen, deactiveren. Een gedeactiveerde medewerker kan bij dit bedrijf nergens meer bij.
 */
class Medewerkers extends BaseController
{
    private int $bedrijfId;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->bedrijfId = (int) tenant()->bedrijfId();
    }

    protected function view(string $name, array $data = []): string
    {
        return parent::view($name, $data + ['beheer' => true, 'isPlanner' => true]);
    }

    /** Medewerker van dít bedrijf, of null. */
    private function medewerker(int $id): ?array
    {
        return db_connect()->table('bedrijf_medewerkers')
            ->where('id', $id)
            ->where('bedrijf_id', $this->bedrijfId)
            ->get()->getRowArray() ?: null;
    }

    private function aantalActievePlanners(): int
    {
        return db_connect()->table('bedrijf_medewerkers')
            ->where('bedrijf_id', $this->bedrijfId)
            ->where('rol', 'planner')
            ->where('actief', 1)
            ->countAllResults();
    }

    public function index()
    {
        return $this->view('bedrijf/medewerkers', [
            'title'         => 'Medewerkers — Boxtracker',
            'medewerkers'   => (new BedrijfModel())->medewerkers($this->bedrijfId),
            'uitnodigingen' => (new MedewerkerUitnodigingModel())->openFor($this->bedrijfId),
            'ik'            => (int) access()->user()['id'],
            'message'       => session()->getFlashdata('message'),
            'fout'          => session()->getFlashdata('fout'),
            'nieuweLink'    => session()->getFlashdata('nieuweLink'),
        ]);
    }

    public function invite()
    {
        $email = UserModel::normalizeEmail((string) $this->request->getPost('email'));
        $rol   = (string) $this->request->getPost('rol');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/bedrijf/medewerkers')->with('fout', 'Vul een geldig e-mailadres in.');
        }
        if (! in_array($rol, MedewerkerUitnodigingModel::ROLLEN, true)) {
            return redirect()->to('/bedrijf/medewerkers')->with('fout', 'Kies een rol.');
        }

        $invite = (new MedewerkerUitnodigingModel())->createFor($this->bedrijfId, $email, $rol, (int) access()->user()['id']);
        $link   = site_url('medewerker/' . $invite['token']);
        $naam   = tenant()->merk()['naam'];
        $gelukt = (new Mailer())->sendAction(
            $email,
            '',
            'Uitnodiging: ' . $naam,
            'Je bent uitgenodigd als ' . $rol . ' bij ' . $naam . '. Maak een account aan (of log in) om te beginnen.',
            $link,
            'Uitnodiging openen',
            'De link is ' . MedewerkerUitnodigingModel::DAGEN . ' dagen geldig en werkt alleen voor ' . $email . '.'
        );

        return redirect()->to('/bedrijf/medewerkers')
            ->with($gelukt ? 'message' : 'fout', $gelukt ? 'Uitnodiging verstuurd naar ' . $email . '.' : 'Mail versturen mislukt — stuur de link hieronder zelf door.')
            ->with('nieuweLink', $link);
    }

    public function setRol(int $id)
    {
        $m   = $this->medewerker($id);
        $rol = (string) $this->request->getPost('rol');
        if (! $m || ! in_array($rol, MedewerkerUitnodigingModel::ROLLEN, true)) {
            return redirect()->to('/bedrijf/medewerkers');
        }
        if ($m['rol'] === 'planner' && $rol !== 'planner' && $m['actief'] && $this->aantalActievePlanners() <= 1) {
            return redirect()->to('/bedrijf/medewerkers')->with('fout', 'Er moet minstens één planner overblijven.');
        }

        db_connect()->table('bedrijf_medewerkers')->where('id', $id)->update(['rol' => $rol]);
        // Toegewezen verhuizingen volgen de nieuwe rol: sjouwer ziet geen inhoud, inpakker wel.
        if (in_array($rol, ['inpakker', 'sjouwer'], true)) {
            $bedrijfId = $this->bedrijfId;
            db_connect()->table('memberships')
                ->where('user_id', $m['user_id'])
                ->whereIn('verhuizing_id', static fn ($b) => $b->select('id')->from('verhuizingen')->where('bedrijf_id', $bedrijfId))
                ->update(['rol' => $rol === 'sjouwer' ? 'sjouwer' : 'helper']);
        }

        return redirect()->to('/bedrijf/medewerkers')->with('message', 'Rol aangepast.');
    }

    public function setActief(int $id)
    {
        $m      = $this->medewerker($id);
        $actief = $this->request->getPost('actief') === '1' ? 1 : 0;
        if (! $m) {
            return redirect()->to('/bedrijf/medewerkers');
        }
        if (! $actief && (int) $m['user_id'] === (int) access()->user()['id']) {
            return redirect()->to('/bedrijf/medewerkers')->with('fout', 'Je kunt jezelf niet deactiveren.');
        }
        if (! $actief && $m['rol'] === 'planner' && $this->aantalActievePlanners() <= 1) {
            return redirect()->to('/bedrijf/medewerkers')->with('fout', 'Er moet minstens één planner overblijven.');
        }

        db_connect()->table('bedrijf_medewerkers')->where('id', $id)->update(['actief' => $actief]);

        return redirect()->to('/bedrijf/medewerkers')->with('message', $actief ? 'Medewerker is weer actief.' : 'Medewerker gedeactiveerd: kan nergens meer bij.');
    }

    public function revokeInvite(int $id)
    {
        db_connect()->table('medewerker_uitnodigingen')
            ->where('id', $id)
            ->where('bedrijf_id', $this->bedrijfId)
            ->where('used_at IS NULL')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/bedrijf/medewerkers');
    }
}
