<?php

namespace App\Models;

use CodeIgniter\Model;

class VerhuizingModel extends Model
{
    protected $table         = 'verhuizingen';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['naam', 'created_by'];

    /** Nieuwe verhuizing met de maker als admin. */
    public function createFor(int $userId, string $naam): int
    {
        $db = db_connect();
        $db->transStart();
        $id = (int) $this->insert(['naam' => $naam, 'created_by' => $userId], true);
        $db->table('memberships')->insert([
            'verhuizing_id' => $id,
            'user_id'       => $userId,
            'rol'           => 'admin',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        return $id;
    }

    public function countForUser(int $userId): int
    {
        return db_connect()->table('memberships')->where('user_id', $userId)->countAllResults();
    }

    public function members(int $verhuizingId): array
    {
        return db_connect()->table('memberships')
            ->select('memberships.id, memberships.rol, memberships.created_at, users.id AS user_id, users.naam, users.email')
            ->join('users', 'users.id = memberships.user_id')
            ->where('memberships.verhuizing_id', $verhuizingId)
            ->orderBy('memberships.rol', 'ASC')
            ->orderBy('users.naam', 'ASC')
            ->get()->getResultArray();
    }

    public function adminCount(int $verhuizingId): int
    {
        return db_connect()->table('memberships')
            ->where('verhuizing_id', $verhuizingId)
            ->where('rol', 'admin')
            ->countAllResults();
    }

    /** Lidmaatschap toevoegen (of rol bijwerken als iemand al lid is — nooit degraderen). */
    public function addMember(int $verhuizingId, int $userId, string $rol): void
    {
        $db       = db_connect();
        $existing = $db->table('memberships')->where('verhuizing_id', $verhuizingId)->where('user_id', $userId)->get()->getRowArray();

        if (! $existing) {
            $db->table('memberships')->insert([
                'verhuizing_id' => $verhuizingId,
                'user_id'       => $userId,
                'rol'           => $rol,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } elseif ($existing['rol'] === 'helper' && $rol === 'admin') {
            $db->table('memberships')->where('id', $existing['id'])->update(['rol' => 'admin']);
        }
    }
}
