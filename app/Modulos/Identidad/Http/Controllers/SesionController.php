<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** CU1 — Iniciar y cerrar sesión (RB12, RB14, RB24). */
class SesionController extends Controller
{
    public const MAX_INTENTOS_POR_MINUTO = 5;     // RB24
    public const MINUTOS_BLOQUEO = 5;              // RB24
    public const INTENTOS_PARA_SUGERIR_RESTABLECER = 3;   // RB12

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'string', 'email', 'max:120'],
            'password' => ['required', 'string', 'max:64'],
        ]);
        $correo = mb_strtolower(trim($datos['correo']));
        $llave = 'login:' . Str::transliterate($correo) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS_POR_MINUTO)) {
            $min = (int) ceil(RateLimiter::availableIn($llave) / 60);

            return back()->withInput($request->only('correo'))
                ->withErrors(['correo' => "Demasiados intentos. Espere {$min} minuto(s) e intente de nuevo."]);
        }

        $usuario = Usuario::where('correo', $correo)->first();

        if (! Auth::attempt(['correo' => $correo, 'password' => $datos['password']], $request->boolean('recordar'))) {
            RateLimiter::hit($llave, self::MINUTOS_BLOQUEO * 60);
            $usuario?->increment('intentos_fallidos');

            return back()->withInput($request->only('correo'))
                ->withErrors(['correo' => 'Correo o contraseña incorrectos.'])   // no indica cuál de los dos falló
                ->with('sugerir_restablecer', ($usuario?->intentos_fallidos ?? 0) >= self::INTENTOS_PARA_SUGERIR_RESTABLECER);
        }

        $usuario = Auth::user();
        if (! $usuario->estaActiva()) {
            Auth::logout();

            return back()->withErrors(['correo' => 'Su cuenta está suspendida, contacte a la coordinación.']);
        }

        RateLimiter::clear($llave);
        $usuario->forceFill(['intentos_fallidos' => 0])->save();
        $request->session()->regenerate();   // evita la fijación de sesión

        return redirect()->intended(self::rutaInicio($usuario));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Sesión cerrada correctamente.');
    }

    public static function rutaInicio(Usuario $usuario): string
    {
        return match (true) {
            $usuario->esAlumno() => route('alumno.inicio'),
            $usuario->esCoordinador() => route('coordinacion.inicio'),
            default => route('responsable.inicio'),
        };
    }
}
