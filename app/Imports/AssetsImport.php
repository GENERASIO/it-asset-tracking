<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Services\AssetCodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                continue;
            }

            $category = $this->findCategory($row['kategori'] ?? null);
            $location = $this->findLocation($row['lokasi'] ?? null);

            if (! $category || ! $location) {
                continue;
            }

            DB::transaction(function () use ($row, $name, $category, $location) {
                $assetCode = trim((string) ($row['kode_aset'] ?? ''));

                if ($assetCode === '') {
                    $assetCode = AssetCodeGenerator::generate($category->id, $location->id);
                }

                Asset::updateOrCreate(
                    ['asset_code' => $assetCode],
                    [
                        'name' => $name,
                        'category_id' => $category->id,
                        'brand' => $row['merk'] ?? null,
                        'model' => $row['model'] ?? null,
                        'serial_number' => $row['serial_number'] ?? null,
                        'location_id' => $location->id,
                        'status' => $row['status'] ?? 'available',
                        'purchase_date' => $this->parseDate($row['tanggal_pembelian'] ?? null),
                        'purchase_price' => $this->parsePrice($row['harga_beli'] ?? null),
                        'warranty_expired_at' => $this->parseDate($row['garansi_sampai'] ?? null),
                        'specification' => $row['spesifikasi'] ?? null,
                    ]
                );
            });
        }
    }

    protected function findCategory($value): ?Category
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Category::where('name', $value)->orWhere('code', $value)->first();
    }

    protected function findLocation($value): ?Location
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Location::where('name', $value)->orWhere('code', $value)->first();
    }

    /**
     * Harga dari Excel: angka biasa (10000000) atau teks berformat ("Rp 10.000.000").
     * Nilai di bawah Rp 1.000 (mis. "6,8" maksudnya 6,8 juta) dikosongkan, bukan disimpan salah.
     */
    protected function parsePrice($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $price = is_numeric($value)
            ? (int) round((float) $value)
            : (int) preg_replace('/\D/', '', (string) $value);

        return $price >= 1000 ? $price : null;
    }

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
