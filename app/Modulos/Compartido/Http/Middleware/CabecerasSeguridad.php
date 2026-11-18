<?php

namespace App\Modulos\Compartido\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras HTTP de seguridad (ISO/IEC 27002:2022, 8.26 y 8.28; OWASP Secure Headers).
 * - CSP: solo se ejecuta JavaScript propio del sitio (sin scripts en línea), lo que mitiga XSS.
 * - Evita que el portal se muestre dentro de un iframe ajeno (clickjacking).
 * - Restringe la cámara al propio sitio (la usa el escáner de asistencia).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);
        header_remove('X-Powered-By');   // no revelar la versión de PHP

        $h = $respuesta->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'DENY');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if (config('pac.csp')) {
            $h->set('Content-Security-Policy', $this->politica());
        }

        return $respuesta;
    }

    private function politica(): string
    {
        // En desarrollo (npm run dev) los recursos vienen del servidor de Vite.
        $vite = '';
        if (Vite::isRunningHot()) {
            $origen = rtrim(trim((string) @file_get_contents(public_path('hot'))), '/');
            $vite = ' '.$origen.' '.preg_replace('#^http#', 'ws', $origen);
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self'{$vite}",
            "style-src 'self' 'unsafe-inline'{$vite}",   // atributos style (anchos de barras de progreso)
            "img-src 'self' data: blob:",
            "connect-src 'self'{$vite}",
            "font-src 'self'",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
