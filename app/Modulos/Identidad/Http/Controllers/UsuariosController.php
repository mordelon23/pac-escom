<?php

namespace App\Modulos\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Acciones\GestionarUsuarios;
use App\Modulos\Identidad\Enums\EstadoCuenta;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Identidad\Reglas\CorreoPersonalIpn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU15 — Panel del superadministrador para la rotación semestral del personal. */
class UsuariosController extends Controller
{
    public function index(Request $request): View
    {
        $rol = RolSistema::tryFrom((string) $request->query('rol'));
        $usuarios = Usuario::with('rol')
            ->when($rol, fn ($q) => $q->whereHas('rol', fn ($r) => $r->where('clave', $rol->value)))
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->whereRaw('nombre ILIKE ?', ["%$t%"])
                ->orWhereRaw('apellidos ILIKE ?', ["%$t%"])->orWhereRaw('correo ILIKE ?', ["%$t%"])->orWhere('matricula', $t)))
            ->orderBy('apellidos')->paginate(15)->withQueryString();

        return view('coordinacion.usuarios', [
            'usuarios' => $usuarios, 'rol' => $rol,
            'conteos' => Rol::withCount('usuarios')->pluck('usuarios_count', 'clave'),
        ]);
    }

    public function store(Request $request, GestionarUsuarios $accion): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:60'],
            'apellidos' => ['required', 'string', 'min:2', 'max:80'],
            'correo' => ['required', 'string', 'max:120', new CorreoPersonalIpn, 'unique:usuarios,correo'],
            'rol' => ['required', Rule::in(array_map(fn ($r) => $r->value, GestionarUsuarios::ROLES_PERSONAL))],
        ], ['correo.unique' => 'Ya existe un usuario con ese correo.']);

        [$usuario, $temporal] = $accion->crear($datos);

        return back()->with('estado', "Cuenta creada para {$usuario->nombreCompleto()}. Se envió un enlace para definir su contraseña. Contraseña temporal: {$temporal}");
    }

    public function update(Request $request, Usuario $usuario, GestionarUsuarios $accion): RedirectResponse
    {
        $datos = $request->validate([
            'rol' => ['nullable', Rule::enum(RolSistema::class)],
            'estado' => ['nullable', Rule::enum(EstadoCuenta::class)],
        ]);
        try {
            if (! empty($datos['rol'])) {
                $accion->cambiarRol($request->user(), $usuario, RolSistema::from($datos['rol']));
            }
            if (! empty($datos['estado'])) {
                $accion->cambiarEstado($request->user(), $usuario, EstadoCuenta::from($datos['estado']));
            }
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Usuario actualizado correctamente.');
    }
}
