<?php

namespace App\Modulos\Identidad\Reglas;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** RB1: el personal (coordinadores y responsables) usa el dominio @ipn.mx. */
class CorreoPersonalIpn implements ValidationRule
{
    public const DOMINIO = 'ipn.mx';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $partes = explode('@', mb_strtolower(trim((string) $value)));
        if (count($partes) !== 2 || $partes[0] === '' || $partes[1] !== self::DOMINIO) {
            $fail('Utilice un correo institucional del personal (@' . self::DOMINIO . ').');
        }
    }
}
