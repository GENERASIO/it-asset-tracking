<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportGlpiAssets extends Command
{
    protected $signature = 'import:glpi {file=storage/app/imports/glpi.csv} {--dry-run}';
    protected $description = 'Import data aset dari export GLPI (CSV)';

    private $typeMap = [
        'Notebook' => 'Laptop',
        'Desktop' => 'Desktop',
        'All in One' => 'All in One',
        'Mini PC' => 'Mini PC',
        'POS' => 'POS',
    ];

    public function handle()
    {
        $path = base_path($this->argument('file'));
        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: $path");
            return 1;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('=== MODE DRY-RUN: tidak ada data yang benar-benar disimpan ===');
        }

        $handle = fopen($path, 'r');
        // Skip BOM kalau ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }
            $rows[] = array_combine($header, $row);
        }
        fclose($handle);

        $this->info("Total baris ditemukan: " . count($rows));

        $stats = [
            'locations_created' => 0,
            'categories_created' => 0,
            'users_created' => 0,
            'assets_created' => 0,
            'skipped' => 0,
        ];

        // Cache supaya tidak query berulang
        $locationCache = [];
        $categoryCache = [];
        $userCache = [];

        foreach ($rows as $i => $row) {
            $name = trim($row['Name'] ?? '');
            if (empty($name)) {
                $stats['skipped']++;
                continue;
            }

            // === LOKASI ===
            // location_id wajib diisi (kolom NOT NULL di tabel assets), jadi baris tanpa
            // data Lokasi tetap harus dipetakan ke suatu Location — pakai fallback "Tidak Diketahui".
            $rawLocation = trim($row['Locations'] ?? '');
            $locationName = !empty($rawLocation) ? str_replace(' > ', ' - ', $rawLocation) : 'Tidak Diketahui';
            if (!array_key_exists($locationName, $locationCache)) {
                $location = Location::where('name', $locationName)->first();
                if (!$location) {
                    $code = $this->generateCode($locationName, 'location');
                    if (!$dryRun) {
                        $location = Location::create([
                            'name' => $locationName,
                            'code' => $code,
                            'address' => null,
                        ]);
                    }
                    $stats['locations_created']++;
                }
                $locationCache[$locationName] = $location?->id;
            }
            $locationId = $locationCache[$locationName];

            // === KATEGORI ===
            $rawType = trim($row['Types'] ?? '');
            $categoryName = $this->typeMap[$rawType] ?? ($rawType ?: 'Lainnya');
            if (!array_key_exists($categoryName, $categoryCache)) {
                $category = Category::where('name', $categoryName)->first();
                if (!$category) {
                    $code = $this->generateCode($categoryName, 'category');
                    if (!$dryRun) {
                        $category = Category::create([
                            'name' => $categoryName,
                            'code' => $code,
                        ]);
                    }
                    $stats['categories_created']++;
                }
                $categoryCache[$categoryName] = $category?->id;
            }
            $categoryId = $categoryCache[$categoryName];

            // === USER (pemegang aset) ===
            if (!array_key_exists($name, $userCache)) {
                $user = User::where('name', $name)->first();
                if (!$user) {
                    $email = $this->generateEmail($name);
                    if (!$dryRun) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make(Str::random(16)),
                            'role' => 'user',
                            'location_id' => $locationId,
                        ]);
                        $user->forceFill(['email_verified_at' => now()])->save();
                    }
                    $stats['users_created']++;
                }
                $userCache[$name] = $user?->id;
            }
            $userId = $userCache[$name];

            // === ASET ===
            $manufacturer = trim($row['Manufacturers'] ?? '');
            $model = trim($row['Model'] ?? '');
            $assetName = trim("$manufacturer $model") ?: ($categoryName . ' - ' . $name);

            $specParts = array_filter([
                $row['Components - Processors'] ?? null,
                !empty($row['Components - Memory']) ? 'RAM: ' . $row['Components - Memory'] : null,
                !empty($row['Components - Drive Type']) ? 'Storage: ' . $row['Components - Drive Type'] : null,
                !empty($row['Comments']) ? 'Catatan: ' . $row['Comments'] : null,
            ]);
            $specification = implode(' | ', $specParts) ?: null;

            $status = $userId ? 'in_use' : 'available';

            if (!$dryRun) {
                $categoryCode = $categoryId ? Category::find($categoryId)->code : 'XXX';
                $locationCode = $locationId ? Location::find($locationId)->code : 'XXX';
                $prefix = strtoupper($categoryCode . '-' . $locationCode);

                $lastNumber = Asset::where('asset_code', 'like', $prefix . '-%')
                    ->selectRaw('MAX(CAST(SUBSTRING_INDEX(asset_code, "-", -1) AS UNSIGNED)) as max_num')
                    ->value('max_num') ?? 0;
                $assetCode = $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                Asset::create([
                    'asset_code' => $assetCode,
                    'name' => $assetName,
                    'category_id' => $categoryId,
                    'brand' => $manufacturer ?: null,
                    'model' => $model ?: null,
                    'serial_number' => trim($row['Serial Number'] ?? '') ?: null,
                    'specification' => $specification,
                    'location_id' => $locationId,
                    'assigned_to' => $userId,
                    'status' => $status,
                ]);
            }
            $stats['assets_created']++;
        }

        $this->newLine();
        $this->info('=== HASIL IMPORT ===');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Lokasi baru dibuat', $stats['locations_created']],
            ['Kategori baru dibuat', $stats['categories_created']],
            ['User baru dibuat', $stats['users_created']],
            ['Aset diimport', $stats['assets_created']],
            ['Baris dilewati (nama kosong)', $stats['skipped']],
        ]);

        if ($dryRun) {
            $this->warn('Ini baru DRY-RUN. Jalankan tanpa --dry-run untuk benar-benar menyimpan data.');
        }

        return 0;
    }

    private function generateCode($name, $type)
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
        $base = strtoupper(substr($clean, 0, $type === 'category' ? 3 : 4));
        $code = $base;
        $counter = 1;
        $model = $type === 'category' ? Category::class : Location::class;
        while ($model::where('code', $code)->exists()) {
            $counter++;
            $code = $base . $counter;
        }
        return $code;
    }

    private function generateEmail($name)
    {
        $slug = Str::slug($name, '.');
        $email = "$slug@yaygroup.internal";
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $counter++;
            $email = "$slug$counter@yaygroup.internal";
        }
        return $email;
    }
}
