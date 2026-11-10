<?php

namespace App\Modulos\Actividades\Enums;

enum EstadoActividad: string
{
    case BORRADOR = 'BORRADOR';
    case PUBLICADA = 'PUBLICADA';
    case CANCELADA = 'CANCELADA';
    case FINALIZADA = 'FINALIZADA';

    public function etiqueta(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
