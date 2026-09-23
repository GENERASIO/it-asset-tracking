<?php

namespace App\Http\Middleware;

use App\Models\AgentToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        $agentToken = AgentToken::findActiveByPlainToken($plainToken);

        if (! $agentToken) {
            return response()->json(['message' => 'Token tidak valid atau sudah dicabut.'], 401);
        }

        $agentToken->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('agentToken', $agentToken);

        return $next($request);
    }
}
