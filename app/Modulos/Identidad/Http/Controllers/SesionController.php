<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Compartido\Auditoria\Auditor;
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
        $llave = 'login:'.Str::transliterate($correo).'|'.$request->ip();

        $reglas = config('pac.reglas');
        if (RateLimiter::tooManyAttempts($llave, $reglas['intentos_por_minuto'])) {
            $min = (int) ceil(RateLimiter::availableIn($llave) / 60);
            Auditor::registrar('sesion.bloqueada', 'usuarios', Usuario::where('correo', $correo)->value('id'));

            return back()->withInput($request->only('correo'))
                ->withErrors(['correo' => "Demasiados intentos. Espere {$min} minuto(s) e intente de nuevo."]);
        }

        $usuario = Usuario::where('correo', $correo)->first();

        if (! Auth::attempt(['correo' => $correo, 'password' => $datos['password']], $request->boolean('recordar'))) {
            RateLimiter::hit($llave, $reglas['minutos_bloqueo'] * 60);
            $usuario?->increment('intentos_fallidos');
            Auditor::registrar('sesion.fallida', 'usuarios', $usuario?->id);   // trazabilidad de intentos (ISO/IEC 27002, 8.15)

            return back()->withInput($request->only('correo'))
                ->withErrors(['correo' => 'Correo o contraseña incorrectos.'])   // no indica cuál de los dos falló
                ->with('sugerir_restablecer', ($usuario?->intentos_fallidos ?? 0) >= $reglas['intentos_para_sugerir_restablecer']);
        }

        $usuario = Auth::user();
        if (! $usuario->estaActiva()) {
            Auth::logout();

            return back()->withErrors(['correo' => 'Su cuenta está suspendida, contacte a la coordinación.']);
        }

        RateLimiter::clear($llave);
        $usuario->forceFill(['intentos_fallidos' => 0])->save();
        $request->session()->regenerate();   // evita la fijación de sesión
        Auditor::registrar('sesion.inicio', 'usuarios', $usuario->id);

        return redirect()->intended(self::rutaInicio($usuario));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auditor::registrar('sesion.cierre', 'usuarios', $request->user()?->id);
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
