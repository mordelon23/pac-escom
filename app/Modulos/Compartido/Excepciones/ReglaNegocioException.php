<?php

namespace App\Modulos\Compartido\Excepciones;

use RuntimeException;

/**
 * Se lanza cuando una operación incumple una regla de negocio del plan de proyecto.
 * $regla guarda la referencia (p. ej. "RB5") para mostrarla en mensajes y pruebas.
 */
class ReglaNegocioException extends RuntimeException
{
    public function __construct(public readonly string $regla, string $mensaje)
    {
        parent::__construct($mensaje);
    }
}
