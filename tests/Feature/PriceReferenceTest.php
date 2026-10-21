<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\PriceReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Category::create(['name' => 'Lainnya', 'code' => 'LAI']);
        Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK']);
    }

    public function test_agent_checkin_mengisi_harga_otomatis_jika_kosong(): void
    {
        PriceReference::updateOrCreate(['match_name' => 'Dell Inc. Vostro 3400'], ['purchase_price' => 4000000]);
        [, $token] = AgentToken::generate('T', User::factory()->create()->id);
        // Aset sudah ada (mis. baru didaftarkan agent) tapi harga masih kosong.
        $asset = Asset::create([
            'asset_code' => 'LAI-X-0009', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => Category::first()->id, 'location_id' => Location::first()->id,
            'status' => 'in_use', 'serial_number' => 'SN-NEW',
        ]);

        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-NEW', 'hostname' => 'PC1', 'os_name' => 'Windows',
        ])->assertOk();

        $this->assertSame(4000000, $asset->fresh()->purchase_price);
        $this->assertFalse((bool) $asset->fresh()->purchase_price_estimated);
    }

    public function test_harga_manual_tidak_ditimpa_agent(): void
    {
        PriceReference::updateOrCreate(['match_name' => 'Dell Inc. Vostro 3400'], ['purchase_price' => 4000000]);
        [, $token] = AgentToken::generate('T', User::factory()->create()->id);
        $asset = Asset::create([
            'asset_code' => 'LAI-X-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => Category::first()->id, 'location_id' => Location::first()->id,
            'status' => 'in_use', 'serial_number' => 'SN-OLD', 'purchase_price' => 9999999,
        ]);

        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-OLD', 'hostname' => 'PC2', 'os_name' => 'Windows',
        ])->assertOk();

        $this->assertSame(9999999, $asset->fresh()->purchase_price);
    }

    public function test_halaman_harga_model_tampil(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $this->actingAs($staff)->get(route('price-references.index'))
            ->assertOk()
            ->assertSee('Harga Model')
            ->assertSee('Dell Inc. Vostro 3400');
    }

    public function test_staff_bisa_simpan_harga_model_dan_terapkan(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        Asset::create([
            'asset_code' => 'LAI-X-0002', 'name' => 'LENOVO 83GU',
            'category_id' => Category::first()->id, 'location_id' => Location::first()->id,
            'status' => 'in_use', 'serial_number' => 'SN-L1',
        ]);

        $this->actingAs($staff)->post(route('price-references.store'), [
            'match_name' => 'LENOVO 83GU',
            'purchase_price' => 'Rp 10.000.000',
        ])->assertRedirect();

        $this->assertDatabaseHas('price_references', ['match_name' => 'LENOVO 83GU', 'purchase_price' => 10000000]);
        $this->assertSame(10000000, Asset::where('serial_number', 'SN-L1')->first()->purchase_price);
    }
}
