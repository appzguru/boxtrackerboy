<?php

use App\Libraries\Access;
use App\Libraries\Tenant;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Boxtracker;
use Config\Services;
use Tests\Support\VerseDatabase;

/**
 * Planner en medewerkers (whitelabel-plan.md stap 4), via de echte routes: alleen planner en
 * sales op het kantoor, alleen het eigen bedrijf, softblock remt nieuwe verhuizingen, ploeg
 * toewijzen raakt bewoners niet, en de bewoner kan een bedrijfsverhuizing niet verwijderen.
 *
 * @internal
 */
final class PlanningTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use VerseDatabase;

    private const HOST_KLANT = 'app.boxtracker.nl';
    private const HOST_A     = 'verhuizer-a.boxtracker.nl';

    private const COOKIE = ['plana' => 'q', 'sala' => 's', 'inka' => 'i', 'sjoa' => 'j', 'planb' => 'r', 'bewa' => 'w'];

    private array $bedrijf = [];
    private array $vh      = [];
    private array $user    = [];
    private array $mw      = [];

    /** @var list<string> */
    private array $mailVoor = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper('access');
        $this->laadSchema();
        $cfg               = config(Boxtracker::class);
        $cfg->tenantDomein = 'boxtracker.nl';
        $cfg->omgeving     = '';
        $cfg->devBedrijf   = '';

        $filters                    = config('Filters');
        $filters->globals['before'] = array_values(array_diff($filters->globals['before'], ['csrf']));

        $db = db_connect();
        foreach (['a' => 'verhuizer-a', 'b' => 'verhuizer-b'] as $k => $sub) {
            $db->table('bedrijven')->insert(['naam' => 'Bedrijf ' . strtoupper($k), 'subdomein' => $sub]);
            $this->bedrijf[$k] = (int) $db->insertID();
        }
        foreach (['A1' => 'a', 'B1' => 'b'] as $naam => $b) {
            $db->table('verhuizingen')->insert(['naam' => 'Verhuizing ' . $naam, 'bedrijf_id' => $this->bedrijf[$b], 'verhuisdatum' => date('Y-m-d', strtotime('+10 days'))]);
            $this->vh[$naam] = (int) $db->insertID();
            $db->table('boxes')->insert(['verhuizing_id' => $this->vh[$naam], 'nummer' => 1, 'token' => 'tok' . strtolower($naam), 'status' => 'ingepakt', 'fragiel' => 1, 'omschrijving' => 'geheim ' . $naam]);
        }
        foreach (self::COOKIE as $naam => $letter) {
            $db->table('users')->insert(['naam' => ucfirst($naam), 'email' => $naam . '@test.nl', 'password_hash' => 'x']);
            $this->user[$naam] = (int) $db->insertID();
            $db->table('sessions')->insert(['user_id' => $this->user[$naam], 'token' => str_repeat($letter, 64)]);
        }
        foreach ([['plana', 'a', 'planner'], ['sala', 'a', 'sales'], ['inka', 'a', 'inpakker'], ['sjoa', 'a', 'sjouwer'], ['planb', 'b', 'planner']] as [$naam, $b, $rol]) {
            $db->table('bedrijf_medewerkers')->insert(['bedrijf_id' => $this->bedrijf[$b], 'user_id' => $this->user[$naam], 'rol' => $rol]);
            $this->mw[$naam] = (int) $db->insertID();
        }
        $db->table('memberships')->insert(['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['bewa'], 'rol' => 'admin']);

        $this->mailVoor = glob(WRITEPATH . 'mail/*.txt') ?: [];
    }

    protected function tearDown(): void
    {
        foreach (array_diff(glob(WRITEPATH . 'mail/*.txt') ?: [], $this->mailVoor) as $file) {
            unlink($file);
        }
        Services::resetSingle('access');
        Services::resetSingle('tenant');
        parent::tearDown();
    }

    private function als(?string $wie, string $host = self::HOST_A): Access
    {
        $tenant  = new Tenant($host, clone config(Boxtracker::class));
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('cookie', $wie ? [Access::USER_COOKIE => str_repeat(self::COOKIE[$wie], 64)] : []);
        $access = new Access($request, $tenant);
        Services::injectMock('tenant', $tenant);
        Services::injectMock('access', $access);

        return $access;
    }

    private function statusVan(string $method, string $path, array $params = []): int
    {
        try {
            return $this->call($method, $path, $params)->response()->getStatusCode();
        } catch (PageNotFoundException) {
            return 404;
        }
    }

    private function aantal(string $tabel, array $where = []): int
    {
        return db_connect()->table($tabel)->where($where)->countAllResults();
    }

    public function testKantoorAlleenVoorPlannerEnSales(): void
    {
        $this->als('inka');
        $this->assertSame(403, $this->statusVan('GET', '/bedrijf'));
        $this->als('sjoa');
        $this->assertSame(403, $this->statusVan('GET', '/bedrijf'));
        $this->als('bewa');
        $this->assertSame(403, $this->statusVan('GET', '/bedrijf'));
        $this->als('plana', self::HOST_KLANT);
        $this->assertSame(404, $this->statusVan('GET', '/bedrijf'), 'niet in de klant-app');
        $this->als(null);
        $this->assertStringContainsString('/login?next=', $this->get('/bedrijf')->getRedirectUrl());

        $this->als('sala');
        $response = $this->get('/bedrijf');
        $response->assertOK();
        $response->assertSee('Verhuizing A1');
        $response->assertDontSee('Verhuizing B1');
        $response->assertDontSee('Nieuwe verhuizing'); // alleen de planner maakt aan
        $this->als('sala');
        $this->assertSame(403, $this->statusVan('GET', '/bedrijf/medewerkers'));
    }

    public function testPlannerMaaktVerhuizingAan(): void
    {
        $this->als('plana');
        $this->get('/bedrijf')->assertSee('Nieuwe verhuizing');
        $this->als('plana');
        $this->post('/bedrijf/verhuizingen', ['naam' => 'Fam. Jansen', 'verhuisdatum' => '2026-11-03', 'adres_van' => 'Dorpsstraat 1, Zwolle', 'adres_naar' => 'Kerkweg 2, Kampen']);
        $row = db_connect()->table('verhuizingen')->where('naam', 'Fam. Jansen')->get()->getRowArray();
        $this->assertSame((string) $this->bedrijf['a'], (string) $row['bedrijf_id']);
        $this->assertSame('2026-11-03', $row['verhuisdatum']);
        $this->assertSame('Kerkweg 2, Kampen', $row['adres_naar']);
        $this->assertSame(0, $this->aantal('memberships', ['verhuizing_id' => $row['id']]));

        $this->als('sala');
        $this->assertSame(403, $this->statusVan('POST', '/bedrijf/verhuizingen', ['naam' => 'Door sales']));
        $this->assertSame(0, $this->aantal('verhuizingen', ['naam' => 'Door sales']));

        $this->als('plana');
        $this->get('/bedrijf')->assertSee('Fam. Jansen');
    }

    public function testSoftblockGeenNieuweVerhuizing(): void
    {
        db_connect()->table('bedrijven')->where('id', $this->bedrijf['a'])->update(['status' => 'softblock']);

        $this->als('plana');
        $response = $this->get('/bedrijf');
        $response->assertSee('Verhuizing A1');
        $response->assertDontSee('Nieuwe verhuizing');
        $this->als('plana');
        $this->post('/bedrijf/verhuizingen', ['naam' => 'Toch nieuw']);
        $this->assertSame(0, $this->aantal('verhuizingen', ['naam' => 'Toch nieuw']));

        // Bestaande verhuizing bewerken kan gewoon.
        $this->als('plana');
        $this->post('/bedrijf/verhuizingen/' . $this->vh['A1'], ['naam' => 'A1 bijgewerkt']);
        $this->assertSame(1, $this->aantal('verhuizingen', ['naam' => 'A1 bijgewerkt']));
    }

    public function testAndermansVerhuizingBestaatNiet(): void
    {
        $this->als('plana');
        $this->assertSame(404, $this->statusVan('GET', '/bedrijf/verhuizingen/' . $this->vh['B1']));
        $this->als('plana');
        $this->assertSame(404, $this->statusVan('POST', '/bedrijf/verhuizingen/' . $this->vh['B1'], ['naam' => 'Gekaapt']));
        $this->als('plana');
        $this->assertSame(404, $this->statusVan('POST', '/bedrijf/verhuizingen/' . $this->vh['B1'] . '/ploeg', ['ploeg' => [$this->user['inka']]]));
        $this->assertSame('Verhuizing B1', db_connect()->table('verhuizingen')->where('id', $this->vh['B1'])->get()->getRow()->naam);
        $this->assertSame(0, $this->aantal('memberships', ['verhuizing_id' => $this->vh['B1']]));
    }

    public function testPloegToewijzen(): void
    {
        $url = '/bedrijf/verhuizingen/' . $this->vh['A1'] . '/ploeg';

        $this->als('plana');
        $this->post($url, ['ploeg' => [$this->user['inka'], $this->user['sjoa'], $this->user['planb'], $this->user['bewa']]]);
        $rollen = array_column(db_connect()->table('memberships')->where('verhuizing_id', $this->vh['A1'])->get()->getResultArray(), 'rol', 'user_id');
        $this->assertSame('helper', $rollen[$this->user['inka']]);
        $this->assertSame('sjouwer', $rollen[$this->user['sjoa']]);
        $this->assertArrayNotHasKey($this->user['planb'], $rollen, 'planner van B is geen kandidaat');
        $this->assertSame('admin', $rollen[$this->user['bewa']], 'bewoner ongemoeid');

        $sjouwer = $this->als('sjoa');
        $this->assertTrue($sjouwer->switchTo($this->vh['A1']));
        $this->assertFalse($sjouwer->can('helper'), 'sjouwer ziet geen inhoud');

        $this->als('plana');
        $this->post($url, ['ploeg' => [$this->user['inka']]]);
        $this->assertSame(0, $this->aantal('memberships', ['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['sjoa']]));
        $this->assertSame(1, $this->aantal('memberships', ['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['bewa']]));
        $this->assertFalse($this->als('sjoa')->switchTo($this->vh['A1']));

        $this->als('plana');
        $response = $this->get('/bedrijf');
        $response->assertSee('Inka');
    }

    public function testMedewerkersBeheren(): void
    {
        $this->als('plana');
        $this->post('/bedrijf/medewerkers/uitnodigen', ['email' => 'nieuw@test.nl', 'rol' => 'sjouwer']);
        $invite = db_connect()->table('medewerker_uitnodigingen')->get()->getRowArray();
        $this->assertSame((string) $this->bedrijf['a'], (string) $invite['bedrijf_id']);
        $this->assertSame('sjouwer', $invite['rol']);

        // Rol van inpakker naar sjouwer: toegewezen verhuizingen volgen.
        db_connect()->table('memberships')->insert(['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['inka'], 'rol' => 'helper']);
        $this->als('plana');
        $this->post('/bedrijf/medewerkers/' . $this->mw['inka'] . '/rol', ['rol' => 'sjouwer']);
        $this->assertSame('sjouwer', db_connect()->table('memberships')->where('user_id', $this->user['inka'])->get()->getRow()->rol);

        $this->als('plana');
        $this->post('/bedrijf/medewerkers/' . $this->mw['inka'] . '/actief', ['actief' => '0']);
        $this->assertSame(0, $this->aantal('bedrijf_medewerkers', ['id' => $this->mw['inka'], 'actief' => 1]));
        $this->assertFalse($this->als('inka')->switchTo($this->vh['A1']));

        // Jezelf niet, de laatste planner niet, en niet bij een ander bedrijf.
        $this->als('plana');
        $this->post('/bedrijf/medewerkers/' . $this->mw['plana'] . '/actief', ['actief' => '0']);
        $this->als('plana');
        $this->post('/bedrijf/medewerkers/' . $this->mw['plana'] . '/rol', ['rol' => 'sales']);
        $this->als('plana');
        $this->post('/bedrijf/medewerkers/' . $this->mw['planb'] . '/actief', ['actief' => '0']);
        $this->assertSame(1, $this->aantal('bedrijf_medewerkers', ['id' => $this->mw['plana'], 'actief' => 1, 'rol' => 'planner']));
        $this->assertSame(1, $this->aantal('bedrijf_medewerkers', ['id' => $this->mw['planb'], 'actief' => 1]));
    }

    public function testBewonerUitnodigen(): void
    {
        $this->als('plana');
        $this->post('/bedrijf/verhuizingen/' . $this->vh['A1'] . '/bewoner', ['email' => 'klant@test.nl', 'rol' => 'admin']);
        $invite = db_connect()->table('invites')->where('verhuizing_id', $this->vh['A1'])->get()->getRowArray();
        $this->assertSame('admin', $invite['rol']);
        $mail = array_values(array_diff(glob(WRITEPATH . 'mail/*.txt') ?: [], $this->mailVoor));
        $this->assertCount(1, $mail);
        $this->assertStringContainsString('/uitnodiging/' . $invite['token'], file_get_contents($mail[0]));
    }

    public function testBewonerVerwijdertBedrijfsverhuizingNiet(): void
    {
        $bewoner = $this->als('bewa');
        $this->assertTrue($bewoner->switchTo($this->vh['A1']));
        $this->als('bewa');
        $this->post('/verhuizing/verwijderen', ['bevestig' => 'Verhuizing A1']);
        $this->assertSame(1, $this->aantal('verhuizingen', ['id' => $this->vh['A1']]));
    }

    public function testPlannerZonderActieveVerhuizingNaarPlanning(): void
    {
        // Twee verhuizingen, dus geen automatische keuze.
        db_connect()->table('verhuizingen')->insert(['naam' => 'Verhuizing A2', 'bedrijf_id' => $this->bedrijf['a']]);
        $this->als('plana');
        $this->assertStringEndsWith('/bedrijf', $this->get('/')->getRedirectUrl());
        $this->als('inka');
        $this->assertStringEndsWith('/verhuizingen', $this->get('/')->getRedirectUrl());
    }

    private function opnameItem(string $vh, string $omschrijving): int
    {
        db_connect()->table('opname_items')->insert(['verhuizing_id' => $this->vh[$vh], 'soort' => 'item', 'omschrijving' => $omschrijving]);

        return (int) db_connect()->insertID();
    }

    public function testOpnameVoorBewonerEnInpakkerNietVoorSjouwer(): void
    {
        db_connect()->table('memberships')->insert(['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['inka'], 'rol' => 'helper']);
        db_connect()->table('memberships')->insert(['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['sjoa'], 'rol' => 'sjouwer']);

        $this->als('sjoa');
        $this->assertSame(403, $this->statusVan('GET', '/opname'));
        $this->als('sjoa');
        $this->assertSame(403, $this->statusVan('POST', '/opname/items', ['omschrijving' => 'Door sjouwer']));

        $this->als('inka');
        $response = $this->get('/opname');
        $response->assertOK();
        $response->assertSee('Trappenhuis');
        $this->als('inka');
        $this->post('/opname/items', ['omschrijving' => 'Hoekbank', 'kamer' => 'Woonkamer']);
        $row = db_connect()->table('opname_items')->where('omschrijving', 'Hoekbank')->get()->getRowArray();
        $this->assertSame((string) $this->vh['A1'], (string) $row['verhuizing_id']);
        $this->assertSame('item', $row['soort']);

        $this->als('bewa');
        $this->get('/opname')->assertSee('Hoekbank');

        // Onbekend punt en upload zonder bestand worden geweigerd.
        $this->als('inka');
        $this->assertSame(404, $this->statusVan('POST', '/opname/punt/zolder/foto'));
        $this->als('inka');
        $this->assertSame(400, $this->statusVan('POST', '/opname/punt/trappenhuis/foto'));
    }

    public function testOpnameNietInDeKlantApp(): void
    {
        $db = db_connect();
        $db->table('verhuizingen')->insert(['naam' => 'Particulier']);
        $vid = (int) $db->insertID();
        $db->table('memberships')->insert(['verhuizing_id' => $vid, 'user_id' => $this->user['bewa'], 'rol' => 'admin']);
        $db->table('memberships')->where('verhuizing_id', $this->vh['A1'])->delete();

        $this->als('bewa', self::HOST_KLANT);
        $this->assertStringEndsWith('/', (string) $this->get('/opname')->getRedirectUrl());
        $this->als('bewa', self::HOST_KLANT);
        $this->post('/opname/items', ['omschrijving' => 'Kast']);
        $this->assertSame(0, $this->aantal('opname_items'));
    }

    public function testOpnameIsGescoped(): void
    {
        $eigen   = $this->opnameItem('A1', 'Piano A1');
        $vreemd  = $this->opnameItem('B1', 'Piano B1');
        db_connect()->table('memberships')->insert(['verhuizing_id' => $this->vh['A1'], 'user_id' => $this->user['inka'], 'rol' => 'helper']);

        $this->als('inka');
        $this->get('/opname')->assertDontSee('Piano B1');
        $this->als('inka');
        $this->assertSame(404, $this->statusVan('GET', '/opname/items/' . $vreemd));
        $this->als('inka');
        $this->statusVan('POST', '/opname/items/' . $vreemd, ['omschrijving' => 'Overschreven']);
        $this->als('inka');
        $this->statusVan('POST', '/opname/items/' . $vreemd . '/verwijderen');
        $this->assertSame('Piano B1', db_connect()->table('opname_items')->where('id', $vreemd)->get()->getRow()->omschrijving);

        $access = $this->als('inka');
        $access->switchTo($this->vh['A1']);
        $this->assertSame([$eigen], array_map('intval', array_column((new \App\Models\OpnameItemModel())->findAll(), 'id')));
    }

    public function testSalesMarkeertRisico(): void
    {
        $item   = $this->opnameItem('A1', 'Piano');
        $vreemd = $this->opnameItem('B1', 'Kast B1');

        $this->als('sala');
        $response = $this->get('/bedrijf/verhuizingen/' . $this->vh['A1'] . '/opname');
        $response->assertOK();
        $response->assertSee('Piano');
        $response->assertDontSee('Kast B1');

        $this->als('sala');
        $this->post('/bedrijf/verhuizingen/' . $this->vh['A1'] . '/opname/' . $item . '/risico', ['risico' => '1', 'risico_notitie' => 'Verhuislift nodig']);
        $row = db_connect()->table('opname_items')->where('id', $item)->get()->getRowArray();
        $this->assertSame('1', (string) $row['risico']);
        $this->assertSame('Verhuislift nodig', $row['risico_notitie']);

        // Item van een ander bedrijf via de eigen verhuizing-URL: niets.
        $this->als('sala');
        $this->post('/bedrijf/verhuizingen/' . $this->vh['A1'] . '/opname/' . $vreemd . '/risico', ['risico' => '1']);
        $this->assertSame('0', (string) db_connect()->table('opname_items')->where('id', $vreemd)->get()->getRow()->risico);

        $this->als('plana');
        $this->assertSame(404, $this->statusVan('GET', '/bedrijf/verhuizingen/' . $this->vh['B1'] . '/opname'));

        $this->als('sala');
        $this->get('/bedrijf')->assertSee('color:var(--red-fg);">1</strong>');
    }
}
