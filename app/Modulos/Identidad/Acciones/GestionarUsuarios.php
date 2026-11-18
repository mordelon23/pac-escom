<?php

namespace App\Modulos\Identidad\Acciones;

use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Compartido\Notificaciones\Notificador;
use App\Modulos\Identidad\Enums\EstadoCuenta;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** CU15 — Gestionar usuarios y roles del personal (RF16, RB25). */
class GestionarUsuarios
{
    public const ROLES_PERSONAL = [RolSistema::RESPONSABLE, RolSistema::COORDINADOR];

    /** Crea la cuenta y envía un enlace para que el usuario defina su contraseña. Devuelve la contraseña temporal. */
    public function crear(array $datos): array
    {
        $temporal = Str::password(12, symbols: false);
        $usuario = Usuario::create([
            'rol_id' => Rol::where('clave', $datos['rol'])->value('id'),
            'nombre' => $datos['nombre'], 'apellidos' => $datos['apellidos'],
            'correo' => mb_strtolower($datos['correo']), 'password' => $temporal,
        ]);
        $usuario->markEmailAsVerified();
        Notificador::intentar(fn () => Password::sendResetLink(['correo' => $usuario->correo]), 'enlace para definir contraseña');
        Auditor::registrar('usuario.crear', 'usuarios', $usuario->id, ['rol' => $datos['rol']]);

        return [$usuario, $temporal];
    }

    public function cambiarRol(Usuario $actor, Usuario $usuario, RolSistema $rol): void
    {
        $this->noSobreSiMismo($actor, $usuario);
        if ($usuario->esAlumno() || ! in_array($rol, self::ROLES_PERSONAL, true)) {
            throw new ReglaNegocioException('RB25', 'Solo se puede asignar el rol de responsable o coordinador a cuentas del personal.');
        }
        $anterior = $usuario->rol->clave;
        $usuario->update(['rol_id' => Rol::where('clave', $rol->value)->value('id')]);
        Auditor::registrar('usuario.cambiar_rol', 'usuarios', $usuario->id, ['de' => $anterior, 'a' => $rol->value]);
    }

    public function cambiarEstado(Usuario $actor, Usuario $usuario, EstadoCuenta $estado): void
    {
        $this->noSobreSiMismo($actor, $usuario);
        if ($usuario->esSuperadministrador()) {
            throw new ReglaNegocioException('RB25', 'No se puede suspender a un superadministrador.');
        }
        $usuario->update(['estado' => $estado]);
        Auditor::registrar($estado === EstadoCuenta::SUSPENDIDA ? 'usuario.suspender' : 'usuario.reactivar', 'usuarios', $usuario->id);
    }

    private function noSobreSiMismo(Usuario $actor, Usuario $usuario): void
    {
        if ($actor->id === $usuario->id) {
            throw new ReglaNegocioException('RB25', 'No puede modificar su propia cuenta.');
        }
    }
}
