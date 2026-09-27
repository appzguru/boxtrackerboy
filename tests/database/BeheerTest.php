<?php

use App\Libraries\Access;
use App\Libraries\Tenant;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Boxtracker;
use Config\Services;
use Tests\Support\VerseDatabase;

/**
 * Global-admin (whitelabel-plan.md stap 2), via de echte routes en filters: /beheer alleen
 * voor platform_admins op de klant-app, bedrijven aanmaken/blokkeren, planner uitnodigen,
 * en meekijken dat niets kan wijzigen en gelogd wordt.
 *
 * @internal
 */
final class BeheerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use VerseDatabase;

    private const HOST_KLANT = 'app.boxtracker.nl';
    private const HOST_A     = 'verhuizer-a.boxtracker.nl';

    private array $ids = [];

    /** @var list<string> */
    private array $mailVoor = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper('access');
        $this->laadSchema();
        // Onafhankelijk van de lokale .env (die kan tenantDomein = localtest.me hebben).
        $cfg               = config(Boxtracker::class);
        $cfg->tenantDomein = 'boxtracker.nl';
        $cfg->omgeving     = '';
        $cfg->devBedrijf   = '';

        // CSRF hoort niet bij wat hier getest wordt; tenant- en meekijkfilter blijven aan.
        $filters                    = config('Filters');
        $filters->globals['before'] = array_values(array_diff($filters->globals['before'], ['csrf']));

        $db = db_connect();
        $db->table('bedrijven')->insert(['naam' => 'Verhuizer A', 'subdomein' => 'verhuizer-a']);
        $this->ids['bedrijf'] = (int) $db->insertID();

        foreach (['P' => null, 'A1' => $this->ids['bedrijf']] as $naam => $bedrijf) {
            $db->table('verhuizingen')->insert(['naam' => 'Verhuizing ' . $naam, 'bedrijf_id' => $bedrijf]);
            $this->ids[$naam] = (int) $db->insertID();
            $db->table('boxes')->insert(['verhuizing_id' => $this->ids[$naam], 'nummer' => 1, 'token' => 'tok' . strtolower($naam), 'status' => 'ingepakt', 'omschrijving' => 'geheim ' . $naam]);
        }

        foreach (['pia' => 'p', 'admin' => 'z'] as $naam => $letter) {
            $db->table('users')->insert(['naam' => ucfirst($naam), 'email' => $naam . '@test.nl', 'password_hash' => 'x']);
            $this->ids[$naam] = (int) $db->insertID();
            $db->table('sessions')->insert(['user_id' => $this->ids[$naam], 'token' => str_repeat($letter, 64)]);
        }
        $db->table('memberships')->insert(['verhuizing_id' => $this->ids['P'], 'user_id' => $this->ids['pia'], 'rol' => 'admin']);
        $db->table('memberships')->insert(['verhuizing_id' => $this->ids['P'], 'user_id' => $this->ids['admin'], 'rol' => 'admin']);
        $db->table('platform_admins')->insert(['user_id' => $this->ids['admin']]);

        $this->mailVoor = glob(WRITEPATH . 'mail/*.txt') ?: [];
    }

    private function cfg(): Boxtracker
    {
        return clone config(Boxtracker::class);
    }

    protected function tearDown(): void
    {
        // Testmails opruimen.
        foreach (array_diff(glob(WRITEPATH . 'mail/*.txt') ?: [], $this->mailVoor) as $file) {
            unlink($file);
        }
        Services::resetSingle('access');
        Services::resetSingle('tenant');
        parent::tearDown();
    }

    /** Het volgende verzoek komt van $wie (null = niet ingelogd) op $host. */
    private function als(?string $wie, string $host = self::HOST_KLANT): void
    {
        $cookies = $wie ? [Access::USER_COOKIE => str_repeat(['pia' => 'p', 'admin' => 'z', 'nieuw' => 'n'][$wie], 64)] : [];
        $tenant  = new Tenant($host, $this->cfg());
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('cookie', $cookies);
        Services::injectMock('tenant', $tenant);
        Services::injectMock('access', new Access($request, $tenant));
    }

    /** Status van een verzoek; een 404-exception telt als 404. */
    private function statusVan(string $method, string $path, array $params = []): int
    {
        try {
            return $this->call($method, $path, $params)->response()->getStatusCode();
        } catch (PageNotFoundException) {
            return 404;
        }
    }

    private function log(): array
    {
        return array_column(db_connect()->table('beheer_log')->orderBy('id')->get()->getResultArray(), 'actie');
    }

    public function testBeheerAlleenVoorGlobalAdminOpKlantApp(): void
    {
        $this->als(null);
        $response = $this->get('/beheer');
        $response->assertRedirectTo('/login?next=%2Fbeheer');

        $this->als('pia');
        $this->assertSame(404, $this->statusVan('GET', '/beheer'));
        $this->als('pia');
        $this->assertSame(404, $this->statusVan('POST', '/beheer/bedrijven', ['naam' => 'X', 'subdomein' => 'xx']));
        $this->assertSame(1, db_connect()->table('bedrijven')->countAllResults());

        $this->als('admin', self::HOST_A);
        $this->assertSame(404, $this->statusVan('GET', '/beheer'), 'niet op een bedrijfssubdomein');

        $this->als('admin');
        $response = $this->get('/beheer');
        $response->assertOK();
        $response->assertSee('Verhuizer A');
    }

    public function testBedrijfAanmaken(): void
    {
        foreach (['app', 'verhuizer-a', '-fout', 'Hoofd Letters'] as $sub) {
            $this->als('admin');
            $this->post('/beheer/bedrijven', ['naam' => 'Nieuw', 'subdomein' => $sub]);
        }
        $this->assertSame(1, db_connect()->table('bedrijven')->countAllResults(), 'gereserveerd, bezet en ongeldig geweigerd');

        $this->als('admin');
        $this->post('/beheer/bedrijven', ['naam' => 'Kwiek Verhuizingen', 'subdomein' => ' Kwiek ']);
        $row = db_connect()->table('bedrijven')->where('subdomein', 'kwiek')->get()->getRowArray();
        $this->assertSame('Kwiek Verhuizingen', $row['naam']);
        $this->assertSame('actief', $row['status']);
        $this->assertSame(['bedrijf_aangemaakt'], $this->log());
        $this->assertSame(Tenant::BEDRIJF, (new Tenant('kwiek.boxtracker.nl', $this->cfg()))->status());
    }

    private function blok(): array
    {
        return db_connect()->table('bedrijven')->select('status, blok_memo, blok_sinds')->where('id', $this->ids['bedrijf'])->get()->getRowArray();
    }

    public function testBlokkerenMetMemo(): void
    {
        $url = '/beheer/bedrijven/' . $this->ids['bedrijf'] . '/status';

        $this->als('admin');
        $this->post($url, ['status' => 'hardblock', 'memo' => '  ']);
        $this->assertSame('actief', $this->blok()['status'], 'zonder memo geen blokkade');

        $this->als('admin');
        $this->post($url, ['status' => 'softblock', 'memo' => 'Factuur september over tijd']);
        $this->assertSame('softblock', $this->blok()['status']);
        $this->assertSame('Factuur september over tijd', $this->blok()['blok_memo']);
        $this->assertNotNull($this->blok()['blok_sinds']);

        $this->als('admin');
        $this->post($url, ['status' => 'hardblock', 'memo' => 'Na 3 herinneringen nog niet betaald']);
        $this->assertSame('hardblock', $this->blok()['status']);

        $this->als('admin');
        $this->get('/beheer/bedrijven/' . $this->ids['bedrijf'])->assertSee('Na 3 herinneringen nog niet betaald');
        $this->als('admin');
        $this->get('/beheer')->assertSee('Hardblock');

        $this->als('admin');
        $this->post($url, ['status' => 'actief', 'memo' => '']);
        $this->assertSame(['status' => 'actief', 'blok_memo' => null, 'blok_sinds' => null], $this->blok());

        $this->assertSame(['bedrijf_softblock', 'bedrijf_hardblock', 'bedrijf_geactiveerd'], $this->log());
        $this->assertSame('Factuur september over tijd', db_connect()->table('beheer_log')->where('actie', 'bedrijf_softblock')->get()->getRow()->detail);
    }

    /** Planner (cookie n) en bewoner (cookie b) bij verhuizing A1 van bedrijf A. */
    private function plannerEnBewoner(string $status, ?string $memo): void
    {
        $db = db_connect();
        $db->table('users')->insert(['naam' => 'Plan', 'email' => 'plan@test.nl', 'password_hash' => 'x']);
        $planner = (int) $db->insertID();
        $db->table('bedrijf_medewerkers')->insert(['bedrijf_id' => $this->ids['bedrijf'], 'user_id' => $planner, 'rol' => 'planner']);
        $db->table('sessions')->insert(['user_id' => $planner, 'token' => str_repeat('n', 64)]);
        $db->table('users')->insert(['naam' => 'Bewoner', 'email' => 'bew@test.nl', 'password_hash' => 'x']);
        $bewoner = (int) $db->insertID();
        $db->table('memberships')->insert(['verhuizing_id' => $this->ids['A1'], 'user_id' => $bewoner, 'rol' => 'admin']);
        $db->table('sessions')->insert(['user_id' => $bewoner, 'token' => str_repeat('b', 64)]);
        $db->table('bedrijven')->where('id', $this->ids['bedrijf'])->update(['status' => $status, 'blok_memo' => $memo]);
    }

    private function alsCookie(string $letter, string $host): void
    {
        $tenant  = new Tenant($host, $this->cfg());
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('cookie', [Access::USER_COOKIE => str_repeat($letter, 64)]);
        Services::injectMock('tenant', $tenant);
        Services::injectMock('access', new Access($request, $tenant));
    }

    public function testHardblockVoeltHetBedrijfNietDeKlant(): void
    {
        $this->plannerEnBewoner('hardblock', 'geheime memo');

        $this->alsCookie('n', self::HOST_A);
        $response = $this->get('/d/1-toka1');
        $this->assertSame(403, $response->response()->getStatusCode());
        $response->assertSee('Je account is geblokkeerd');
        $response->assertDontSee('geheim A1');
        $response->assertDontSee('geheime memo');

        // Bewoner: gewoon doorgaan, ook invoeren.
        $this->alsCookie('b', self::HOST_A);
        $this->get('/d/1-toka1')->assertSee('geheim A1');
        $this->alsCookie('b', self::HOST_A);
        $this->post('/d/1-toka1/status', ['status' => 'uitgepakt']);
        $this->assertSame('uitgepakt', db_connect()->table('boxes')->where('token', 'toka1')->get()->getRow()->status);
    }

    public function testSoftblockMeldingAlleenVoorMedewerkers(): void
    {
        $this->plannerEnBewoner('softblock', 'geheime memo');

        $this->alsCookie('n', self::HOST_A);
        $response = $this->get('/d/1-toka1');
        $response->assertOK();
        $response->assertSee('geheim A1');
        $response->assertSee('Account beperkt');
        $response->assertDontSee('geheime memo');

        $this->alsCookie('b', self::HOST_A);
        $response = $this->get('/d/1-toka1');
        $response->assertSee('geheim A1');
        $response->assertDontSee('Account beperkt');
    }

    public function testPlannerUitnodigenEnRegistreren(): void
    {
        $this->als('admin');
        $this->post('/beheer/bedrijven/' . $this->ids['bedrijf'] . '/uitnodigen', ['email' => 'Planner@Verhuizer-A.nl', 'rol' => 'planner']);
        $invite = db_connect()->table('medewerker_uitnodigingen')->get()->getRowArray();
        $this->assertSame('planner@verhuizer-a.nl', $invite['email']);
        $this->assertSame(['medewerker_uitgenodigd'], $this->log());

        $this->als('admin');
        $response = $this->get('/beheer/bedrijven/' . $this->ids['bedrijf']);
        $response->assertOK();
        $response->assertSee('planner@verhuizer-a.nl');
        $response->assertSee('Verhuizing A1');

        $mail = array_values(array_diff(glob(WRITEPATH . 'mail/*.txt') ?: [], $this->mailVoor));
        $this->assertCount(1, $mail);
        $this->assertStringContainsString('://verhuizer-a.boxtracker.nl', file_get_contents($mail[0]));

        // Op de klant-app wordt de uitnodiging niet herkend: gewoon het particuliere formulier.
        $this->als(null);
        $response = $this->get('/registreren?medewerker=' . $invite['token']);
        $response->assertSee('Naam van je verhuizing');
        $response->assertDontSee('Welkom bij Verhuizer A');

        // Registreren op het subdomein; het e-mailadres komt uit de uitnodiging, niet uit het formulier.
        $this->als(null, self::HOST_A);
        $this->post('/registreren', ['medewerker' => $invite['token'], 'naam' => 'Paula', 'email' => 'iemand@anders.nl', 'password' => 'geheim1234']);

        $user = db_connect()->table('users')->where('naam', 'Paula')->get()->getRowArray();
        $this->assertSame('planner@verhuizer-a.nl', $user['email']);
        $this->assertNotNull($user['email_verified_at']);
        $mw = db_connect()->table('bedrijf_medewerkers')->where('user_id', $user['id'])->get()->getRowArray();
        $this->assertSame('planner', $mw['rol']);
        $this->assertSame((string) $this->ids['bedrijf'], (string) $mw['bedrijf_id']);
        $this->assertSame(0, db_connect()->table('memberships')->where('user_id', $user['id'])->countAllResults(), 'geen eigen verhuizing');
        $this->assertNotNull(db_connect()->table('medewerker_uitnodigingen')->where('id', $invite['id'])->get()->getRow()->used_at);

        // Als planner op het subdomein: alle verhuizingen van het bedrijf, niet de particuliere.
        db_connect()->table('sessions')->insert(['user_id' => $user['id'], 'token' => str_repeat('n', 64)]);
        $this->als('nieuw', self::HOST_A);
        $response = $this->get('/verhuizingen');
        $response->assertOK();
        $response->assertSee('Verhuizing A1');
        $response->assertDontSee('Verhuizing P');
        $response->assertDontSee('Zelf een verhuizing starten');
    }

    public function testUitnodigingAlleenVoorJuisteEmail(): void
    {
        $this->als('admin');
        $this->post('/beheer/bedrijven/' . $this->ids['bedrijf'] . '/uitnodigen', ['email' => 'planner@verhuizer-a.nl']);
        $token = db_connect()->table('medewerker_uitnodigingen')->get()->getRow()->token;

        $this->als('pia', self::HOST_A);
        $this->get('/medewerker/' . $token)->assertSee('Deze uitnodiging is voor planner@verhuizer-a.nl');
        $this->als('pia', self::HOST_A);
        $this->post('/medewerker/' . $token);
        $this->assertSame(0, db_connect()->table('bedrijf_medewerkers')->countAllResults());
    }

    public function testRegistrerenOpSubdomeinAlleenMetUitnodiging(): void
    {
        $this->als(null, self::HOST_A);
        $this->post('/registreren', ['naam' => 'Zomaar', 'email' => 'zomaar@test.nl', 'password' => 'geheim1234']);
        $this->assertSame(0, db_connect()->table('users')->where('email', 'zomaar@test.nl')->countAllResults());
    }

    public function testMeekijkenAlleenLezenEnGelogd(): void
    {
        $this->als('admin');
        $this->post('/beheer/meekijken/' . $this->ids['A1'])->assertRedirectTo('/');
        $this->assertSame(['meekijken_start'], $this->log());

        $this->als('admin');
        $response = $this->get('/d/1-toka1');
        $response->assertOK();
        $response->assertSee('geheim A1');
        $response->assertSee('Je kijkt mee');

        $this->als('admin');
        $this->assertSame(403, $this->statusVan('POST', '/d/1-toka1/status', ['status' => 'uitgepakt']));
        $this->assertSame('ingepakt', db_connect()->table('boxes')->where('token', 'toka1')->get()->getRow()->status);

        $this->als('admin');
        $this->assertSame(403, $this->statusVan('POST', '/labels', ['aantal' => 10]));
        $this->assertSame(1, db_connect()->table('boxes')->where('verhuizing_id', $this->ids['A1'])->countAllResults());

        $this->als('admin');
        $this->post('/beheer/meekijken/stop');
        $this->assertSame(['meekijken_start', 'meekijken_stop'], $this->log());
        $this->assertNull(db_connect()->table('sessions')->where('user_id', $this->ids['admin'])->get()->getRow()->meekijk_verhuizing_id);
    }

    public function testStickerOpVerkeerdeIngangGaatDoor(): void
    {
        $this->als(null);
        $response = $this->get('/d/1-toka1');
        $response->assertRedirect();
        $this->assertMatchesRegularExpression('#^https?://verhuizer-a\.boxtracker\.nl(:\d+)?/d/1-toka1$#', $response->getRedirectUrl());

        $this->als(null, self::HOST_A);
        $response = $this->get('/d/1-tokp');
        $this->assertSame(rtrim(config('App')->baseURL, '/') . '/d/1-tokp', $response->getRedirectUrl());

        // Op de eigen ingang: gewoon naar inloggen.
        $this->als(null, self::HOST_A);
        $this->assertStringContainsString('/login?next=', $this->get('/d/1-toka1')->getRedirectUrl());
    }

    public function testHuisstijlAlleenOpBedrijfsSubdomein(): void
    {
        db_connect()->table('bedrijven')->insert(['naam' => 'Kwiek Verhuist', 'subdomein' => 'kwiek']);

        $this->als(null);
        $response = $this->get('/login');
        $response->assertDontSee('merken/');
        $response->assertSee('<title>Inloggen — Boxtracker</title>');

        $this->als(null, 'kwiek.boxtracker.nl');
        $response = $this->get('/login');
        $response->assertSee('merken/kwiek/merk.css');
        $response->assertSee('merken/kwiek/logo.svg');
        $response->assertSee('<title>Inloggen — Kwiek Verhuist</title>');
        $response->assertSee('bij Kwiek Verhuist');

        // Bedrijf zonder merkmap: gewone Boxtracker-stijl.
        $this->als(null, self::HOST_A);
        $this->get('/login')->assertDontSee('merken/');
    }

    public function testMeekijkenNietBijParticulierOfZonderRechten(): void
    {
        $this->als('admin');
        $this->post('/beheer/meekijken/' . $this->ids['P']);

        $this->als('pia');
        $this->assertSame(404, $this->statusVan('POST', '/beheer/meekijken/' . $this->ids['A1']));

        $this->assertSame([], $this->log());
        $this->assertSame(0, db_connect()->table('sessions')->where('meekijk_verhuizing_id IS NOT NULL')->countAllResults());
    }

    public function testHealth(): void
    {
        $this->als(null);
        $response = $this->get('/health');
        $json     = json_decode($response->getJSON(), true);
        $this->assertSame('ok', $json['checks']['database']);
        $this->assertSame('ok', $json['checks']['opslag']);
        $this->assertArrayHasKey('mail', $json['checks']);
        $this->assertStringNotContainsString(WRITEPATH, $response->getJSON());
    }
}
