<?php

use App\Libraries\Access;
use App\Models\BoxModel;
use App\Models\LocationModel;
use App\Models\MovementModel;
use App\Models\PhotoModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Lektest (handoff.md §6): twee verhuizingen met data. Wie in verhuizing A zit, mag via
 * de models niets van B zien, wijzigen of verwijderen — en rollen/gasten gedragen zich.
 *
 * Draait tegen de `tests`-database uit .env (lokaal: boxtracker_test); het schema wordt
 * per test vers geladen uit sql/schema.sql.
 *
 * @internal
 */
final class ScopingTest extends CIUnitTestCase
{
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper('access');

        $db = db_connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($db->listTables() as $table) {
            $db->query('DROP TABLE `' . $table . '`');
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
        $db->resetDataCache();

        $sql = file_get_contents(ROOTPATH . 'sql/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))) as $statement) {
            $db->query($statement);
        }

        $now = date('Y-m-d H:i:s');
        foreach (['anna', 'bert'] as $naam) {
            $db->table('users')->insert(['naam' => ucfirst($naam), 'email' => $naam . '@test.nl', 'password_hash' => 'x']);
            $uid = $db->insertID();
            $db->table('verhuizingen')->insert(['naam' => 'Verhuizing ' . $naam, 'created_by' => $uid]);
            $vid = $db->insertID();
            $db->table('memberships')->insert(['verhuizing_id' => $vid, 'user_id' => $uid, 'rol' => 'admin']);
            $db->table('sessions')->insert(['user_id' => $uid, 'token' => str_repeat($naam[0], 64), 'active_verhuizing_id' => $vid]);
            $db->table('boxes')->insert(['verhuizing_id' => $vid, 'nummer' => 1, 'token' => $naam . '1', 'status' => 'ingepakt', 'omschrijving' => 'geheim van ' . $naam, 'eigenaar' => ucfirst($naam)]);
            $bid = $db->insertID();
            $db->table('movements')->insert(['verhuizing_id' => $vid, 'box_id' => $bid, 'naar_locatie' => 'zolder ' . $naam, 'door' => $naam, 'op' => $now]);
            $db->table('photos')->insert(['verhuizing_id' => $vid, 'box_id' => $bid, 'bestandsnaam' => $naam . '.jpg']);
            $pid = $db->insertID();
            $db->table('locations')->insert(['verhuizing_id' => $vid, 'naam' => 'kamer ' . $naam, 'soort' => 'nieuw huis']);
            $this->ids[$naam] = ['user' => $uid, 'verhuizing' => $vid, 'box' => $bid, 'photo' => $pid];
        }
    }

    protected function tearDown(): void
    {
        Services::resetSingle('access');
        parent::tearDown();
    }

    /** Doet alsof het volgende request deze cookies meestuurt. */
    private function actAs(array $cookies): Access
    {
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('cookie', $cookies);
        $access = new Access($request);
        Services::injectMock('access', $access);

        return $access;
    }

    private function actAsAnna(): Access
    {
        return $this->actAs([Access::USER_COOKIE => str_repeat('a', 64)]);
    }

    public function testReadsAreScoped(): void
    {
        $this->actAsAnna();
        $b = $this->ids['bert'];

        $boxes = new BoxModel();
        $this->assertSame(['geheim van anna'], array_column($boxes->findAll(), 'omschrijving'));
        $this->assertNull($boxes->find($b['box']));
        $this->assertNull($boxes->findByToken('bert1'));
        $this->assertSame(1, $boxes->countAll());
        $this->assertSame(['Anna'], $boxes->ownerSuggestions());
        $this->assertSame([], $boxes->search('geheim van bert'));
        $this->assertCount(1, $boxes->search('geheim'));

        $this->assertNull((new PhotoModel())->find($b['photo']));
        $this->assertSame([], (new MovementModel())->journeyFor($b['box']));
        $this->assertSame(['zolder anna'], (new MovementModel())->recentDestinations());
        $this->assertSame(['kamer anna'], (new LocationModel())->suggestions('nieuw huis'));
    }

    public function testWritesCannotTouchOtherVerhuizing(): void
    {
        $this->actAsAnna();
        $b     = $this->ids['bert'];
        $boxes = new BoxModel();

        $boxes->update($b['box'], ['omschrijving' => 'overschreven']);
        $boxes->delete($b['box']);
        (new LocationModel())->hide('kamer bert');

        $row = db_connect()->table('boxes')->where('id', $b['box'])->get()->getRowArray();
        $this->assertSame('geheim van bert', $row['omschrijving']);
        $this->assertSame('1', (string) db_connect()->table('locations')->where('naam', 'kamer bert')->get()->getRow()->actief);
    }

    public function testInsertGetsActiveVerhuizing(): void
    {
        $this->actAsAnna();
        $id = (new BoxModel())->insert(['nummer' => 2, 'token' => 'nieuw2', 'status' => 'leeg', 'verhuizing_id' => $this->ids['bert']['verhuizing']]);

        $row = db_connect()->table('boxes')->where('id', $id)->get()->getRowArray();
        $this->assertSame($this->ids['anna']['verhuizing'], (int) $row['verhuizing_id']);
    }

    public function testNoVerhuizingFailsClosed(): void
    {
        $this->actAs([]);
        $this->expectException(RuntimeException::class);
        (new BoxModel())->findAll();
    }

    public function testCannotSwitchToForeignVerhuizing(): void
    {
        $access = $this->actAsAnna();
        $this->assertFalse($access->switchTo($this->ids['bert']['verhuizing']));
        $this->assertSame($this->ids['anna']['verhuizing'], $access->verhuizingId());
        $this->assertFalse($access->hasAccessTo($this->ids['bert']['verhuizing']));
    }

    public function testRoles(): void
    {
        $access = $this->actAsAnna();
        $this->assertTrue($access->can('admin'));

        db_connect()->table('memberships')->where('user_id', $this->ids['anna']['user'])->update(['rol' => 'helper']);
        $access = $this->actAsAnna();
        $this->assertTrue($access->can('helper'));
        $this->assertFalse($access->can('admin'));
    }

    public function testGuestSessions(): void
    {
        $db  = db_connect();
        $vid = $this->ids['anna']['verhuizing'];
        $row = ['verhuizing_id' => $vid, 'rol' => 'sjouwer', 'naam' => 'Kees'];
        $db->table('guest_sessions')->insert($row + ['token' => str_repeat('g', 64), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
        $db->table('guest_sessions')->insert($row + ['token' => str_repeat('v', 64), 'expires_at' => date('Y-m-d H:i:s', time() - 60)]);
        $db->table('guest_sessions')->insert($row + ['token' => str_repeat('r', 64), 'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'revoked_at' => date('Y-m-d H:i:s')]);

        $guest = $this->actAs([Access::GUEST_COOKIE => str_repeat('g', 64)]);
        $this->assertSame($vid, $guest->verhuizingId());
        $this->assertSame('Kees', $guest->naam());
        $this->assertTrue($guest->can('sjouwer'));
        $this->assertFalse($guest->can('helper'));
        $this->assertNull($guest->user());
        $this->assertFalse($guest->switchTo($this->ids['bert']['verhuizing']));
        $this->assertCount(1, (new BoxModel())->findAll());

        $this->assertFalse($this->actAs([Access::GUEST_COOKIE => str_repeat('v', 64)])->isAuthenticated(), 'verlopen');
        $this->assertFalse($this->actAs([Access::GUEST_COOKIE => str_repeat('r', 64)])->isAuthenticated(), 'ingetrokken');
    }

    public function testRemovedMemberLosesAccess(): void
    {
        db_connect()->table('memberships')->where('user_id', $this->ids['anna']['user'])->delete();
        $access = $this->actAsAnna();

        $this->assertNotNull($access->user());
        $this->assertNull($access->verhuizingId());
    }
}
