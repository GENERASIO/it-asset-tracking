<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_staff_menampilkan_ringkasan_kategori_dan_lokasi(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $ho = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        Location::create(['name' => 'Outlet Kosong', 'code' => 'OK']);

        foreach (['available', 'in_use', 'in_use', 'broken'] as $i => $status) {
            Asset::create([
                'asset_code' => "LTP-HO-000{$i}", 'name' => 'Dell', 'category_id' => $cat->id,
                'location_id' => $ho->id, 'status' => $status, 'serial_number' => "SN-{$i}",
            ]);
        }

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Komposisi Status Aset')
            ->assertSee('Head Office')
            ->assertDontSee('Outlet Kosong')   // lokasi tanpa aset tidak ikut grafik
            ->assertSee('50% dari total aset'); // 2 dari 4 aset In Use
    }

    public function test_dashboard_user_biasa_menampilkan_aset_miliknya(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $cat = Category::create(['name' => 'Laptop', 'code' => 'LTP']);
        $loc = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        Asset::create([
            'asset_code' => 'LTP-HO-0009', 'name' => 'Lenovo', 'category_id' => $cat->id,
            'location_id' => $loc->id, 'status' => 'in_use', 'serial_number' => 'SN-9', 'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aset yang Anda Pegang')
            ->assertSee('LTP-HO-0009')
            ->assertDontSee('Komposisi Status Aset');
    }
}
