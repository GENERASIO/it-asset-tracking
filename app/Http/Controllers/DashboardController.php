<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
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

            $assetLogActivities = AssetLog::with(['asset', 'handledBy'])
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => 'asset_log',
                        'user_name' => optional($log->handledBy)->name ?? 'System',
                        'action' => $log->action,
                        'asset_code' => optional($log->asset)->asset_code ?? '-',
                        'description' => match ($log->action) {
                            'check_out' => 'melakukan checkout',
                            'check_in' => 'melakukan checkin',
                            'transfer' => 'memindahkan',
                            'status_change' => 'mengubah status',
                            default => $log->action,
                        },
                        'created_at' => $log->created_at,
                    ];
                });

            $maintenanceActivities = MaintenanceLog::with(['asset', 'createdBy'])
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => 'maintenance',
                        'user_name' => optional($log->createdBy)->name ?? 'System',
                        'action' => 'maintenance',
                        'asset_code' => optional($log->asset)->asset_code ?? '-',
                        'description' => 'melaporkan maintenance pada',
                        'created_at' => $log->created_at,
                    ];
                });

            $recentActivities = $assetLogActivities->concat($maintenanceActivities)
                ->sortByDesc('created_at')
                ->take(10)
                ->values();

            return view('dashboard', compact('stats', 'recentAssets', 'warrantySoon', 'warrantyAlerts', 'overdueMaintenances', 'recentActivities'));
        }

        // Role "user" biasa: lihat aset miliknya sendiri
        $myAssets = Asset::where('assigned_to', $user->id)->with('category', 'location')->get();

        return view('dashboard', compact('myAssets'));
    }
}