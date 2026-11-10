<?php

namespace App\Modulos\Identidad\Enums;

/** Claves de los roles del sistema (tabla roles.clave). */
enum RolSistema: string
{
    case ALUMNO = 'alumno';
    case RESPONSABLE = 'responsable';
    case COORDINADOR = 'coordinador';
    case SUPERADMINISTRADOR = 'superadministrador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ALUMNO => 'Alumno',
            self::RESPONSABLE => 'Responsable de grupo',
            self::COORDINADOR => 'Coordinador',
            self::SUPERADMINISTRADOR => 'Superadministrador',
        };
    }
}
