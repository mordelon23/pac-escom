<?php

namespace App\Modulos\Comunidad\Enums;

enum EstadoModeracion: string
{
    case PENDIENTE = 'PENDIENTE';
    case APROBADA = 'APROBADA';
    case RECHAZADA = 'RECHAZADA';
}
