<?php

namespace Tests\Feature;

use App\Models\BastDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BastSignTest extends TestCase
{
    use RefreshDatabase;

    private function makeBast(): BastDocument
    {
        return BastDocument::create([
            'handover_date' => now(), 'city' => 'Jakarta',
            'first_party_name' => 'Andi', 'first_party_position' => 'Staff IT',
            'second_party_name' => 'Deo', 'second_party_position' => 'Staff IT',
            'created_by' => User::factory()->create()->id,
        ]);
    }

    public function test_bast_dapat_sign_token_otomatis(): void
    {
        $this->assertNotEmpty($this->makeBast()->sign_token);
    }

    public function test_penerima_bisa_buka_dan_tanda_tangan_lewat_link_publik(): void
    {
        Storage::fake('local');
        $bast = $this->makeBast();

        // Halaman publik tanpa login.
        $this->get(route('bast.sign.show', $bast->sign_token))
            ->assertOk()->assertSee('Deo')->assertSee('Tanda Tangan');

        $dataUrl = 'data:image/png;base64,'.base64_encode(str_repeat('x', 400));
        $this->post(route('bast.sign.store', $bast->sign_token), ['signature' => $dataUrl])
            ->assertRedirect(route('bast.sign.show', $bast->sign_token));

        $bast->refresh();
        $this->assertNotNull($bast->second_party_signed_at);
        $this->assertNotNull($bast->second_party_signature);
        Storage::disk('local')->assertExists($bast->second_party_signature);
        $this->assertTrue($bast->isDigitallySigned());
    }

    public function test_tanda_tangan_kosong_ditolak(): void
    {
        $bast = $this->makeBast();
        $this->post(route('bast.sign.store', $bast->sign_token), ['signature' => ''])
            ->assertSessionHasErrors('signature');
    }

    public function test_pdf_publik_bisa_dibuka_lewat_token(): void
    {
        $bast = $this->makeBast();
        $res = $this->get(route('bast.sign.pdf', $bast->sign_token));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', strtolower($res->headers->get('content-type') ?? ''));
    }

    public function test_token_salah_404(): void
    {
        $this->get(route('bast.sign.show', 'token-ngawur'))->assertNotFound();
    }

    public function test_endpoint_status_untuk_auto_refresh(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'it_staff']);
        $bast = $this->makeBast();

        $this->actingAs($staff)->getJson(route('bast.status', $bast))
            ->assertOk()->assertJson(['second_signed' => false]);

        $dataUrl = 'data:image/png;base64,'.base64_encode(str_repeat('x', 400));
        $this->post(route('bast.sign.store', $bast->sign_token), ['signature' => $dataUrl]);

        $this->actingAs($staff)->getJson(route('bast.status', $bast))
            ->assertOk()->assertJson(['second_signed' => true]);
    }

    public function test_pihak_pertama_bisa_tanda_tangan_dari_aplikasi(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['role' => 'it_staff']);
        $bast = $this->makeBast();

        $dataUrl = 'data:image/png;base64,'.base64_encode(str_repeat('y', 400));
        $this->actingAs($staff)->post(route('bast.sign-first', $bast), ['signature' => $dataUrl])->assertRedirect();

        $this->assertNotNull($bast->fresh()->first_party_signed_at);
    }
}
