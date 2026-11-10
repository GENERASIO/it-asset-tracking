<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Simpan/baca gambar tanda tangan digital (PNG dari canvas) di disk privat. */
class SignatureImage
{
    public const DISK = 'local';

    /** Simpan data URL PNG ke $dir. Null kalau bukan PNG valid atau kosong (tanpa coretan). */
    public static function store(string $dataUrl, string $dir): ?string
    {
        if (! preg_match('#^data:image/png;base64,#', $dataUrl)) {
            return null;
        }

        $raw = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        if ($raw === false || strlen($raw) < 100 || strlen($raw) > 800000) {
            return null;
        }

        if (! self::hasInk($raw)) {
            return null;
        }

        $path = $dir.'/'.Str::random(40).'.png';
        Storage::disk(self::DISK)->put($path, $raw);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Data URI base64 untuk ditempel di PDF (dompdf). */
    public static function dataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? 'data:image/png;base64,'.base64_encode($disk->get($path)) : null;
    }

    /** true kalau gambar punya cukup piksel bertinta (bukan kosong). Lewati kalau GD tak ada. */
    private static function hasInk(string $png): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return true;
        }

        $img = @imagecreatefromstring($png);
        if (! $img) {
            return true;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $stepX = max(1, (int) ($w / 150));
        $stepY = max(1, (int) ($h / 80));
        $ink = 0;

        for ($x = 0; $x < $w; $x += $stepX) {
            for ($y = 0; $y < $h; $y += $stepY) {
                $alpha = (imagecolorat($img, $x, $y) & 0x7F000000) >> 24; // 0 = opaque, 127 = transparan
                if ($alpha < 100) {
                    $ink++;
                }
            }
        }

        imagedestroy($img);

        return $ink >= 12;
    }
}
