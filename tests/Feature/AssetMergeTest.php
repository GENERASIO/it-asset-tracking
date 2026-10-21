<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_gabung_memindahkan_data_agent_dan_histori_ke_aset_lama(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $owner = User::factory()->create(['name' => 'Budi']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $catL = Category::create(['name' => 'Lainnya', 'code' => 'LAI']);
        $loc = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        $locUnknown = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK']);

        // Aset lama (benar): punya pemegang, serial lama yang salah.
        $target = Asset::create([
            'asset_code' => 'LTP-HO-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => $cat->id, 'location_id' => $loc->id, 'status' => 'in_use',
            'serial_number' => 'SALAH123', 'assigned_to' => $owner->id,
        ]);

        // Record buatan agent (kembar): serial benar + data hardware.
        $source = Asset::create([
            'asset_code' => 'LAI-TDK-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => $catL->id, 'location_id' => $locUnknown->id, 'status' => 'in_use',
            'serial_number' => 'BV83JK3', 'hostname' => 'PC-X', 'cpu' => 'Intel i5',
            'last_seen_at' => now(), 'purchase_price' => 4000000, 'current_value' => 2333333,
        ]);

        // Histori nempel di record agent.
        DB::table('asset_logs')->insert([
            'asset_id' => $source->id, 'action' => 'status_change', 'handled_by' => $staff->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('asset-merge.store', $source), ['target_id' => $target->id])
            ->assertRedirect(route('assets.show', $target));

        // Record agent terhapus.
        $this->assertNull(Asset::find($source->id));

        $target->refresh();
        $this->assertSame('BV83JK3', $target->serial_number);   // serial benar pindah
        $this->assertSame('PC-X', $target->hostname);           // hardware pindah
        $this->assertSame($owner->id, $target->assigned_to);    // pemegang tetap
        $this->assertSame('LTP-HO-0001', $target->asset_code);  // kode & identitas tetap
        $this->assertSame(4000000, $target->purchase_price);    // harga ikut (target kosong)

        // Histori ikut pindah.
        $this->assertSame(1, DB::table('asset_logs')->where('asset_id', $target->id)->count());
    }

    public function test_index_menandai_kemungkinan_cocok_glpi(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $cat = Category::create(['name' => 'Lainnya', 'code' => 'LAI']);
        $loc = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK']);
        $locHO = Location::create(['name' => 'Head Office', 'code' => 'HO']);

        // Record GLPI: model sama, tanpa serial, bukan agent, punya pemegang.
        Asset::create([
            'asset_code' => 'LAI-HO-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => $cat->id, 'location_id' => $locHO->id, 'status' => 'in_use',
            'serial_number' => null, 'assigned_to' => User::factory()->create()->id,
        ]);
        // Kandidat buatan agent.
        Asset::create([
            'asset_code' => 'LAI-TDK-0001', 'name' => 'Dell Inc. Vostro 3400',
            'category_id' => $cat->id, 'location_id' => $loc->id, 'status' => 'in_use',
            'serial_number' => 'BV83JK3', 'last_seen_at' => now(),
        ]);

        $this->actingAs($staff)->get(route('asset-merge.index'))
            ->assertOk()
            ->assertSee('kemungkinan cocok');
    }

    public function test_tidak_bisa_gabung_ke_diri_sendiri(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $cat = Category::create(['name' => 'Lainnya', 'code' => 'LAI']);
        $loc = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TDK']);
        $a = Asset::create([
            'asset_code' => 'LAI-TDK-0002', 'name' => 'X',
            'category_id' => $cat->id, 'location_id' => $loc->id, 'status' => 'in_use',
            'serial_number' => 'S1', 'last_seen_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('asset-merge.store', $a), ['target_id' => $a->id])
            ->assertSessionHasErrors('target_id');
    }
}
