<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Support\DeviceAge;
use App\Support\ReportInsights;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', $this->buildReportData($request));
    }

    public function exportPdf(Request $request)
    {
        $pdf = Pdf::loadView('reports.pdf', $this->buildReportData($request));

        return $pdf->download('laporan-aset-' . date('Y-m-d') . '.pdf');
    }

    protected function buildReportData(Request $request): array
    {
        $dateField = in_array($request->input('date_field'), ['purchase_date', 'created_at'])
            ? $request->input('date_field')
            : 'created_at';

        $filters = [
            'date_field' => $dateField,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'category_id' => $request->input('category_id'),
            'location_id' => $request->input('location_id'),
            'status' => $request->input('status'),
        ];

        $assetsPerCategory = Category::withCount(['assets' => fn ($q) => $this->applyAssetFilters($q, $filters)])
            ->orderBy('name')->get();
        $assetsPerLocation = Location::withCount(['assets' => fn ($q) => $this->applyAssetFilters($q, $filters)])
            ->orderBy('name')->get();
        $assetsPerStatus = $this->applyAssetFilters(Asset::query(), $filters)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        $totalAssetValue = $this->applyAssetFilters(Asset::query(), $filters)->sum('purchase_price');
        $estimatedPriceCount = $this->applyAssetFilters(Asset::query(), $filters)
            ->whereNotNull('purchase_price')->where('purchase_price_estimated', true)->count();
        $totalAssets = $this->applyAssetFilters(Asset::query(), $filters)->count();

        // Tren bulanan sengaja tidak ikut filter rentang tanggal (supaya sumbu waktunya tetap utuh),
        // tapi tetap ikut filter kategori/lokasi/status.
        $trendFilters = collect($filters)->except(['date_from', 'date_to'])->toArray();
        $monthlyPurchases = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $total = $this->applyAssetFilters(Asset::query(), $trendFilters)
                ->whereYear('purchase_date', $month->year)
                ->whereMonth('purchase_date', $month->month)
                ->count();

            $monthlyPurchases->push([
                'label' => $month->translatedFormat('M Y'),
                'total' => $total,
            ]);
        }

        return [
            ...$this->buildAnalysis($filters),
            'assetsPerCategory' => $assetsPerCategory,
            'assetsPerLocation' => $assetsPerLocation,
            'assetsPerStatus' => $assetsPerStatus,
            'totalAssetValue' => $totalAssetValue,
            'estimatedPriceCount' => $estimatedPriceCount,
            'monthlyPurchases' => $monthlyPurchases,
            'totalAssets' => $totalAssets,
            'totalCategories' => Category::count(),
            'totalLocations' => Location::count(),
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ];
    }

    /**
     * Analisis tambahan: umur perangkat, nilai per lokasi, perkiraan nilai buku,
     * kelengkapan data, dan biaya maintenance per model. Semua mengikuti filter laporan.
     */
    protected function buildAnalysis(array $filters): array
    {
        $assets = $this->applyAssetFilters(Asset::query(), $filters)->with('location')->get();
        $total = max($assets->count(), 1);

        // Umur perangkat (dari generasi prosesor).
        $ageGroups = collect(DeviceAge::LABELS)->map(function ($label, $key) use ($assets) {
            $group = $assets->filter(fn ($a) => DeviceAge::forAsset($a) === $key);

            return ['key' => $key, 'label' => $label, 'count' => $group->count(), 'value' => (int) $group->sum('purchase_price')];
        })->values();

        // Nilai per lokasi, 10 terbesar.
        $valueByLocation = $assets->groupBy(fn ($a) => $a->location->name ?? 'Tanpa lokasi')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count(), 'value' => (int) $group->sum('purchase_price')])
            ->sortByDesc('value')
            ->values();

        // Nilai sekarang: pakai angka tim aset (current_value) kalau ada; selebihnya
        // perkiraan garis lurus 4 tahun (komputer = harta Kelompok 1 untuk pajak),
        // untuk aset yang punya tanggal beli dan harga.
        $dated = $assets->filter(fn ($a) => ! is_null($a->current_value) || ($a->purchase_date && $a->purchase_price));
        $bookValue = (int) $dated->sum(function ($a) {
            if (! is_null($a->current_value)) {
                return $a->current_value;
            }

            $years = $a->purchase_date->diffInDays(now()) / 365.25;

            return $a->purchase_price * max(0, 1 - $years / 4);
        });

        // Kelengkapan data.
        $completeness = collect([
            ['label' => 'Lokasi diketahui', 'filled' => $assets->filter(fn ($a) => $a->location && $a->location->name !== 'Tidak Diketahui')->count()],
            ['label' => 'Ada pemegang', 'filled' => $assets->whereNotNull('assigned_to')->count()],
            ['label' => 'Serial number', 'filled' => $assets->filter(fn ($a) => filled($a->serial_number))->count()],
            ['label' => 'Prosesor diketahui', 'filled' => $assets->filter(fn ($a) => DeviceAge::forAsset($a) !== 'unknown')->count()],
            ['label' => 'Harga beli asli', 'filled' => $assets->filter(fn ($a) => $a->purchase_price && ! $a->purchase_price_estimated)->count()],
            ['label' => 'Tanggal beli', 'filled' => $assets->whereNotNull('purchase_date')->count()],
            ['label' => 'Tanggal garansi', 'filled' => $assets->whereNotNull('warranty_expired_at')->count()],
        ])->map(fn ($row) => $row + ['percent' => (int) round($row['filled'] / $total * 100)]);

        // Biaya maintenance per model.
        $maintenanceByModel = MaintenanceLog::with('asset')
            ->whereIn('asset_id', $assets->pluck('id'))
            ->get()
            ->filter(fn ($log) => $log->asset)
            ->groupBy(fn ($log) => trim(($log->asset->brand ?? '').' '.($log->asset->model ?? '')) ?: $log->asset->name)
            ->map(fn ($logs, $model) => [
                'model' => $model,
                'count' => $logs->count(),
                'assets' => $logs->pluck('asset_id')->unique()->count(),
                'cost' => (int) $logs->sum('cost'),
            ])
            ->sortByDesc('cost')
            ->take(10)
            ->values();

        $analysis = [
            'ageGroups' => $ageGroups,
            'valueByLocation' => $valueByLocation,
            'bookValue' => $bookValue,
            'bookValueCoverage' => $dated->count(),
            'bookValuePurchase' => (int) $dated->sum('purchase_price'),
            'completeness' => $completeness,
            'dataScore' => (int) round($completeness->avg('percent')),
            'maintenanceByModel' => $maintenanceByModel,
            'spareCount' => $assets->where('status', 'available')->count(),
        ];

        return $analysis + ['insights' => ReportInsights::build($assets, $analysis)];
    }

    protected function applyAssetFilters($query, array $filters)
    {
        $dateField = in_array($filters['date_field'] ?? null, ['purchase_date', 'created_at'])
            ? $filters['date_field']
            : 'created_at';

        return $query
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate($dateField, '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate($dateField, '<=', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }
}
