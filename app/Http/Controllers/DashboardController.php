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

            // Rincian jumlah aset per kategori + pecahan statusnya (karena tidak semua aset laptop).
            $statusKeys = ['available', 'in_use', 'maintenance', 'broken', 'retired'];
            $byCategory = Category::withCount([
                'assets',
                'assets as available_count' => fn ($q) => $q->where('status', 'available'),
                'assets as in_use_count' => fn ($q) => $q->where('status', 'in_use'),
                'assets as maintenance_count' => fn ($q) => $q->where('status', 'maintenance'),
                'assets as broken_count' => fn ($q) => $q->where('status', 'broken'),
                'assets as retired_count' => fn ($q) => $q->where('status', 'retired'),
            ])->orderByDesc('assets_count')->get();

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
                            'agent_register' => 'mendaftarkan perangkat baru (via agent)',
                            'location_update' => 'memperbarui lokasi GPS',
                            'transfer' => 'memindahkan',
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

            // Aktivitas agent: pendaftaran perangkat baru & update lokasi GPS (biar tim langsung tahu).
            $agentActivities = AssetLog::with('asset')
                ->whereIn('action', ['agent_register', 'location_update'])
                ->latest()
                ->take(8)
                ->get()
                ->filter(fn ($log) => $log->asset)
                ->map(fn ($log) => [
                    'kind' => $log->action,
                    'asset_code' => $log->asset->asset_code,
                    'asset_id' => $log->asset->id,
                    'name' => $log->asset->name,
                    'label' => $log->action === 'agent_register' ? 'Perangkat baru terdaftar' : 'Lokasi GPS diperbarui',
                    'created_at' => $log->created_at,
                ])
                ->values();

            return view('dashboard', compact('stats', 'byCategory', 'recentAssets', 'warrantySoon', 'warrantyAlerts', 'overdueMaintenances', 'recentActivities', 'agentActivities'));
        }

        // Role "user" biasa: lihat aset miliknya sendiri
        $myAssets = Asset::where('assigned_to', $user->id)->with('category', 'location')->get();

        return view('dashboard', compact('myAssets'));
    }
}