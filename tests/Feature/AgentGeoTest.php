<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentGeoTest extends TestCase
{
    use RefreshDatabase;

    private function asset(): Asset
    {
        return Asset::create([
            'asset_code' => 'LAI-X-0001', 'name' => 'Laptop',
            'category_id' => Category::create(['name' => 'Lainnya', 'code' => 'LAI'])->id,
            'location_id' => Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK'])->id,
            'status' => 'in_use', 'serial_number' => 'SN-G',
        ]);
    }

    public function test_lokasi_di_luar_indonesia_ditolak(): void
    {
        $asset = $this->asset();
        [, $token] = AgentToken::generate('T', User::factory()->create()->id);

        // Koordinat Denmark (VPN / geolokasi IP salah).
        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-G', 'hostname' => 'H', 'os_name' => 'Windows',
            'latitude' => 55.3021604, 'longitude' => 10.4925624, 'location_accuracy_m' => 108,
        ])->assertOk();

        $asset->refresh();
        $this->assertNull($asset->latitude);
        $this->assertNull($asset->geo_captured_at);
        $this->assertSame('outside_region', $asset->location_status);
    }

    public function test_lokasi_di_indonesia_diterima(): void
    {
        $asset = $this->asset();
        [, $token] = AgentToken::generate('T', User::factory()->create()->id);

        // Koordinat Surabaya.
        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-G', 'hostname' => 'H', 'os_name' => 'Windows',
            'latitude' => -7.3160, 'longitude' => 112.7680, 'location_accuracy_m' => 30,
        ])->assertOk();

        $asset->refresh();
        $this->assertNotNull($asset->latitude);
        $this->assertNotNull($asset->geo_captured_at);
    }
}
