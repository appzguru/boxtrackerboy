<?php

namespace App\Models;

use CodeIgniter\Model;

/** Uitnodigingslinks voor leden met een account (handoff.md §3.2): 7 dagen, eenmalig. */
class InviteModel extends Model
{
    public const DAGEN = 7;

    protected $table         = 'invites';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['verhuizing_id', 'token', 'rol', 'created_by', 'expires_at', 'used_by', 'used_at', 'revoked_at'];

    public function createFor(int $verhuizingId, string $rol, int $createdBy): array
    {
        $token = bin2hex(random_bytes(16));
        $id    = $this->insert([
            'verhuizing_id' => $verhuizingId,
            'token'         => $token,
            'rol'           => $rol === 'admin' ? 'admin' : 'helper',
            'created_by'    => $createdBy,
            'expires_at'    => date('Y-m-d H:i:s', time() + self::DAGEN * 86400),
        ], true);

        return $this->find($id);
    }

    /** Geldige (niet gebruikte, ingetrokken of verlopen) uitnodiging, met naam van verhuizing en uitnodiger. */
    public function findUsable(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        return $this->select('invites.*, verhuizingen.naam AS verhuizing_naam, users.naam AS door_naam')
            ->join('verhuizingen', 'verhuizingen.id = invites.verhuizing_id')
            ->join('users', 'users.id = invites.created_by', 'left')
            ->where('invites.token', $token)
            ->where('invites.used_at IS NULL')
            ->where('invites.revoked_at IS NULL')
            ->where('invites.expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function accept(array $invite, int $userId): void
    {
        (new VerhuizingModel())->addMember((int) $invite['verhuizing_id'], $userId, $invite['rol']);
        $this->update($invite['id'], ['used_by' => $userId, 'used_at' => date('Y-m-d H:i:s')]);
    }

    public function openFor(int $verhuizingId): array
    {
        return $this->where('verhuizing_id', $verhuizingId)
            ->where('used_at IS NULL')
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
