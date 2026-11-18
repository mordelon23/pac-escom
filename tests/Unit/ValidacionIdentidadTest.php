<?php

namespace Tests\Unit;

use App\Modulos\Identidad\Reglas\BoletaIpn;
use App\Modulos\Identidad\Reglas\CorreoAlumnoIpn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** RB1 y RB3: formato del correo institucional y de la boleta. */
class ValidacionIdentidadTest extends TestCase
{
    private function falla(object $regla, string $valor): bool
    {
        $fallo = false;
        $regla->validate('campo', $valor, function () use (&$fallo) {
            $fallo = true;
        });

        return $fallo;
    }

    #[DataProvider('correos')]
    public function test_rb1_dominio_del_correo_de_alumno(string $correo, bool $valido): void
    {
        $this->assertSame(! $valido, $this->falla(new CorreoAlumnoIpn, $correo));
    }

    public static function correos(): array
    {
        return [
            'dominio correcto' => ['jperez2301@alumno.ipn.mx', true],
            'mayúsculas' => ['JPerez@ALUMNO.IPN.MX', true],
            'dominio del personal' => ['jperez@ipn.mx', false],
            'subdominio falso' => ['jperez@alumno.ipn.mx.com', false],
            'gmail' => ['jperez@gmail.com', false],
            'sin usuario' => ['@alumno.ipn.mx', false],
            'doble arroba' => ['a@b@alumno.ipn.mx', false],
        ];
    }

    #[DataProvider('boletas')]
    public function test_rb3_formato_de_boleta(string $boleta, bool $valida): void
    {
        $this->assertSame(! $valida, $this->falla(new BoletaIpn, $boleta));
    }

    public static function boletas(): array
    {
        return [
            '10 dígitos' => ['2023630101', true],
            '9 dígitos' => ['202363010', false],
            '11 dígitos' => ['20236301011', false],
            'con letra' => ['2023A30101', false],
            'con espacios' => ['2023 630101', false],
        ];
    }
}
