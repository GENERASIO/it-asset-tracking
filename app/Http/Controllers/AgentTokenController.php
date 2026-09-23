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
        if ($request->session()->has('newToken')) {
            $request->session()->reflash();
        }

        $tokens = AgentToken::with('creator')->latest()->get();

        $recentCheckins = Asset::whereNotNull('last_seen_at')
            ->orderByDesc('last_seen_at')
            ->take(20)
            ->get();

        return view('agent-tokens.index', compact('tokens', 'recentCheckins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        [$agentToken, $plainToken] = AgentToken::generate($request->name, $request->user()->id);

        return redirect()->route('agent-tokens.index')
            ->with('newToken', $plainToken)
            ->with('success', 'Token "'.$agentToken->name.'" berhasil dibuat. Salin sekarang, token ini tidak akan ditampilkan lagi.');
    }

    public function destroy(AgentToken $agentToken)
    {
        $agentToken->update(['revoked_at' => now()]);

        return redirect()->route('agent-tokens.index')->with('success', 'Token berhasil dicabut.');
    }

    /**
     * Download paket agent (script + config berisi token) yang baru saja dibuat.
     * Hanya tersedia selama token masih ada di session (sekali reveal), sama seperti tampilan copy-token.
     */
    public function downloadPackage(Request $request, string $os)
    {
        abort_unless(in_array($os, ['windows', 'macos']), 404);

        $token = $request->session()->get('newToken');
        abort_unless($token, 404, 'Token sudah tidak tersedia untuk didownload. Buat token baru untuk mendapatkan paket agent.');

        $request->session()->reflash();

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

            foreach (['checkin.ps1', 'install.ps1', 'uninstall.ps1'] as $file) {
                $zip->addFile($sourceDir.'/'.$file, $file);
            }
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
