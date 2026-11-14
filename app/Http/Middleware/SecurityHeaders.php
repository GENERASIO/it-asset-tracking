<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua respons:
 * - X-Frame-Options: halaman tidak bisa dibingkai situs lain (anti clickjacking).
 * - X-Content-Type-Options: browser tidak menebak-nebak tipe file (anti MIME sniffing).
 * - Referrer-Policy: URL lengkap (mis. link tanda tangan bertoken) tidak bocor ke situs lain.
 * - Strict-Transport-Security: browser selalu memakai HTTPS (hanya dikirim lewat HTTPS).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()', false);

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000', false);
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
