<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCheckinLocationTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Lainnya', 'code' => 'LN']);
        $location = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TD']);

        // Aset dibuat lebih dulu karena AssetCodeGenerator memakai SUBSTRING_INDEX (khusus
        // MySQL) yang tidak ada di SQLite yang dipakai test.
        Asset::create([
            'asset_code' => 'TEST-0001',
            'name' => 'Laptop Test',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'status' => 'in_use',
            'serial_number' => 'SN-123',
        ]);

        [, $this->token] = AgentToken::generate('Test', User::factory()->create()->id);
    }

    private function checkin(array $extra = [])
    {
        return $this->withToken($this->token)->postJson('/api/agent/checkin', array_merge([
            'serial_number' => 'SN-123',
            'hostname' => 'LAPTOP-01',
            'os_name' => 'Windows',
        ], $extra));
    }

    public function test_checkin_menyimpan_lokasi_gps(): void
    {
        $this->checkin([
            'latitude' => -6.2087634,
            'longitude' => 106.845599,
            'location_accuracy_m' => 25,
        ])->assertOk();

        $asset = Asset::where('serial_number', 'SN-123')->first();
        $this->assertEquals(-6.2087634, (float) $asset->latitude);
        $this->assertEquals(106.845599, (float) $asset->longitude);
        $this->assertSame(25, $asset->location_accuracy_m);
        $this->assertNotNull($asset->geo_captured_at);
    }

    public function test_checkin_tanpa_lokasi_tidak_menghapus_lokasi_terakhir(): void
    {
        $this->checkin(['latitude' => -6.2, 'longitude' => 106.8])->assertOk();
        $this->checkin()->assertOk();

        $asset = Asset::where('serial_number', 'SN-123')->first();
        $this->assertEquals(-6.2, (float) $asset->latitude);
        $this->assertEquals(106.8, (float) $asset->longitude);
    }

    public function test_checkin_mengembalikan_hash_script_terbaru(): void
    {
        $this->checkin()->assertOk()->assertJson([
            'script_sha256' => hash_file('sha256', base_path('agents/windows/checkin.ps1')),
        ]);
    }

    public function test_script_agent_bisa_diunduh_dengan_token(): void
    {
        $path = base_path('agents/windows/checkin.ps1');

        $this->withToken($this->token)->get('/api/agent/script')
            ->assertOk()
            ->assertHeader('X-Script-Sha256', hash_file('sha256', $path));

        $this->flushHeaders()->get('/api/agent/script')->assertUnauthorized();
    }

    public function test_koordinat_tidak_valid_ditolak(): void
    {
        $this->checkin(['latitude' => 120, 'longitude' => 106.8])->assertUnprocessable();
        $this->checkin(['latitude' => -6.2])->assertUnprocessable();
    }
}
