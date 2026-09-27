<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Uitnodiging om medewerker van een bedrijf te worden (whitelabel). 7 dagen, eenmalig, en
 * gebonden aan het e-mailadres: alleen het account met dat adres kan hem accepteren.
 * Werkt alleen op het subdomein van dat bedrijf.
 */
class MedewerkerUitnodigingModel extends Model
{
    public const DAGEN = 7;
    public const ROLLEN = ['planner', 'sales', 'inpakker', 'sjouwer'];

    protected $table         = 'medewerker_uitnodigingen';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['bedrijf_id', 'email', 'rol', 'token', 'created_by', 'expires_at', 'used_by', 'used_at', 'revoked_at'];

    public function createFor(int $bedrijfId, string $email, string $rol, ?int $createdBy): array
    {
        $id = $this->insert([
            'bedrijf_id' => $bedrijfId,
            'email'      => UserModel::normalizeEmail($email),
            'rol'        => in_array($rol, self::ROLLEN, true) ? $rol : 'inpakker',
            'token'      => bin2hex(random_bytes(16)),
            'created_by' => $createdBy,
            'expires_at' => date('Y-m-d H:i:s', time() + self::DAGEN * 86400),
        ], true);

        return $this->find($id);
    }

    /** Geldige uitnodiging voor het bedrijf van deze ingang, met bedrijfsnaam. */
    public function findUsable(string $token): ?array
    {
        helper('access');
        $bedrijfId = tenant()->bedrijfId();
        if ($bedrijfId === null || ! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        return $this->select('medewerker_uitnodigingen.*, bedrijven.naam AS bedrijf_naam')
            ->join('bedrijven', 'bedrijven.id = medewerker_uitnodigingen.bedrijf_id')
            ->where('medewerker_uitnodigingen.bedrijf_id', $bedrijfId)
            ->where('medewerker_uitnodigingen.token', $token)
            ->where('medewerker_uitnodigingen.used_at IS NULL')
            ->where('medewerker_uitnodigingen.revoked_at IS NULL')
            ->where('medewerker_uitnodigingen.expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public static function emailPast(array $invite, array $user): bool
    {
        return UserModel::normalizeEmail($user['email']) === $invite['email'];
    }

    /** Maakt het account medewerker. Foutmelding of null. */
    public function accept(array $invite, array $user): ?string
    {
        if (! self::emailPast($invite, $user)) {
            return 'Deze uitnodiging is voor ' . $invite['email'] . '. Log in met dat e-mailadres.';
        }

        $db       = db_connect();
        $bestaand = $db->table('bedrijf_medewerkers')->where('user_id', $user['id'])->get()->getRowArray();
        if ($bestaand && (int) $bestaand['bedrijf_id'] !== (int) $invite['bedrijf_id']) {
            return 'Dit account hoort al bij een ander bedrijf. Gebruik een ander e-mailadres.';
        }

        $db->transStart();
        if ($bestaand) {
            $db->table('bedrijf_medewerkers')->where('id', $bestaand['id'])->update(['rol' => $invite['rol'], 'actief' => 1]);
        } else {
            $db->table('bedrijf_medewerkers')->insert([
                'bedrijf_id' => $invite['bedrijf_id'],
                'user_id'    => $user['id'],
                'rol'        => $invite['rol'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->update($invite['id'], ['used_by' => $user['id'], 'used_at' => date('Y-m-d H:i:s')]);
        $db->transComplete();

        return null;
    }

    public function openFor(int $bedrijfId): array
    {
        return $this->where('bedrijf_id', $bedrijfId)
            ->where('used_at IS NULL')
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
