<?php

namespace App\Modulos\Actividades\Enums;

/** Días hábiles en que puede impartirse una actividad (RB26). Valor ISO-8601: 1 = lunes. */
enum DiaSemana: int
{
    case LUNES = 1;
    case MARTES = 2;
    case MIERCOLES = 3;
    case JUEVES = 4;
    case VIERNES = 5;

    public function etiqueta(): string
    {
        return match ($this) {
            self::LUNES => 'Lunes',
            self::MARTES => 'Martes',
            self::MIERCOLES => 'Miércoles',
            self::JUEVES => 'Jueves',
            self::VIERNES => 'Viernes',
        };
    }

    public function abreviatura(): string
    {
        return mb_substr($this->etiqueta(), 0, 3);
    }
}
