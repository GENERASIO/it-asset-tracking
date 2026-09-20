<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Services\AssetCodeGenerator;
use Illuminate\Support\Collection;
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
                    'purchase_price' => $row['harga_beli'] ?? null,
                    'warranty_expired_at' => $this->parseDate($row['garansi_sampai'] ?? null),
                    'specification' => $row['spesifikasi'] ?? null,
                ]
            );
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
