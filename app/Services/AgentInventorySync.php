<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

/**
 * Menyimpan daftar aplikasi & status lisensi yang dikirim agent saat check-in.
 *
 * Data dari agent diperlakukan longgar: item yang aneh dilewati dan teks kepanjangan
 * dipotong, bukan ditolak - supaya satu nama aplikasi yang tidak biasa tidak membuat
 * check-in laptop itu gagal terus.
 */
class AgentInventorySync
{
    // LicenseStatus dari SoftwareLicensingProduct (Windows SPP).
    private const SPP_STATUS = [
        0 => 'unlicensed',
        1 => 'licensed',
        2 => 'grace', // out-of-box grace
        3 => 'grace', // out-of-tolerance grace
        4 => 'grace', // non-genuine grace
        5 => 'notification',
        6 => 'grace', // extended grace
    ];

    public function sync(Asset $asset, array $data): void
    {
        $touched = false;

        if (array_key_exists('crack_indicators', $data)) {
            $indicators = collect($data['crack_indicators'] ?? [])
                ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                ->map(fn ($item) => mb_substr(trim((string) $item), 0, 255))
                ->unique()
                ->values()
                ->all();

            $asset->crack_indicators = $indicators ?: null;
            $touched = true;
        }

        if (array_key_exists('software', $data)) {
            $this->syncSoftware($asset, $data['software'] ?? []);
            $touched = true;
        }

        if (array_key_exists('licenses', $data)) {
            $this->syncLicenses($asset, $data['licenses'] ?? [], $asset->crack_indicators ?? []);
            $touched = true;
        }

        if ($touched) {
            $asset->inventory_synced_at = now();
            $asset->save();
        }
    }

    private function syncSoftware(Asset $asset, array $items): void
    {
        $now = now();

        $rows = collect($items)
            ->filter(fn ($item) => is_array($item) && is_scalar($item['name'] ?? null) && trim((string) $item['name']) !== '')
            ->map(fn ($item) => [
                'asset_id' => $asset->id,
                'name' => mb_substr(trim((string) $item['name']), 0, 255),
                'version' => self::text($item['version'] ?? null, 100),
                'publisher' => self::text($item['publisher'] ?? null, 255),
                'installed_on' => self::installDate($item['installed_on'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->unique(fn ($row) => mb_strtolower($row['name']).'|'.$row['version'])
            ->values();

        $asset->software()->delete();

        foreach ($rows->chunk(500) as $chunk) {
            $asset->software()->getRelated()->newQuery()->insert($chunk->all());
        }
    }

    private function syncLicenses(Asset $asset, array $items, array $crackIndicators): void
    {
        $allowedKms = array_map('strtolower', config('licenses.allowed_kms_hosts', []));

        $asset->licenses()->delete();

        foreach ($items as $item) {
            if (! is_array($item) || ! in_array($item['product'] ?? null, ['office', 'windows'], true)) {
                continue;
            }

            $name = self::text($item['name'] ?? null, 255);
            if (! $name) {
                continue;
            }

            $channel = self::text($item['channel'] ?? null, 50);
            $kmsHost = self::text($item['kms_host'] ?? null, 255);
            $graceMinutes = is_numeric($item['grace_minutes'] ?? null) ? (int) $item['grace_minutes'] : 0;

            if (($item['status'] ?? null) === 'subscription') {
                $status = 'subscription';
            } elseif (is_numeric($item['status_code'] ?? null)) {
                $status = self::SPP_STATUS[(int) $item['status_code']] ?? 'unlicensed';
            } else {
                $status = 'unlicensed';
            }

            $reasons = [];

            // Aktivasi KMS (kanal Volume:GVLK) hanya sah lewat server KMS milik perusahaan
            // sendiri. Tanpa Volume License, KMS hampir selalu berarti tool aktivasi ilegal.
            if ($channel && str_contains(strtoupper($channel), 'GVLK')) {
                $hostKey = strtolower(explode(':', (string) $kmsHost)[0]);
                if (! $kmsHost || ! in_array($hostKey, $allowedKms, true)) {
                    $reasons[] = 'Aktivasi KMS ke server di luar daftar resmi perusahaan ('.($kmsHost ?: 'server tidak diketahui').')';
                }
            }

            if ($crackIndicators) {
                $reasons[] = 'Ditemukan jejak tool aktivasi ilegal: '.implode(', ', $crackIndicators);
            }

            $asset->licenses()->create([
                'product' => $item['product'],
                'name' => $name,
                'description' => self::text($item['description'] ?? null, 255),
                'channel' => $channel,
                'status' => $status,
                'partial_key' => self::text($item['partial_key'] ?? null, 10),
                'kms_host' => $kmsHost,
                // GracePeriodRemaining (menit) = sisa masa aktivasi untuk KMS / sisa masa tenggang.
                'expires_at' => $graceMinutes > 0 ? now()->addMinutes($graceMinutes) : null,
                'suspected' => (bool) $reasons,
                'suspected_reason' => $reasons ? mb_substr(implode('. ', $reasons), 0, 500) : null,
            ]);
        }
    }

    private static function text($value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private static function installDate($value): ?string
    {
        // Registry Windows menyimpan InstallDate sebagai "yyyymmdd".
        if (! is_scalar($value) || ! preg_match('/^(\d{4})(\d{2})(\d{2})$/', (string) $value, $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            ? Carbon::create((int) $m[1], (int) $m[2], (int) $m[3])->toDateString()
            : null;
    }
}
