<?php

namespace App\Modulos\Inscripciones\Enums;

enum EstadoInscripcion: string
{
    case ACTIVA = 'ACTIVA';
    case CANCELADA = 'CANCELADA';
    case CONCLUIDA = 'CONCLUIDA';
}
