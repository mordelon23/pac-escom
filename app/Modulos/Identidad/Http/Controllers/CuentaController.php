<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Compartido\Auditoria\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Mi cuenta: consulta de los datos personales registrados (RNF8) y cambio de contraseña (RB2). */
class CuentaController extends Controller
{
    public function show(Request $request): View
    {
        return view('cuenta', ['u' => $request->user()->fresh()]);   // incluye todas las columnas (modo estricto)
    }

    public function actualizarContrasena(Request $request): RedirectResponse
    {
        $request->validate([
            'actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'max:64', 'different:actual', Password::min(8)->mixedCase()->numbers()],
        ], [
            'actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ], ['actual' => 'contraseña actual', 'password' => 'nueva contraseña']);

        $request->user()->forceFill(['password' => $request->password])->save();
        $request->session()->regenerate();
        Auditor::registrar('cuenta.cambiar_contrasena', 'usuarios', $request->user()->id);

        return back()->with('estado', 'Tu contraseña se actualizó correctamente.');
    }
}
