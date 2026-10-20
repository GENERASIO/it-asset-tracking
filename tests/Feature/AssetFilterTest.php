<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'it_staff']);
        $laptop = Category::create(['name' => 'Laptop', 'code' => 'LAI']);
        $training = Location::create(['name' => 'Training Center', 'code' => 'TC']);
        $ho = Location::create(['name' => 'Head Office', 'code' => 'HO']);

        $make = fn ($code, $location, $price, $estimated = false) => Asset::create([
            'asset_code' => $code, 'name' => 'Laptop '.$code, 'brand' => 'DELL', 'model' => 'Vostro 3400',
            'category_id' => $laptop->id, 'location_id' => $location->id, 'status' => 'in_use',
            'purchase_price' => $price, 'purchase_price_estimated' => $estimated,
        ]);

        $make('TC-001', $training, 6800000);
        $make('TC-002', $training, 7900000, true);
        $make('HO-001', $ho, 12500000);
        $make('HO-002', $ho, null);
    }

    private function table(array $query)
    {
        return $this->actingAs($this->staff)->get(route('assets.index', ['view' => 'table'] + $query))->assertOk();
    }

    public function test_cari_berdasarkan_nama_lokasi(): void
    {
        $this->table(['search' => 'training center'])
            ->assertSee('TC-001')->assertSee('TC-002')->assertDontSee('HO-001')
            ->assertSee('Rp 14.700.000');
    }

    public function test_filter_lokasi_dan_rentang_harga(): void
    {
        $ho = Location::where('code', 'HO')->first();

        $this->table(['location_id' => $ho->id])
            ->assertSee('HO-001')->assertSee('HO-002')->assertDontSee('TC-001')
            ->assertSee('1 tanpa harga');

        $this->table(['price_min' => 'Rp 7.000.000', 'price_max' => 'Rp 10.000.000'])
            ->assertSee('TC-002')->assertDontSee('TC-001')->assertDontSee('HO-001');
    }

    public function test_filter_jenis_harga(): void
    {
        $this->table(['price' => 'estimated'])->assertSee('TC-002')->assertDontSee('TC-001');
        $this->table(['price' => 'empty'])->assertSee('HO-002')->assertDontSee('HO-001');
    }

    public function test_urut_berdasarkan_harga(): void
    {
        $this->table(['sort' => 'price', 'direction' => 'desc'])
            ->assertSeeInOrder(['HO-001', 'TC-002', 'TC-001']);
    }

    public function test_hasil_kosong_menampilkan_pesan_filter(): void
    {
        $this->table(['search' => 'tidak-ada-aset-ini'])
            ->assertSee('Tidak ada aset yang cocok dengan filter.');
    }

    public function test_export_mengikuti_filter(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('assets.export', ['search' => 'training center']))
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent() ?: $response->getFile()->getContent());
        $rows = \Maatwebsite\Excel\Facades\Excel::toArray(new class {}, $path)[0];
        unlink($path);

        $codes = array_column(array_slice($rows, 1), 0);
        sort($codes);
        $this->assertSame(['TC-001', 'TC-002'], $codes);
    }
}
