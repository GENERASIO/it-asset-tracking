<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatedPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'it_staff']);
        $this->asset = Asset::create([
            'asset_code' => 'LAI-HO-0001',
            'name' => 'Dell Vostro 3400',
            'category_id' => Category::create(['name' => 'Laptop', 'code' => 'LAI'])->id,
            'location_id' => Location::create(['name' => 'Head Office', 'code' => 'HO'])->id,
            'status' => 'available',
            'purchase_price' => 7900000,
            'purchase_price_estimated' => true,
        ]);
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->asset->name,
            'category_id' => $this->asset->category_id,
            'location_id' => $this->asset->location_id,
            'status' => $this->asset->status,
            'purchase_price' => '7900000',
        ], $overrides);
    }

    public function test_harga_estimasi_diberi_label_di_detail_dan_laporan(): void
    {
        $this->actingAs($this->staff)->get(route('assets.show', $this->asset))
            ->assertOk()->assertSee('Rp 7.900.000')->assertSee('estimasi');

        $this->actingAs($this->staff)->get(route('reports.index'))
            ->assertOk()->assertSee('1 aset harga estimasi');
    }

    public function test_simpan_tanpa_ubah_harga_tetap_estimasi(): void
    {
        $this->actingAs($this->staff)->put(route('assets.update', $this->asset), $this->updatePayload(['name' => 'Dell Vostro 14 3400']));

        $this->assertTrue($this->asset->fresh()->purchase_price_estimated);
    }

    public function test_input_harga_berformat_rupiah_disimpan_sebagai_angka(): void
    {
        $this->actingAs($this->staff)
            ->put(route('assets.update', $this->asset), $this->updatePayload(['purchase_price' => 'Rp 10.000.000']))
            ->assertSessionHasNoErrors();

        $this->assertSame(10000000, $this->asset->fresh()->purchase_price);

        $this->actingAs($this->staff)->get(route('assets.edit', $this->asset))
            ->assertOk()
            ->assertSee('value="Rp 10.000.000"', false);
    }

    public function test_harga_dalam_satuan_juta_ditolak(): void
    {
        $this->actingAs($this->staff)
            ->put(route('assets.update', $this->asset), $this->updatePayload(['purchase_price' => '6,8']))
            ->assertSessionHasErrors('purchase_price');

        $this->assertSame(7900000, $this->asset->fresh()->purchase_price);
    }

    public function test_harga_boleh_dikosongkan(): void
    {
        $this->actingAs($this->staff)
            ->put(route('assets.update', $this->asset), $this->updatePayload(['purchase_price' => '']))
            ->assertSessionHasNoErrors();

        $asset = $this->asset->fresh();
        $this->assertNull($asset->purchase_price);
        $this->assertFalse($asset->purchase_price_estimated);
    }

    public function test_harga_diubah_manual_jadi_harga_asli(): void
    {
        $this->actingAs($this->staff)->put(route('assets.update', $this->asset), $this->updatePayload(['purchase_price' => '8250000']));

        $asset = $this->asset->fresh();
        $this->assertEquals(8250000, (float) $asset->purchase_price);
        $this->assertFalse($asset->purchase_price_estimated);

        $this->actingAs($this->staff)->get(route('assets.show', $asset))->assertDontSee('>estimasi<', false);
    }
}
