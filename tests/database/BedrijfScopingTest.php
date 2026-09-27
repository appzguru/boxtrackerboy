<?php

use App\Filters\MeekijkFilter;
use App\Filters\TenantFilter;
use App\Libraries\Access;
use App\Libraries\Tenant;
use App\Models\BoxModel;
use App\Models\InviteModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Boxtracker;
use Config\Services;
use Tests\Support\VerseDatabase;

/**
 * Lektest whitelabel (whitelabel-plan.md §3, stap 1–2): twee verhuizers en een particulier.
 * Verhuizer A mag aantoonbaar niets zien van verhuizer B of van particulieren, en andersom —
 * per ingang (hostnaam), per rol, voor gasten, uitnodigingen en meekijken.
 *
 * @internal
 */
final class BedrijfScopingTest extends CIUnitTestCase
{
    use VerseDatabase;

    private const HOST_KLANT = 'app.boxtracker.nl';
    private const HOST_A     = 'verhuizer-a.boxtracker.nl';
    private const HOST_B     = 'verhuizer-b.boxtracker.nl';
    private const HOST_DICHT = 'dicht.boxtracker.nl';

    /** @var array<string, int> */
    private array $bedrijf = [];

    /** @var array<string, int> verhuizing-ids: P (particulier), A1, A2, B1, X1 */
    private array $vh = [];

    /** @var array<string, int> user-ids */
    private array $user = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper('access');
        $this->laadSchema();
        $db = db_connect();

        foreach (['a' => 'verhuizer-a', 'b' => 'verhuizer-b', 'x' => 'dicht'] as $k => $sub) {
            $db->table('bedrijven')->insert(['naam' => 'Bedrijf ' . strtoupper($k), 'subdomein' => $sub, 'status' => $k === 'x' ? 'geblokkeerd' : 'actief']);
            $this->bedrijf[$k] = (int) $db->insertID();
        }

        foreach (['P' => null, 'A1' => 'a', 'A2' => 'a', 'B1' => 'b', 'X1' => 'x'] as $naam => $b) {
            $db->table('verhuizingen')->insert(['naam' => 'Verhuizing ' . $naam, 'bedrijf_id' => $b ? $this->bedrijf[$b] : null]);
            $vid            = (int) $db->insertID();
            $this->vh[$naam] = $vid;
            $db->table('boxes')->insert(['verhuizing_id' => $vid, 'nummer' => 1, 'token' => 'tok' . strtolower($naam), 'status' => 'ingepakt', 'omschrijving' => 'geheim ' . $naam]);
        }

        // Sessie-token = de eerste letter 64 keer; geen actieve verhuizing.
        $mensen = [
            'pia'   => 'p', // particulier, admin van P
            'plana' => 'q', // planner bij A
            'sala'  => 's', // sales bij A
            'inka'  => 'i', // inpakker bij A, toegewezen aan A1
            'sjoa'  => 'j', // sjouwer bij A, toegewezen aan A1
            'bewa'  => 'w', // bewoner van A1 (geen medewerker)
            'ouda'  => 'o', // gedeactiveerde inpakker bij A, nog lid van A1
            'planb' => 'r', // planner bij B
            'planx' => 'x', // planner bij het geblokkeerde bedrijf
            'admin' => 'z', // global-admin
        ];
        foreach ($mensen as $naam => $letter) {
            $db->table('users')->insert(['naam' => ucfirst($naam), 'email' => $naam . '@test.nl', 'password_hash' => 'x']);
            $this->user[$naam] = (int) $db->insertID();
            $db->table('sessions')->insert(['user_id' => $this->user[$naam], 'token' => str_repeat($letter, 64)]);
        }

        $medewerkers = [
            ['plana', 'a', 'planner', 1], ['sala', 'a', 'sales', 1], ['inka', 'a', 'inpakker', 1],
            ['sjoa', 'a', 'sjouwer', 1], ['ouda', 'a', 'inpakker', 0], ['planb', 'b', 'planner', 1],
            ['planx', 'x', 'planner', 1],
        ];
        foreach ($medewerkers as [$naam, $b, $rol, $actief]) {
            $db->table('bedrijf_medewerkers')->insert(['bedrijf_id' => $this->bedrijf[$b], 'user_id' => $this->user[$naam], 'rol' => $rol, 'actief' => $actief]);
        }

        $leden = [['pia', 'P', 'admin'], ['inka', 'A1', 'helper'], ['sjoa', 'A1', 'sjouwer'], ['bewa', 'A1', 'admin'], ['ouda', 'A1', 'helper']];
        foreach ($leden as [$naam, $v, $rol]) {
            $db->table('memberships')->insert(['verhuizing_id' => $this->vh[$v], 'user_id' => $this->user[$naam], 'rol' => $rol]);
        }

        $db->table('platform_admins')->insert(['user_id' => $this->user['admin']]);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('access');
        Services::resetSingle('tenant');
        parent::tearDown();
    }

    private function tenant(string $host): Tenant
    {
        return new Tenant($host, new Boxtracker());
    }

    /** Doet alsof het volgende request op deze hostnaam binnenkomt met deze cookies. */
    private function actAs(string $host, array $cookies): Access
    {
        $tenant  = $this->tenant($host);
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('cookie', $cookies);
        $access = new Access($request, $tenant);
        Services::injectMock('tenant', $tenant);
        Services::injectMock('access', $access);

        return $access;
    }

    private function als(string $naam, string $host): Access
    {
        $letters = ['pia' => 'p', 'plana' => 'q', 'sala' => 's', 'inka' => 'i', 'sjoa' => 'j', 'bewa' => 'w', 'ouda' => 'o', 'planb' => 'r', 'planx' => 'x', 'admin' => 'z'];

        return $this->actAs($host, [Access::USER_COOKIE => str_repeat($letters[$naam], 64)]);
    }

    /** @return list<int> */
    private function zichtbaar(Access $access): array
    {
        $ids = array_map('intval', array_column($access->memberships(), 'id'));
        sort($ids);

        return $ids;
    }

    private function ids(string ...$namen): array
    {
        $ids = array_map(fn ($n) => $this->vh[$n], $namen);
        sort($ids);

        return $ids;
    }

    public function testHostnamen(): void
    {
        $this->assertSame(Tenant::KLANT, $this->tenant('app.boxtracker.nl')->status());
        $this->assertSame(Tenant::KLANT, $this->tenant('boxtracker.nl')->status());
        $this->assertSame(Tenant::KLANT, $this->tenant('localhost')->status());
        $this->assertSame(Tenant::KLANT, $this->tenant('boxtracker.minisaas.nl')->status());
        $this->assertSame(Tenant::KLANT, $this->tenant('portfolio.boxtracker.nl')->status(), 'gereserveerd');
        $this->assertSame(Tenant::KLANT, $this->tenant('www.boxtracker.nl')->status(), 'gereserveerd');

        $a = $this->tenant('Verhuizer-A.boxtracker.nl:443');
        $this->assertSame(Tenant::BEDRIJF, $a->status());
        $this->assertSame($this->bedrijf['a'], $a->bedrijfId());

        $this->assertSame(Tenant::ONBEKEND, $this->tenant('bestaatniet.boxtracker.nl')->status());
        $this->assertSame(Tenant::ONBEKEND, $this->tenant('x.verhuizer-a.boxtracker.nl')->status());
        $this->assertSame(Tenant::ONBEKEND, $this->tenant("verhuizer-a'--.boxtracker.nl")->status());

        $dicht = $this->tenant(self::HOST_DICHT);
        $this->assertSame(Tenant::GEBLOKKEERD, $dicht->status());
        $this->assertNull($dicht->bedrijfId());
        $this->assertFalse($dicht->owns($this->bedrijf['x']));
        $this->assertFalse($dicht->owns(null));
    }

    public function testDevBedrijfAlleenOpDev(): void
    {
        $config             = new Boxtracker();
        $config->devBedrijf = 'verhuizer-b';
        $this->assertSame(Tenant::KLANT, (new Tenant('boxtracker.minisaas.nl', $config))->status(), 'prd negeert devBedrijf');

        $config->omgeving = 'dev';
        $t                = new Tenant('boxtracker.minisaas.nl', $config);
        $this->assertSame($this->bedrijf['b'], $t->bedrijfId());
    }

    public function testScopeOpQueries(): void
    {
        $tel = fn (string $host) => $this->tenant($host)->scope(db_connect()->table('verhuizingen'))->countAllResults();

        $this->assertSame(1, $tel(self::HOST_KLANT));
        $this->assertSame(2, $tel(self::HOST_A));
        $this->assertSame(1, $tel(self::HOST_B));
        $this->assertSame(0, $tel(self::HOST_DICHT));
        $this->assertSame(0, $tel('bestaatniet.boxtracker.nl'));
    }

    public function testPlannerZietAlleenEigenBedrijf(): void
    {
        $access = $this->als('plana', self::HOST_A);
        $this->assertSame($this->ids('A1', 'A2'), $this->zichtbaar($access));
        $this->assertFalse($access->switchTo($this->vh['B1']));
        $this->assertFalse($access->switchTo($this->vh['P']));
        $this->assertFalse($access->hasAccessTo($this->vh['B1']));

        $this->assertTrue($access->switchTo($this->vh['A2']));
        $this->assertTrue($access->can('admin'));
        $this->assertSame(['geheim A2'], array_column((new BoxModel())->findAll(), 'omschrijving'));
        $this->assertNull((new BoxModel())->findByToken('tokb1'));

        $this->assertTrue($this->als('sala', self::HOST_A)->switchTo($this->vh['A1']), 'sales = admin in eigen bedrijf');
    }

    public function testMedewerkerZietNietsOpAndereIngang(): void
    {
        foreach ([self::HOST_KLANT, self::HOST_B] as $host) {
            $access = $this->als('plana', $host);
            $this->assertSame([], $this->zichtbaar($access), $host);
            $this->assertFalse($access->switchTo($this->vh['A1']), $host);
            $this->assertFalse($access->switchTo($this->vh['B1']), $host);
            $this->assertNull($access->verhuizingId(), $host);
        }
    }

    public function testParticulierAlleenInKlantApp(): void
    {
        $access = $this->als('pia', self::HOST_KLANT);
        $this->assertSame($this->ids('P'), $this->zichtbaar($access));
        $this->assertSame($this->vh['P'], $access->verhuizingId(), 'enige verhuizing wordt vanzelf actief');

        $access = $this->als('pia', self::HOST_A);
        $this->assertSame([], $this->zichtbaar($access));
        $this->assertFalse($access->switchTo($this->vh['P']));
    }

    public function testActieveVerhuizingVanAndereIngangTeltNiet(): void
    {
        // Sessie staat nog op A1 (bv. cookie gekopieerd naar een andere hostnaam).
        db_connect()->table('sessions')->where('user_id', $this->user['plana'])->update(['active_verhuizing_id' => $this->vh['A1']]);

        $this->assertSame($this->vh['A1'], $this->als('plana', self::HOST_A)->verhuizingId());
        $this->assertNull($this->als('plana', self::HOST_KLANT)->verhuizingId());
        $this->assertNull($this->als('plana', self::HOST_B)->verhuizingId());
    }

    public function testInpakkerEnSjouwerAlleenToegewezen(): void
    {
        $inpakker = $this->als('inka', self::HOST_A);
        $this->assertSame($this->ids('A1'), $this->zichtbaar($inpakker));
        $this->assertSame($this->vh['A1'], $inpakker->verhuizingId());
        $this->assertTrue($inpakker->can('helper'));
        $this->assertFalse($inpakker->can('admin'));
        $this->assertFalse($inpakker->switchTo($this->vh['A2']));

        $sjouwer = $this->als('sjoa', self::HOST_A);
        $this->assertSame($this->ids('A1'), $this->zichtbaar($sjouwer));
        $this->assertTrue($sjouwer->can('sjouwer'));
        $this->assertFalse($sjouwer->can('helper'), 'sjouwer ziet geen inhoud (Box::show toont dan box_sjouwer)');
        $this->assertFalse($sjouwer->switchTo($this->vh['A2']));
    }

    public function testBewonerViaLidmaatschap(): void
    {
        $access = $this->als('bewa', self::HOST_A);
        $this->assertSame($this->ids('A1'), $this->zichtbaar($access));
        $this->assertTrue($access->can('admin'));

        $this->assertSame([], $this->zichtbaar($this->als('bewa', self::HOST_KLANT)));
    }

    public function testGedeactiveerdeMedewerkerNergensMeer(): void
    {
        $access = $this->als('ouda', self::HOST_A);
        $this->assertNotNull($access->user());
        $this->assertSame([], $this->zichtbaar($access));
        $this->assertFalse($access->switchTo($this->vh['A1']));
        $this->assertFalse($access->hasAccessTo($this->vh['A1']));
    }

    public function testGeblokkeerdBedrijfIsDicht(): void
    {
        $access = $this->als('planx', self::HOST_DICHT);
        $this->assertSame([], $this->zichtbaar($access));
        $this->assertFalse($access->switchTo($this->vh['X1']));
    }

    public function testGastAlleenOpEigenIngang(): void
    {
        db_connect()->table('guest_sessions')->insert([
            'verhuizing_id' => $this->vh['A1'], 'rol' => 'sjouwer', 'naam' => 'Kees',
            'token' => str_repeat('g', 64), 'expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
        $cookie = [Access::GUEST_COOKIE => str_repeat('g', 64)];

        $this->assertSame($this->vh['A1'], $this->actAs(self::HOST_A, $cookie)->verhuizingId());
        $this->assertFalse($this->actAs(self::HOST_KLANT, $cookie)->isAuthenticated());
        $this->assertFalse($this->actAs(self::HOST_B, $cookie)->isAuthenticated());
    }

    public function testUitnodigingAlleenOpEigenIngang(): void
    {
        $db = db_connect();
        foreach (['A1' => str_repeat('a', 32), 'P' => str_repeat('b', 32)] as $v => $token) {
            $db->table('invites')->insert(['verhuizing_id' => $this->vh[$v], 'token' => $token, 'rol' => 'helper', 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
        }

        $vind = function (string $host, string $token): ?array {
            $this->actAs($host, []);

            return (new InviteModel())->findUsable($token);
        };

        $this->assertNotNull($vind(self::HOST_A, str_repeat('a', 32)));
        $this->assertNull($vind(self::HOST_KLANT, str_repeat('a', 32)));
        $this->assertNull($vind(self::HOST_B, str_repeat('a', 32)));
        $this->assertNotNull($vind(self::HOST_KLANT, str_repeat('b', 32)));
        $this->assertNull($vind(self::HOST_A, str_repeat('b', 32)));
    }

    private function meekijkNaar(string $v): void
    {
        db_connect()->table('sessions')->where('user_id', $this->user['admin'])->update(['meekijk_verhuizing_id' => $this->vh[$v]]);
    }

    public function testMeekijken(): void
    {
        $this->meekijkNaar('A1');
        $access = $this->als('admin', self::HOST_KLANT);
        $this->assertTrue($access->meekijken());
        $this->assertSame($this->vh['A1'], $access->verhuizingId());
        $this->assertSame(['geheim A1'], array_column((new BoxModel())->findAll(), 'omschrijving'));
        $this->assertFalse($access->switchTo($this->vh['B1']), 'tijdens meekijken niet wisselen');
        $this->assertFalse($access->hasAccessTo($this->vh['B1']));

        // Niet op een bedrijfssubdomein.
        $this->assertFalse($this->als('admin', self::HOST_A)->meekijken());
    }

    public function testMeekijkenNooitBijParticulier(): void
    {
        $this->meekijkNaar('P');
        $access = $this->als('admin', self::HOST_KLANT);
        $this->assertFalse($access->meekijken());
        $this->assertNull($access->verhuizingId());
    }

    public function testMeekijkenAlleenVoorGlobalAdmin(): void
    {
        db_connect()->table('sessions')->where('user_id', $this->user['pia'])->update(['meekijk_verhuizing_id' => $this->vh['A1']]);
        $access = $this->als('pia', self::HOST_KLANT);
        $this->assertFalse($access->meekijken());
        $this->assertSame($this->vh['P'], $access->verhuizingId());
    }

    private function request(string $method, string $path): IncomingRequest
    {
        $config  = config('App');
        $request = new IncomingRequest($config, new SiteURI($config, $path), null, new UserAgent());

        return $request->withMethod($method);
    }

    public function testMeekijkFilterWeigertSchrijven(): void
    {
        $this->meekijkNaar('A1');
        $this->als('admin', self::HOST_KLANT);
        $filter = new MeekijkFilter();

        $this->assertNull($filter->before($this->request('GET', 'd/1-toka1')));
        $this->assertNull($filter->before($this->request('POST', 'beheer/meekijken/stop')));
        foreach (['d/1-toka1', 'd/1-toka1/status', 'labels', 'leden/uitnodigen', 'verhuizing/verwijderen', 'verhuizingen/1/kies'] as $path) {
            $response = $filter->before($this->request('POST', $path));
            $this->assertInstanceOf(ResponseInterface::class, $response, $path);
            $this->assertSame(403, $response->getStatusCode(), $path);
        }
    }

    public function testMeekijkFilterLaatGewoneGebruikersMet(): void
    {
        $this->als('plana', self::HOST_A);
        $this->assertNull((new MeekijkFilter())->before($this->request('POST', 'labels')));
    }

    public function testTenantFilter(): void
    {
        $filter = new TenantFilter();
        $status = function (string $host) use ($filter): ?int {
            $this->actAs($host, []);
            $response = $filter->before($this->request('GET', 'login'));

            return $response?->getStatusCode();
        };

        $this->assertNull($status(self::HOST_KLANT));
        $this->assertNull($status(self::HOST_A));
        $this->assertSame(404, $status('bestaatniet.boxtracker.nl'));
        $this->assertSame(503, $status(self::HOST_DICHT));
    }
}
