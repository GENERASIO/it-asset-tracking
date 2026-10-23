<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\StfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StfTest extends TestCase
{
    use RefreshDatabase;

    public function test_buat_stf_memindahkan_lokasi_aset_dan_mencatat_log(): void
    {
        config(['bast.first_parties' => ['Aris Munandar']]);
        $staff = User::factory()->create(['role' => 'it_staff', 'name' => 'Aris Munandar']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $ho = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        $outlet = Location::create(['name' => 'Sushi YAY! Cipondoh', 'code' => 'SUSH']);

        $asset = Asset::create([
            'asset_code' => 'LTP-HO-0001', 'name' => 'Dell Vostro', 'category_id' => $cat->id,
            'location_id' => $ho->id, 'status' => 'in_use', 'serial_number' => 'SN-1',
        ]);

        $payload = [
            'transfer_date' => '2026-10-23',
            'from_location_id' => $ho->id,
            'to_location_id' => $outlet->id,
            'reason' => 'Perpindahan Asset',
            'sender_name' => 'Aris Munandar',
            'supervisor_name' => 'Aris Munandar',
            'items' => [
                ['asset_id' => $asset->id, 'asset_no' => 'LTP-HO-0001', 'description' => 'Dell Vostro', 'quantity' => 1, 'notes' => ''],
            ],
        ];

        $this->actingAs($staff)->post(route('stf.store'), $payload)->assertRedirect();

        // Aset pindah lokasi ke outlet tujuan.
        $this->assertSame($outlet->id, $asset->fresh()->location_id);
        // Dokumen + item tersimpan.
        $this->assertSame(1, StfDocument::count());
        // Log mutasi tercatat.
        $this->assertSame(1, DB::table('asset_logs')->where('asset_id', $asset->id)->where('action', 'transfer')->count());
    }

    public function test_halaman_create_dan_index_tampil(): void
    {
        config(['bast.first_parties' => ['Aris Munandar']]);
        $staff = User::factory()->create(['role' => 'it_staff', 'name' => 'Aris Munandar']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $ho = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        $asset = Asset::create([
            'asset_code' => 'LTP-HO-0001', 'name' => 'Dell', 'category_id' => $cat->id,
            'location_id' => $ho->id, 'status' => 'in_use', 'serial_number' => 'SN-R',
        ]);

        $this->actingAs($staff)->get(route('stf.create', ['ids' => [$asset->id]]))
            ->assertOk()->assertSee('Outlet Tujuan')->assertSee('LTP-HO-0001');

        $this->actingAs($staff)->get(route('stf.index'))->assertOk()->assertSee('Mutasi Aset');
    }

    public function test_pic_pengirim_harus_dari_daftar(): void
    {
        config(['bast.first_parties' => ['Aris Munandar']]);
        $staff = User::factory()->create(['role' => 'it_staff']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $outlet = Location::create(['name' => 'Outlet', 'code' => 'OUT']);
        $asset = Asset::create([
            'asset_code' => 'LTP-X-0001', 'name' => 'X', 'category_id' => $cat->id,
            'location_id' => $outlet->id, 'status' => 'in_use', 'serial_number' => 'SN-2',
        ]);

        $this->actingAs($staff)->post(route('stf.store'), [
            'transfer_date' => '2026-10-23', 'to_location_id' => $outlet->id,
            'reason' => 'x', 'sender_name' => 'Orang Asing', 'supervisor_name' => 'Aris Munandar',
            'items' => [['asset_id' => $asset->id, 'asset_no' => 'LTP-X-0001', 'description' => 'X', 'quantity' => 1]],
        ])->assertSessionHasErrors('sender_name');
    }
}
