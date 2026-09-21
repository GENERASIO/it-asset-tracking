<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', $this->buildReportData());
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('reports.pdf', $this->buildReportData());

        return $pdf->download('laporan-aset-' . date('Y-m-d') . '.pdf');
    }

    protected function buildReportData(): array
    {
        $assetsPerCategory = Category::withCount('assets')->orderBy('name')->get();
        $assetsPerLocation = Location::withCount('assets')->orderBy('name')->get();
        $assetsPerStatus = Asset::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        $totalAssetValue = Asset::sum('purchase_price');

        $monthlyPurchases = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $total = Asset::whereYear('purchase_date', $month->year)
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
            'totalAssets' => Asset::count(),
            'totalCategories' => $assetsPerCategory->count(),
            'totalLocations' => $assetsPerLocation->count(),
        ];
    }
}
