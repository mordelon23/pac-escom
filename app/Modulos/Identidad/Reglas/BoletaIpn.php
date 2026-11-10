<?php

namespace App\Modulos\Identidad\Reglas;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** RB3: la boleta consta de exactamente 10 dígitos. Solo se valida el formato. */
class BoletaIpn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{10}$/', (string) $value)) {
            $fail('La boleta debe tener exactamente 10 dígitos.');
        }
    }
}
