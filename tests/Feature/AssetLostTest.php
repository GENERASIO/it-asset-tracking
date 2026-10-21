<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetLostTest extends TestCase
{
    use RefreshDatabase;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asset = Asset::create([
            'asset_code' => 'LAI-HO-0001',
            'name' => 'Laptop Test',
            'category_id' => Category::create(['name' => 'Laptop', 'code' => 'LAI'])->id,
            'location_id' => Location::create(['name' => 'Head Office', 'code' => 'HO'])->id,
            'status' => 'in_use',
            'serial_number' => 'SN-1',
        ]);
    }

    public function test_checkin_response_menandai_track_location_always(): void
    {
        [, $token] = AgentToken::generate('T', User::factory()->create()->id);

        $payload = ['serial_number' => 'SN-1', 'hostname' => 'H', 'os_name' => 'Windows'];

        $this->withToken($token)->postJson('/api/agent/checkin', $payload)
            ->assertOk()->assertJson(['track_location_always' => false]);

        $this->asset->update(['reported_lost_at' => now()]);

        $this->flushHeaders()->withToken($token)->postJson('/api/agent/checkin', $payload)
            ->assertOk()->assertJson(['track_location_always' => true]);
    }

    public function test_staff_bisa_tandai_hilang_dan_ditemukan(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->actingAs($staff)->post(route('assets.toggle-lost', $this->asset))->assertRedirect();
        $this->assertNotNull($this->asset->fresh()->reported_lost_at);

        // Banner tampil di detail aset.
        $this->actingAs($staff)->get(route('assets.show', $this->asset))
            ->assertSee('ditandai HILANG');

        // Toggle lagi -> ditemukan.
        $this->actingAs($staff)->post(route('assets.toggle-lost', $this->asset))->assertRedirect();
        $this->assertNull($this->asset->fresh()->reported_lost_at);
    }

    public function test_user_biasa_tidak_bisa_tandai_hilang(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->post(route('assets.toggle-lost', $this->asset))
            ->assertForbidden();
    }
}
