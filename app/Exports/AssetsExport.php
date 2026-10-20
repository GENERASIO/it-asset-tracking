<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    // Tanpa argumen: semua aset. Dari halaman daftar aset: aset hasil filter yang sedang tampil.
    public function __construct(private ?Collection $assets = null)
    {
    }

    public function collection(): Collection
    {
        return $this->assets ?? Asset::with(['category', 'location', 'assignedUser'])->latest()->get();
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
            'Harga Estimasi',
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
            $asset->purchase_price !== null ? ($asset->purchase_price_estimated ? 'Ya' : 'Tidak') : '',
            optional($asset->warranty_expired_at)->format('Y-m-d'),
            $asset->specification,
        ];
    }
}
