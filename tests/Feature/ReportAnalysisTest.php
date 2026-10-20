<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'it_staff']);
        $laptop = Category::create(['name' => 'Laptop', 'code' => 'LAI']);
        $ho = Location::create(['name' => 'Head Office', 'code' => 'HO']);
        $outlet = Location::create(['name' => 'Sushi Yay! Kelapa Gading', 'code' => 'SUSH']);

        $make = fn ($code, $location, $spec, $price, $status = 'in_use', $date = null) => Asset::create([
            'asset_code' => $code, 'name' => 'Laptop '.$code, 'brand' => 'DELL', 'model' => 'Vostro 3400',
            'category_id' => $laptop->id, 'location_id' => $location->id, 'status' => $status,
            'specification' => $spec, 'purchase_price' => $price, 'purchase_date' => $date,
        ]);

        $make('A1', $ho, '13th Gen Intel Core i3-1315U', 8000000, 'in_use', now()->subYears(2)->toDateString());
        $make('A2', $ho, 'Intel Core i3-10110U CPU', 9000000);
        $old = $make('A3', $outlet, 'Intel Core i3-5005U', 6000000, 'available');
        $make('A4', $outlet, 'Catatan: Ex', 7000000);

        MaintenanceLog::create([
            'asset_id' => $old->id, 'issue' => 'Layar rusak', 'cost' => 750000,
            'reported_at' => now()->toDateString(), 'status' => 'done', 'created_by' => $this->staff->id,
        ]);
    }

    public function test_laporan_menampilkan_analisis(): void
    {
        $this->actingAs($this->staff)->get(route('reports.index'))
            ->assertOk()
            // Nilai buku: Rp 8 jt dibeli 2 tahun lalu, susut 4 tahun -> ~Rp 4 jt.
            ->assertSee('Perkiraan Nilai Buku')
            ->assertSee('Dari 1 aset bertanggal beli')
            ->assertSee('Umur Perangkat')
            ->assertSee('Baru (2023+)')
            ->assertSee('Tua (2020 ke bawah)')
            ->assertSee('Nilai Aset per Lokasi')
            ->assertSee('Sushi Yay! Kelapa Gading')
            ->assertSee('Kelengkapan Data')
            ->assertSee('Biaya Maintenance per Model')
            ->assertSee('Rp 750.000')
            ->assertSee('1 <span class="text-base font-semibold text-gray-500 dark:text-gray-400">unit (25%)</span>', false);
    }

    public function test_kesimpulan_dan_rekomendasi(): void
    {
        $response = $this->actingAs($this->staff)->get(route('reports.index'))->assertOk();

        $titles = collect($response->viewData('insights'))->pluck('title');

        // A2 (i3-10110U) & A3 (i3-5005U) tua; harga pengganti dari rata-rata perangkat baru (A1 Rp 8 jt).
        $this->assertContains('2 perangkat (50%) sudah tua (prosesor 2020 ke bawah)', $titles);
        $this->assertContains('Nilai buku baru terhitung untuk 1 dari 4 aset', $titles);
        // Ada 1 unit Available dari 4 aset, sudah memenuhi 3% -> tidak ada peringatan cadangan.
        $this->assertNotContains('Tidak ada perangkat cadangan', $titles);

        $response->assertSee('Kesimpulan &amp; Rekomendasi', false)
            ->assertSee('Anggarkan peremajaan bertahap sekitar Rp 16.000.000 (2 unit × Rp 8.000.000)');
    }

    public function test_umur_dan_nilai_mengikuti_filter_lokasi(): void
    {
        $outlet = Location::where('code', 'SUSH')->first();

        $response = $this->actingAs($this->staff)->get(route('reports.index', ['location_id' => $outlet->id]))->assertOk();
        $ageGroups = $response->viewData('ageGroups')->keyBy('key');

        $this->assertSame(1, $ageGroups['old']['count']);
        $this->assertSame(1, $ageGroups['unknown']['count']);
        $this->assertSame(0, $ageGroups['new']['count']);
        $this->assertSame(13000000, $response->viewData('valueByLocation')->sum('value'));
    }

    public function test_prosesor_dari_agent_dipakai_untuk_umur(): void
    {
        // A4 spesifikasinya cuma "Catatan: Ex" (umur tidak diketahui). Setelah agent melaporkan
        // prosesornya, umurnya ikut terbaca.
        [, $token] = \App\Models\AgentToken::generate('Test', $this->staff->id);
        $a4 = Asset::where('asset_code', 'A4')->first();
        $a4->update(['serial_number' => 'SN-A4']);

        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-A4', 'hostname' => 'LAPTOP-A4', 'os_name' => 'Windows',
            'cpu' => '13th Gen Intel(R) Core(TM) i5-1335U',
        ])->assertOk();

        $this->assertSame('13th Gen Intel(R) Core(TM) i5-1335U', $a4->fresh()->cpu);

        $ages = $this->actingAs($this->staff)->get(route('reports.index'))->viewData('ageGroups')->keyBy('key');
        $this->assertSame(2, $ages['new']['count']);
        $this->assertSame(0, $ages['unknown']['count']);

        $this->flushHeaders()->actingAs($this->staff)->get(route('assets.show', $a4))
            ->assertSee('13th Gen Intel(R) Core(TM) i5-1335U')
            ->assertSee('Baru (2023+)');

        // Check-in agent lama (tanpa cpu) tidak menghapus nilai yang sudah ada.
        $this->withToken($token)->postJson('/api/agent/checkin', [
            'serial_number' => 'SN-A4', 'hostname' => 'LAPTOP-A4', 'os_name' => 'Windows',
        ])->assertOk();
        $this->assertSame('13th Gen Intel(R) Core(TM) i5-1335U', $a4->fresh()->cpu);
    }

    public function test_klasifikasi_nama_prosesor_dari_agent(): void
    {
        $cases = [
            '13th Gen Intel(R) Core(TM) i3-1315U' => 'new',
            'Intel(R) Core(TM) i3-10110U CPU @ 2.10GHz' => 'old',
            '11th Gen Intel(R) Core(TM) i3-1115G4 @ 3.00GHz' => 'mid',
            'AMD Ryzen 5 7535HS with Radeon Graphics' => 'new',
            'Intel(R) Celeron(R) N4000 CPU @ 1.10GHz' => 'old',
            'Intel(R) Core(TM) Ultra 5 125H' => 'new',
            'Apple M1' => 'mid',
            'Apple M3 Pro' => 'new',
        ];

        foreach ($cases as $cpu => $expected) {
            $this->assertSame($expected, \App\Support\DeviceAge::classify($cpu), $cpu);
        }
    }

    public function test_export_pdf_berisi_analisis(): void
    {
        $this->actingAs($this->staff)->get(route('reports.export-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
