<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\MaintenanceLog;
use App\Models\SoftwareEntitlement;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            $view->with('navNotifications', $this->buildNavNotifications());
        });
    }

    protected function buildNavNotifications()
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['super_admin', 'it_staff'])) {
            return collect();
        }

        $warrantyAlerts = Asset::whereNotNull('warranty_expired_at')
            ->where('warranty_expired_at', '<=', now()->addDays(30))
            ->orderBy('warranty_expired_at')
            ->take(10)
            ->get()
            ->map(function ($asset) {
                $isPast = $asset->warranty_expired_at->isPast();

                return [
                    'severity' => $isPast ? 'danger' : 'warning',
                    'title' => $asset->asset_code.' — '.($asset->name ?: 'Aset'),
                    'subtitle' => ($isPast ? 'Garansi berakhir ' : 'Garansi akan berakhir ').$asset->warranty_expired_at->diffForHumans(),
                    'url' => route('assets.show', $asset),
                ];
            });

        $overdueMaintenances = MaintenanceLog::where('status', '!=', 'done')
            ->where('created_at', '<=', now()->subDays(7))
            ->with('asset')
            ->orderBy('created_at')
            ->take(10)
            ->get()
            ->filter(fn ($log) => $log->asset)
            ->map(fn ($log) => [
                'severity' => 'warning',
                'title' => $log->asset->asset_code.' — '.($log->asset->name ?: 'Aset'),
                'subtitle' => 'Maintenance tertunda sejak '.$log->created_at->diffForHumans(),
                'url' => route('assets.show', $log->asset),
            ]);

        $reminderDays = config('licenses.reminder_days');

        // Hanya lisensi resmi - yang terindikasi tidak resmi sengaja tidak diingatkan.
        $assetLicenseAlerts = AssetLicense::needsAttention($reminderDays)
            ->with('asset')
            ->orderByRaw('expires_at IS NULL, expires_at')
            ->take(10)
            ->get()
            ->filter(fn ($license) => $license->asset)
            ->map(function ($license) {
                $expired = $license->expires_at?->isPast() || in_array($license->status, ['notification', 'unlicensed']);

                return [
                    'severity' => $expired ? 'danger' : 'warning',
                    'title' => $license->asset->asset_code.' — Lisensi '.ucfirst($license->product),
                    'subtitle' => $license->expires_at && ! $expired
                        ? 'Lisensi habis '.$license->expires_at->diffForHumans()
                        : 'Status lisensi: '.$license->status_label,
                    'url' => route('assets.show', $license->asset),
                ];
            });

        $entitlementAlerts = SoftwareEntitlement::whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($reminderDays))
            ->orderBy('expires_at')
            ->take(10)
            ->get()
            ->map(function ($entitlement) {
                $isPast = $entitlement->expires_at->endOfDay()->isPast();

                return [
                    'severity' => $isPast ? 'danger' : 'warning',
                    'title' => 'Lisensi '.$entitlement->name,
                    'subtitle' => ($isPast ? 'Sudah habis ' : 'Perlu diperpanjang, habis ').$entitlement->expires_at->endOfDay()->diffForHumans(),
                    'url' => route('software-licenses.index'),
                ];
            });

        // Perangkat baru dari agent yang belum ditinjau (belum ada pemegang & lokasi
        // masih "Tidak Diketahui"). Ditaruh paling atas supaya mudah dikenali.
        $newDevices = Asset::whereNotNull('last_seen_at')
            ->whereNull('assigned_to')
            ->whereHas('location', fn ($q) => $q->where('name', 'Tidak Diketahui'))
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn ($asset) => [
                'severity' => 'info',
                'title' => $asset->asset_code.' — '.($asset->name ?: 'Perangkat baru'),
                'subtitle' => 'Perangkat baru terdaftar '.$asset->created_at->diffForHumans().' · perlu ditinjau',
                'url' => route('asset-merge.create', $asset),
            ]);

        $alerts = $warrantyAlerts->concat($overdueMaintenances)
            ->concat($assetLicenseAlerts)
            ->concat($entitlementAlerts)
            ->sortBy(fn ($notif) => $notif['severity'] === 'danger' ? 0 : 1)
            ->values();

        return $newDevices->concat($alerts)->values();
    }
}
