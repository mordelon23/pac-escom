<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaPassword;
use Illuminate\View\View;

/** CU14 — Restablecer contraseña (RB13: enlace válido 10 minutos y de un solo uso). */
class ContrasenaController extends Controller
{
    public function solicitar(): View
    {
        return view('auth.olvide-contrasena');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $request->validate(['correo' => ['required', 'email', 'max:120']]);
        Password::sendResetLink(['correo' => mb_strtolower(trim($request->correo))]);

        // Misma respuesta exista o no la cuenta, para no revelar qué correos están registrados.
        return back()->with('estado', 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña (válido 10 minutos).');
    }

    public function formulario(Request $request, string $token): View
    {
        return view('auth.restablecer', ['token' => $token, 'correo' => $request->query('email')]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'correo' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'max:64', ReglaPassword::min(8)->mixedCase()->numbers()],
        ]);

        $estado = Password::reset(
            ['correo' => mb_strtolower(trim($request->correo)), 'password' => $request->password,
                'password_confirmation' => $request->password_confirmation, 'token' => $request->token],
            function (Usuario $usuario, string $password) {
                $usuario->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'intentos_fallidos' => 0])->save();
                event(new PasswordReset($usuario));
            }
        );

        return $estado === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('estado', 'Contraseña actualizada. Ya puede iniciar sesión.')
            : back()->withErrors(['correo' => 'El enlace expiró o no es válido, solicite uno nuevo.']);
    }
}
