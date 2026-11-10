<?php

namespace App\Modulos\Asistencia\Enums;

enum EstadoSesion: string
{
    case ABIERTA = 'ABIERTA';
    case CONTINGENCIA = 'CONTINGENCIA';
    case CERRADA = 'CERRADA';
}
