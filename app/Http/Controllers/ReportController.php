<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
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
            'assetsPerCategory' => $assetsPerCategory,
            'assetsPerLocation' => $assetsPerLocation,
            'assetsPerStatus' => $assetsPerStatus,
            'totalAssetValue' => $totalAssetValue,
            'monthlyPurchases' => $monthlyPurchases,
            'totalAssets' => $totalAssets,
            'totalCategories' => Category::count(),
            'totalLocations' => Location::count(),
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ];
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
