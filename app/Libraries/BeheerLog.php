<?php

namespace App\Libraries;

/**
 * Wat een global-admin deed (tabel beheer_log): bedrijven aanmaken en blokkeren,
 * uitnodigen, meekijken. Voor support en de verwerkersovereenkomst.
 */
class BeheerLog
{
    public static function schrijf(string $actie, ?int $bedrijfId = null, ?int $verhuizingId = null, ?string $detail = null): void
    {
        helper('access');
        db_connect()->table('beheer_log')->insert([
            'user_id'       => access()->user()['id'] ?? null,
            'bedrijf_id'    => $bedrijfId,
            'verhuizing_id' => $verhuizingId,
            'actie'         => $actie,
            'detail'        => $detail !== null ? mb_substr($detail, 0, 200) : null,
            'op'            => date('Y-m-d H:i:s'),
        ]);
    }

    public static function voorBedrijf(int $bedrijfId, int $limit = 30): array
    {
        return db_connect()->table('beheer_log')
            ->select('beheer_log.*, users.naam AS door_naam, verhuizingen.naam AS verhuizing_naam')
            ->join('users', 'users.id = beheer_log.user_id', 'left')
            ->join('verhuizingen', 'verhuizingen.id = beheer_log.verhuizing_id', 'left')
            ->where('beheer_log.bedrijf_id', $bedrijfId)
            ->orderBy('beheer_log.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }
}
