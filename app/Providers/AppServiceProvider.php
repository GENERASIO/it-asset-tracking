<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\MaintenanceLog;
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

        return $warrantyAlerts->concat($overdueMaintenances)
            ->sortBy(fn ($notif) => $notif['severity'] === 'danger' ? 0 : 1)
            ->values();
    }
}
