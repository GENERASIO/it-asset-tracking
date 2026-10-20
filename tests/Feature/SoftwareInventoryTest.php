<?php

namespace Tests\Feature;

use App\Models\AgentToken;
use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\Category;
use App\Models\Location;
use App\Models\SoftwareEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftwareInventoryTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Lainnya', 'code' => 'LN']);
        $location = Location::create(['name' => 'Tidak Diketahui', 'code' => 'TD']);

        // Dibuat lebih dulu karena AssetCodeGenerator memakai SUBSTRING_INDEX (khusus MySQL).
        $this->asset = Asset::create([
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

    private function retailOffice(): array
    {
        return [
            'product' => 'office', 'name' => 'Office 21, Office21HomeBusiness2021R_Retail edition',
            'description' => 'Office 21, RETAIL channel', 'channel' => 'Retail',
            'status_code' => 1, 'grace_minutes' => 0, 'partial_key' => 'ABCDE',
        ];
    }

    public function test_menyimpan_aplikasi_dan_lisensi_resmi(): void
    {
        $this->checkin([
            'software' => [
                ['name' => 'Microsoft® Edge', 'version' => '130.0', 'publisher' => 'Microsoft', 'installed_on' => '20261015'],
                ['name' => 'AnyDesk', 'version' => '7.0', 'installed_on' => 'bukan-tanggal'],
                ['name' => ''],
                'bukan-array',
            ],
            'licenses' => [$this->retailOffice()],
            'crack_indicators' => [],
        ])->assertOk();

        $this->asset->refresh();
        $this->assertSame(['AnyDesk', 'Microsoft® Edge'], $this->asset->software->pluck('name')->all());
        $this->assertSame('2026-10-15', $this->asset->software->firstWhere('name', 'Microsoft® Edge')->installed_on->toDateString());
        $this->assertNull($this->asset->software->firstWhere('name', 'AnyDesk')->installed_on);

        $license = $this->asset->licenses->first();
        $this->assertSame('licensed', $license->status);
        $this->assertFalse($license->suspected);
        $this->assertNotNull($this->asset->inventory_synced_at);
    }

    public function test_aktivasi_kms_luar_kantor_dan_jejak_crack_ditandai(): void
    {
        $this->checkin([
            'licenses' => [
                ['product' => 'office', 'name' => 'Office 19, Office19ProPlus2019VL_KMS_Client_AE edition', 'channel' => 'Volume:GVLK',
                 'status_code' => 1, 'grace_minutes' => 60 * 24 * 100, 'kms_host' => '127.0.0.2:1688'],
                ['product' => 'windows', 'name' => 'Windows(R), Professional edition', 'channel' => 'OEM:DM', 'status_code' => 1],
            ],
            'crack_indicators' => ['task: \\AutoPico Daily Restart'],
        ])->assertOk();

        $office = AssetLicense::where('product', 'office')->first();
        $this->assertTrue($office->suspected);
        $this->assertStringContainsString('127.0.0.2:1688', $office->suspected_reason);
        $this->assertStringContainsString('AutoPico', $office->suspected_reason);

        // Jejak crack berlaku untuk semua lisensi di laptop itu.
        $this->assertTrue(AssetLicense::where('product', 'windows')->first()->suspected);
    }

    public function test_server_kms_resmi_perusahaan_tidak_ditandai(): void
    {
        config(['licenses.allowed_kms_hosts' => ['172.16.0.2']]);

        $this->checkin([
            'licenses' => [['product' => 'office', 'name' => 'Office 19 VL', 'channel' => 'Volume:GVLK', 'status_code' => 1, 'kms_host' => '172.16.0.2:1688']],
            'crack_indicators' => [],
        ])->assertOk();

        $this->assertFalse(AssetLicense::first()->suspected);
    }

    public function test_agent_lama_tanpa_inventaris_tidak_menghapus_data(): void
    {
        $this->checkin(['software' => [['name' => 'AnyDesk']], 'licenses' => [$this->retailOffice()]])->assertOk();
        $this->checkin()->assertOk();

        $this->assertSame(1, $this->asset->software()->count());
        $this->assertSame(1, $this->asset->licenses()->count());
    }

    public function test_pemakaian_lisensi_dihitung_tanpa_yang_terindikasi_tidak_resmi(): void
    {
        $this->checkin(['licenses' => [$this->retailOffice()], 'crack_indicators' => []])->assertOk();

        $entitlement = SoftwareEntitlement::create([
            'name' => 'Office H&B 2021', 'match' => 'HomeBusiness2021', 'license_type' => 'perpetual', 'quantity' => 1,
        ]);
        $this->assertSame(1, $entitlement->usedCount());

        AssetLicense::query()->update(['suspected' => true]);
        $this->assertSame(0, $entitlement->usedCount());
    }

    public function test_halaman_lisensi_dan_pengingat_hanya_untuk_lisensi_resmi(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->asset->licenses()->create([
            'product' => 'office', 'name' => 'Office Resmi', 'status' => 'licensed', 'expires_at' => now()->addDays(2),
        ]);
        $this->asset->licenses()->create([
            'product' => 'windows', 'name' => 'Windows Crack', 'status' => 'licensed', 'expires_at' => now()->addDays(2),
            'suspected' => true, 'suspected_reason' => 'KMS luar',
        ]);
        SoftwareEntitlement::create([
            'name' => 'Microsoft 365 Business', 'match' => 'O365', 'license_type' => 'subscription',
            'quantity' => 10, 'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($staff)->get(route('software-licenses.index'))
            ->assertOk()
            ->assertSee('Office Resmi')
            ->assertSee('Windows Crack')
            ->assertSee('Microsoft 365 Business')
            // Lonceng notifikasi: lisensi resmi dan langganan diingatkan, yang crack tidak.
            ->assertSee('TEST-0001 — Lisensi Office')
            ->assertDontSee('TEST-0001 — Lisensi Windows')
            ->assertSee('Lisensi Microsoft 365 Business');

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('software-licenses.index'))
            ->assertForbidden();
    }

    public function test_detail_aset_menampilkan_inventaris_hanya_untuk_staff(): void
    {
        $this->checkin([
            'software' => [['name' => 'AnyDesk', 'version' => '7.0']],
            'licenses' => [$this->retailOffice()],
            'crack_indicators' => ['file: C:\\Program Files\\KMSpico'],
        ])->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'it_staff']))
            ->get(route('assets.show', $this->asset))
            ->assertOk()
            ->assertSee('Lisensi Software')
            ->assertSee('Office21HomeBusiness2021R_Retail')
            ->assertSee('Terindikasi tidak resmi')
            ->assertSee('KMSpico')
            ->assertSee('AnyDesk');

        $holder = User::factory()->create(['role' => 'user']);
        $this->asset->update(['assigned_to' => $holder->id]);

        $this->actingAs($holder)
            ->get(route('assets.show', $this->asset))
            ->assertOk()
            ->assertDontSee('Lisensi Software')
            ->assertDontSee('KMSpico');
    }

    public function test_tambah_lisensi_langganan_wajib_tanggal(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post(route('software-licenses.store'), [
            'name' => 'M365', 'match' => 'O365', 'license_type' => 'subscription', 'quantity' => 5,
        ])->assertSessionHasErrors('expires_at');

        $this->actingAs($admin)->post(route('software-licenses.store'), [
            'name' => 'M365', 'match' => 'O365', 'license_type' => 'subscription', 'quantity' => 5, 'expires_at' => '2027-01-01',
        ])->assertRedirect(route('software-licenses.index'));

        $this->assertDatabaseHas('software_entitlements', ['name' => 'M365', 'quantity' => 5]);
    }
}
