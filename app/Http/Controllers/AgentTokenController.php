<?php

namespace App\Http\Controllers;

use App\Models\AgentToken;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class AgentTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = AgentToken::with('creator')->latest()->get();

        $recentCheckins = Asset::whereNotNull('last_seen_at')
            ->orderByDesc('last_seen_at')
            ->take(20)
            ->get();

        $newToken = $this->pullActiveTokenReveal($request);

        return view('agent-tokens.index', compact('tokens', 'recentCheckins', 'newToken'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        [$agentToken, $plainToken] = AgentToken::generate($request->name, $request->user()->id);

        $request->session()->put('newTokenReveal', [
            'token' => $plainToken,
            'expires_at' => now()->addMinutes(10),
        ]);

        return redirect()->route('agent-tokens.index')
            ->with('success', 'Token "'.$agentToken->name.'" berhasil dibuat. Salin sekarang, token ini hanya ditampilkan selama 10 menit.');
    }

    /**
     * Ambil token yang baru saja dibuat kalau masih dalam window reveal (10 menit),
     * sekaligus bersihkan dari session begitu sudah lewat waktunya.
     */
    protected function pullActiveTokenReveal(Request $request): ?string
    {
        $reveal = $request->session()->get('newTokenReveal');

        if (! $reveal || now()->greaterThan($reveal['expires_at'])) {
            $request->session()->forget('newTokenReveal');

            return null;
        }

        return $reveal['token'];
    }

    public function destroy(AgentToken $agentToken)
    {
        $agentToken->update(['revoked_at' => now()]);

        return redirect()->route('agent-tokens.index')->with('success', 'Token berhasil dicabut.');
    }

    /**
     * Download paket agent (script + config berisi token) yang baru saja dibuat.
     * Hanya tersedia selama masih dalam window reveal 10 menit sejak token dibuat.
     */
    public function downloadPackage(Request $request, string $os)
    {
        abort_unless(in_array($os, ['windows', 'macos']), 404);

        $token = $this->pullActiveTokenReveal($request);
        abort_unless($token, 404, 'Token sudah tidak tersedia untuk didownload. Buat token baru untuk mendapatkan paket agent.');

        $apiUrl = url('/api/agent/checkin');
        $sourceDir = base_path("agents/{$os}");
        $zipPath = storage_path('app/it-asset-agent-'.$os.'-'.Str::random(8).'.zip');

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(base_path('agents/README.md'), 'README.md');

        if ($os === 'windows') {
            $zip->addFromString('config.json', json_encode([
                'ApiUrl' => $apiUrl,
                'ApiToken' => $token,
            ], JSON_PRETTY_PRINT));

            $zip->addFile($sourceDir.'/ITAssetAgentSetup.exe', 'ITAssetAgentSetup.exe');
        } else {
            $zip->addFromString('config.sh', "#!/bin/bash\nAPI_URL=\"{$apiUrl}\"\nAPI_TOKEN=\"{$token}\"\n");

            foreach (['checkin.sh', 'install.sh', 'uninstall.sh'] as $file) {
                $zip->addFile($sourceDir.'/'.$file, $file);
            }
        }

        $zip->close();

        return response()->download($zipPath, "it-asset-agent-{$os}.zip")->deleteFileAfterSend(true);
    }
}
