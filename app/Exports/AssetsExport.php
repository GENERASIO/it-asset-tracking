<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Asset::with(['category', 'location', 'assignedUser'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Kode Aset',
            'Nama',
            'Kategori',
            'Merk',
            'Model',
            'Serial Number',
            'Lokasi',
            'Status',
            'Dipegang Oleh',
            'Tanggal Pembelian',
            'Harga Beli',
            'Garansi Sampai',
            'Spesifikasi',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_code,
            $asset->name,
            $asset->category->name ?? '-',
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->location->name ?? '-',
            $asset->status,
            $asset->assignedUser->name ?? '-',
            optional($asset->purchase_date)->format('Y-m-d'),
            $asset->purchase_price,
            optional($asset->warranty_expired_at)->format('Y-m-d'),
            $asset->specification,
        ];
    }
}
