<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tampilkan_notifikasi_dan_aktivitas_perangkat_baru(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $cat = Category::create(['name' => 'Lainnya', 'code' => 'LAI']);
        $loc = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK']);

        $asset = Asset::create([
            'asset_code' => 'LAI-TDK-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => $cat->id, 'location_id' => $loc->id, 'status' => 'in_use',
            'serial_number' => 'BV83JK3', 'last_seen_at' => now(),
        ]);

        AssetLog::create([
            'asset_id' => $asset->id, 'action' => 'agent_register',
            'status_after' => 'in_use', 'notes' => 'Perangkat baru didaftarkan otomatis oleh agent',
            'handled_by' => $staff->id,
        ]);

        $res = $this->actingAs($staff)->get(route('dashboard'))->assertOk();

        // Notifikasi lonceng: perangkat baru.
        $res->assertSee('Perangkat baru terdaftar');
        // Aktivitas terbaru: label pendaftaran via agent.
        $res->assertSee('mendaftarkan perangkat baru (via agent)');
    }
}
