<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['super_admin', 'it_staff'])) {
            $stats = [
                'total' => Asset::count(),
                'available' => Asset::where('status', 'available')->count(),
                'in_use' => Asset::where('status', 'in_use')->count(),
                'maintenance' => Asset::where('status', 'maintenance')->count(),
                'broken' => Asset::where('status', 'broken')->count(),
                'retired' => Asset::where('status', 'retired')->count(),
                'categories' => Category::count(),
                'locations' => Location::count(),
            ];

            $recentAssets = Asset::with(['category', 'location'])->latest()->take(5)->get();

            $warrantySoon = Asset::whereNotNull('warranty_expired_at')
                ->whereBetween('warranty_expired_at', [now(), now()->addDays(30)])
                ->orderBy('warranty_expired_at')
                ->take(5)
                ->get();

            $warrantyAlerts = Asset::whereNotNull('warranty_expired_at')
                ->where('warranty_expired_at', '<=', now()->addDays(30))
                ->where('warranty_expired_at', '>=', now())
                ->orderBy('warranty_expired_at')
                ->get();

            $overdueMaintenances = MaintenanceLog::where('status', '!=', 'done')
                ->where('created_at', '<=', now()->subDays(7))
                ->with('asset')
                ->orderBy('created_at')
                ->get();

            return view('dashboard', compact('stats', 'recentAssets', 'warrantySoon', 'warrantyAlerts', 'overdueMaintenances'));
        }

        // Role "user" biasa: lihat aset miliknya sendiri
        $myAssets = Asset::where('assigned_to', $user->id)->with('category', 'location')->get();

        return view('dashboard', compact('myAssets'));
    }
}