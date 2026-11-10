<?php

namespace App\Modulos\Identidad\Reglas;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * RB1: el correo del alumno debe pertenecer exactamente al dominio @alumno.ipn.mx.
 * Solo se valida la parte posterior a la arroba (no se consulta ningún padrón).
 */
class CorreoAlumnoIpn implements ValidationRule
{
    public const DOMINIO = 'alumno.ipn.mx';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $partes = explode('@', mb_strtolower(trim((string) $value)));
        if (count($partes) !== 2 || $partes[0] === '' || $partes[1] !== self::DOMINIO) {
            $fail('Utilice su correo institucional con dominio @' . self::DOMINIO . '.');
        }
    }
}
