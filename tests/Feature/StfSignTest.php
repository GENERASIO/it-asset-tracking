<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\StfDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StfSignTest extends TestCase
{
    use RefreshDatabase;

    private function makeStf(): StfDocument
    {
        $stf = StfDocument::create([
            'transfer_date' => now(),
            'from_location_id' => Location::create(['name' => 'Sushi YAY! MT Haryono', 'code' => 'SMTH'])->id,
            'to_location_id' => Location::create(['name' => 'Head Office', 'code' => 'HO'])->id,
            'reason' => 'Perpindahan Asset',
            'sender_name' => 'Indra Paksi Putra',
            'supervisor_name' => 'Aris Munandar',
            'created_by' => User::factory()->create()->id,
        ]);
        $stf->items()->create([
            'asset_no' => 'LAI-TIDA-0056', 'description' => 'LENOVO 83GU',
            'to_location_name' => 'Head Office', 'quantity' => 1,
        ]);

        return $stf;
    }

    private function signature(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('x', 400));
    }

    public function test_stf_dapat_sign_token_otomatis(): void
    {
        $this->assertNotEmpty($this->makeStf()->sign_token);
    }

    public function test_penerima_bisa_tanda_tangan_lewat_link_publik(): void
    {
        Storage::fake('local');
        $stf = $this->makeStf();

        // Halaman publik tanpa login.
        $this->get(route('stf.sign.show', $stf->sign_token))
            ->assertOk()->assertSee('LENOVO 83GU')->assertSee('Head Office');

        $this->post(route('stf.sign.store', $stf->sign_token), [
            'receiver_name' => 'Denny',
            'signature' => $this->signature(),
        ])->assertRedirect(route('stf.sign.show', $stf->sign_token));

        $stf->refresh();
        $this->assertTrue($stf->isReceiverSigned());
        $this->assertSame('Denny', $stf->receiver_name);
        Storage::disk('local')->assertExists($stf->receiver_signature);
    }

    public function test_nama_dan_tanda_tangan_penerima_wajib(): void
    {
        $stf = $this->makeStf();

        $this->post(route('stf.sign.store', $stf->sign_token), ['receiver_name' => '', 'signature' => ''])
            ->assertSessionHasErrors(['receiver_name', 'signature']);
        $this->post(route('stf.sign.store', $stf->sign_token), ['receiver_name' => 'Denny', 'signature' => 'bukan-gambar'])
            ->assertSessionHasErrors('signature');

        $this->assertFalse($stf->fresh()->isReceiverSigned());
    }

    public function test_token_salah_404(): void
    {
        $this->get(route('stf.sign.show', 'token-salah'))->assertNotFound();
        $this->get(route('stf.sign.pdf', 'token-salah'))->assertNotFound();
    }

    public function test_pdf_publik_berisi_nama_penerima(): void
    {
        Storage::fake('local');
        $stf = $this->makeStf();
        $this->post(route('stf.sign.store', $stf->sign_token), ['receiver_name' => 'Denny', 'signature' => $this->signature()]);

        $res = $this->get(route('stf.sign.pdf', $stf->sign_token));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_pengirim_tanda_tangan_dan_status_untuk_auto_refresh(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'it_staff']);
        $stf = $this->makeStf();

        $this->actingAs($staff)->get(route('stf.show', $stf))
            ->assertOk()->assertSee('Minta Tanda Tangan (WA)')->assertSee($stf->sign_token, false);

        $this->actingAs($staff)->post(route('stf.sign-sender', $stf), ['signature' => $this->signature()])
            ->assertRedirect();
        $this->assertNotNull($stf->fresh()->sender_signed_at);

        $this->actingAs($staff)->getJson(route('stf.status', $stf))
            ->assertExactJson(['sender_signed' => true, 'receiver_signed' => false]);
    }

    public function test_tamu_tidak_bisa_tanda_tangan_sebagai_pengirim(): void
    {
        $stf = $this->makeStf();

        $this->post(route('stf.sign-sender', $stf), ['signature' => $this->signature()])
            ->assertRedirect(route('login'));
        $this->assertNull($stf->fresh()->sender_signed_at);
    }
}
