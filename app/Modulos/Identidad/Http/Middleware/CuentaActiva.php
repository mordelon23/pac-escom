<?php

namespace App\Modulos\Identidad\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Cierra la sesión si la cuenta fue suspendida mientras el usuario estaba conectado. */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->estaActiva()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['correo' => 'Su cuenta está suspendida, contacte a la coordinación.']);
        }

        return $next($request);
    }
}
