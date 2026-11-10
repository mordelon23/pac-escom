<?php

namespace App\Modulos\Asistencia\Enums;

enum MotivoContingencia: string
{
    case SIN_RED = 'SIN_RED';
    case FALLA_DISPOSITIVO_ALUMNO = 'FALLA_DISPOSITIVO_ALUMNO';
    case FALLA_PROYECTOR = 'FALLA_PROYECTOR';
    case OTRO = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SIN_RED => 'Sin conexión a internet',
            self::FALLA_DISPOSITIVO_ALUMNO => 'Falla del dispositivo del alumno',
            self::FALLA_PROYECTOR => 'Falla del proyector o pantalla',
            self::OTRO => 'Otro',
        };
    }
}
