<?php

namespace Tests\Feature;

use App\Support\SlackNotifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackNotifierTest extends TestCase
{
    public function test_tidak_kirim_kalau_webhook_kosong(): void
    {
        config(['services.slack.webhook' => null]);
        Http::fake();

        SlackNotifier::send('halo');

        Http::assertNothingSent();
    }

    public function test_kirim_ke_webhook_kalau_diset(): void
    {
        config(['services.slack.webhook' => 'https://hooks.slack.com/services/XXX']);
        Http::fake();

        SlackNotifier::send('Perangkat baru');

        Http::assertSent(fn ($req) => $req->url() === 'https://hooks.slack.com/services/XXX'
            && $req['text'] === 'Perangkat baru');
    }
}
