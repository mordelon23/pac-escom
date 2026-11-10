<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Http\Requests\RegistroAlumnoRequest;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** CU2 — Registrarse como alumno. */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroAlumnoRequest $request): RedirectResponse
    {
        $usuario = Usuario::create([
            'rol_id' => Rol::where('clave', RolSistema::ALUMNO->value)->value('id'),
            ...$request->safe()->only(['matricula', 'nombre', 'apellidos', 'correo', 'password']),
        ]);

        if (config('pac.verificar_correo')) {
            event(new Registered($usuario));   // envía el correo de verificación
        } else {
            $usuario->markEmailAsVerified();
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('alumno.inicio')->with('estado', 'Cuenta creada. ¡Bienvenido(a) al PAC!');
    }
}
