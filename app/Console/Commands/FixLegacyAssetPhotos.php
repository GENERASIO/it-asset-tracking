<?php

namespace App\Console\Commands;

use App\Models\AssetPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixLegacyAssetPhotos extends Command
{
    protected $signature = 'assets:fix-legacy-photos';

    protected $description = 'Pindahkan file foto aset lama (sebelum fitur multi-foto) dari disk public ke disk assets';

    public function handle(): int
    {
        $photos = AssetPhoto::with('asset')->get();
        $fixed = 0;
        $missing = 0;
        $skipped = 0;
        $affectedAssets = collect();

        foreach ($photos as $photo) {
            if (Storage::disk('assets')->exists($photo->path)) {
                $skipped++;
                continue;
            }

            if (! Storage::disk('public')->exists($photo->path)) {
                $this->warn("File tidak ditemukan di kedua disk: {$photo->path} (asset_photo id {$photo->id})");
                $missing++;
                continue;
            }

            $contents = Storage::disk('public')->get($photo->path);
            $newPath = basename($photo->path);

            Storage::disk('assets')->put($newPath, $contents);
            $photo->update(['path' => $newPath]);

            if ($photo->asset) {
                $affectedAssets->put($photo->asset->id, $photo->asset);
            }

            $fixed++;
        }

        $affectedAssets->each->syncPrimaryPhoto();

        $this->info("Selesai. Diperbaiki: {$fixed}, sudah benar: {$skipped}, tidak ditemukan: {$missing}.");

        return self::SUCCESS;
    }
}
