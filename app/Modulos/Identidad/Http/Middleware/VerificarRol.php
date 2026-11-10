<?php

namespace App\Modulos\Identidad\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por rol (RBAC). Uso en rutas: ->middleware('rol:alumno') o 'rol:coordinador,superadministrador'.
 * El superadministrador hereda los accesos del coordinador.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        if (! $usuario) {
            return redirect()->route('login');
        }
        if (in_array('coordinador', $roles, true)) {
            $roles[] = 'superadministrador';
        }
        abort_unless($usuario->tieneRol(...$roles), 403, 'No tiene permiso para acceder a esta sección.');

        return $next($request);
    }
}
