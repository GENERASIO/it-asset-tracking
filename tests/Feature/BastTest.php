<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\BastDocument;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BastTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Laptop', 'code' => 'LAI']);
        $location = Location::create(['name' => 'Head Office', 'code' => 'HO']);

        $this->staff = User::factory()->create(['role' => 'it_staff', 'name' => 'Andi Wibowo']);
        $this->asset = Asset::create([
            'asset_code' => 'LAI-HO-0001',
            'name' => 'Laptop Lenovo',
            'brand' => 'LENOVO',
            'model' => 'IdeaPad Gaming 3 15ARH7',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'status' => 'available',
            'serial_number' => 'MP2EB2QS',
        ]);
    }

    private function bastPayload(array $overrides = []): array
    {
        return array_merge([
            'handover_date' => '2026-10-19',
            'city' => 'Jakarta',
            'first_party_name' => 'Andi Wibowo',
            'first_party_position' => 'IT Staff',
            'second_party_name' => 'Outlet Kemang',
            'second_party_position' => 'Kepala Outlet',
            'items' => [[
                'asset_id' => $this->asset->id,
                'item_type' => 'Laptop',
                'detail' => 'LENOVO IdeaPad Gaming 3 15ARH7',
                'serial_number' => 'MP2EB2QS',
                'quantity' => 1,
                'condition' => 'Bekas',
            ]],
        ], $overrides);
    }

    public function test_checkout_wajib_pilih_kondisi_dan_menawarkan_bast(): void
    {
        $receiver = User::factory()->create(['name' => 'Rifki Deo Winnada']);

        $this->actingAs($this->staff)
            ->post(route('assets.checkout', $this->asset), ['to_user_id' => $receiver->id])
            ->assertSessionHasErrors('condition');

        $this->actingAs($this->staff)
            ->post(route('assets.checkout', $this->asset), ['to_user_id' => $receiver->id, 'condition' => 'Bekas'])
            ->assertRedirect(route('assets.show', $this->asset))
            ->assertSessionHas('offer_bast', true);

        $this->assertSame('Bekas', AssetLog::where('action', 'check_out')->first()->condition);
    }

    public function test_form_bast_terisi_otomatis_dari_aset(): void
    {
        $receiver = User::factory()->create(['name' => 'Rifki Deo Winnada']);
        $this->actingAs($this->staff)
            ->post(route('assets.checkout', $this->asset), ['to_user_id' => $receiver->id, 'condition' => 'Bekas']);

        $this->actingAs($this->staff)
            ->get(route('bast.create', ['ids' => [$this->asset->id]]))
            ->assertOk()
            // Staff yang login ada di daftar Pihak Pertama, jadi langsung terpilih.
            ->assertSee('<option value="Andi Wibowo" selected', false)
            ->assertSee('value="Rifki Deo Winnada"', false)
            ->assertSee('value="LENOVO IdeaPad Gaming 3 15ARH7"', false)
            ->assertSee('value="MP2EB2QS"', false)
            ->assertSee('value="Bekas"', false);
    }

    public function test_buat_bast_untuk_outlet_lalu_cetak_pdf(): void
    {
        $this->actingAs($this->staff)
            ->post(route('bast.store'), $this->bastPayload())
            ->assertRedirect();

        $bast = BastDocument::with('items')->first();
        $this->assertSame('Outlet Kemang', $bast->second_party_name);
        $this->assertSame('19 Oktober 2026', $bast->handover_date_label);
        $this->assertCount(1, $bast->items);

        $this->actingAs($this->staff)->get(route('bast.show', $bast))
            ->assertOk()
            ->assertSee('Outlet Kemang')
            ->assertSee('Belum upload tanda tangan');

        $this->actingAs($this->staff)->get(route('bast.pdf', $bast))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Tampil juga di detail aset.
        $this->actingAs($this->staff)->get(route('assets.show', $this->asset))
            ->assertSee('19 Oktober 2026 — ke Outlet Kemang');
    }

    public function test_pihak_pertama_harus_dari_daftar_dan_jabatannya_diingat(): void
    {
        $this->actingAs($this->staff)
            ->post(route('bast.store'), $this->bastPayload(['first_party_name' => 'Orang Lain']))
            ->assertSessionHasErrors('first_party_name');

        $this->actingAs($this->staff)
            ->post(route('bast.store'), $this->bastPayload(['first_party_name' => 'Indra Paksi Putra', 'first_party_position' => 'IT Supervisor']))
            ->assertRedirect();

        $this->actingAs($this->staff)
            ->get(route('bast.create', ['ids' => [$this->asset->id]]))
            ->assertOk()
            ->assertSee('Aris Munandar')
            ->assertSee('Rifki Deo')
            ->assertSee('IT Supervisor');
    }

    public function test_jabatan_wajib_diisi(): void
    {
        $this->actingAs($this->staff)
            ->post(route('bast.store'), $this->bastPayload(['first_party_position' => '', 'second_party_position' => '']))
            ->assertSessionHasErrors(['first_party_position', 'second_party_position']);
    }

    public function test_upload_ganti_dan_hapus_scan_tanda_tangan(): void
    {
        Storage::fake('local');

        $this->actingAs($this->staff)->post(route('bast.store'), $this->bastPayload());
        $bast = BastDocument::first();

        $this->actingAs($this->staff)
            ->post(route('bast.signed.upload', $bast), ['signed_file' => UploadedFile::fake()->create('bast-ttd.pdf', 200, 'application/pdf')])
            ->assertRedirect(route('bast.show', $bast));

        $first = $bast->fresh()->signed_file;
        Storage::disk('local')->assertExists($first);
        $this->actingAs($this->staff)->get(route('bast.signed.show', $bast))->assertOk();

        $this->actingAs($this->staff)
            ->post(route('bast.signed.upload', $bast), ['signed_file' => UploadedFile::fake()->image('foto-ttd.jpg')]);
        Storage::disk('local')->assertMissing($first);
        $second = $bast->fresh()->signed_file;
        Storage::disk('local')->assertExists($second);

        $this->actingAs($this->staff)
            ->post(route('bast.signed.upload', $bast), ['signed_file' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('signed_file');

        $this->actingAs($this->staff)->delete(route('bast.destroy', $bast))->assertRedirect(route('bast.index'));
        Storage::disk('local')->assertMissing($second);
        $this->assertDatabaseCount('bast_items', 0);
    }

    public function test_user_biasa_tidak_bisa_akses_bast(): void
    {
        $this->actingAs($this->staff)->post(route('bast.store'), $this->bastPayload());
        $bast = BastDocument::first();
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('bast.index'))->assertForbidden();
        $this->actingAs($user)->get(route('bast.show', $bast))->assertForbidden();
        $this->actingAs($user)->get(route('bast.pdf', $bast))->assertForbidden();
        $this->actingAs($user)->get(route('bast.signed.show', $bast))->assertForbidden();
    }

    public function test_daftar_bast_bisa_difilter(): void
    {
        $this->actingAs($this->staff)->post(route('bast.store'), $this->bastPayload());
        $this->actingAs($this->staff)->post(route('bast.store'), $this->bastPayload(['second_party_name' => 'Budi Santoso']));
        BastDocument::where('second_party_name', 'Budi Santoso')->update(['signed_file' => 'bast/x.pdf']);

        $this->actingAs($this->staff)->get(route('bast.index', ['status' => 'unsigned']))
            ->assertOk()->assertSee('Outlet Kemang')->assertDontSee('Budi Santoso');

        $this->actingAs($this->staff)->get(route('bast.index', ['q' => 'LAI-HO-0001']))
            ->assertOk()->assertSee('Outlet Kemang')->assertSee('Budi Santoso');
    }
}
