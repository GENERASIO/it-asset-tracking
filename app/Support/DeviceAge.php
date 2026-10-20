<?php

namespace App\Support;

/**
 * Perkiraan umur perangkat dari generasi prosesor di kolom spesifikasi. Dipakai karena
 * tanggal pembelian sebagian besar aset tidak tercatat.
 */
class DeviceAge
{
    public const LABELS = [
        'new' => 'Baru (2023+)',
        'mid' => 'Menengah (2021–2022)',
        'old' => 'Tua (2020 ke bawah)',
        'unknown' => 'Tidak diketahui',
    ];

    public static function classify(?string $specification): string
    {
        $spec = strtoupper((string) $specification);

        if ($spec === '') {
            return 'unknown';
        }

        if (str_contains($spec, 'CORE ULTRA')) {
            return 'new';
        }

        if (preg_match('/\b(CELERON|PENTIUM|ATOM)\b/', $spec)) {
            return 'old';
        }

        if (preg_match('/RYZEN\s*\d\s*(?:PRO\s*)?(\d)\d{3}/', $spec, $m)) {
            return match (true) {
                (int) $m[1] >= 7 => 'new',
                (int) $m[1] >= 5 => 'mid',
                default => 'old',
            };
        }

        $generation = null;

        if (preg_match('/(\d{1,2})(?:TH|ND|RD|ST) GEN/', $spec, $m)) {
            $generation = (int) $m[1];
        } elseif (preg_match('/\bI[3579]-(\d{4,5})/', $spec, $m)) {
            // i3-1315U -> 13, i5-10300H -> 10, i3-8130U -> 8, i3-5005U -> 5
            $number = $m[1];
            $generation = (strlen($number) === 5 || ($number[0] === '1' && (int) $number[1] <= 4))
                ? (int) substr($number, 0, 2)
                : (int) $number[0];
        }

        return match (true) {
            $generation === null => 'unknown',
            $generation >= 13 => 'new',
            $generation >= 11 => 'mid',
            default => 'old',
        };
    }
}
