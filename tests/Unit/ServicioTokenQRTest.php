<?php

namespace Tests\Unit;

use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use PHPUnit\Framework\TestCase;

/** RB20: el token cambia cada 30 s y se acepta la ventana actual y la anterior. */
class ServicioTokenQRTest extends TestCase
{
    private function sesion(int $id = 7, ?string $secreto = null): SesionAsistencia
    {
        $s = new SesionAsistencia;
        $s->id = $id;
        $s->secreto_hmac = $secreto ?? str_repeat('a', 64);

        return $s;
    }

    public function test_token_valido_en_la_misma_ventana(): void
    {
        $svc = new ServicioTokenQR;
        $t = 1_800_000_010;
        $this->assertTrue($svc->validar($this->sesion(), $svc->generar($this->sesion(), $t), $t + 15));
    }

    public function test_se_acepta_la_ventana_anterior(): void
    {
        $svc = new ServicioTokenQR;
        $t = 1_800_000_010;
        $this->assertTrue($svc->validar($this->sesion(), $svc->generar($this->sesion(), $t), $t + 30));
    }

    public function test_token_de_hace_dos_ventanas_expira(): void
    {
        $svc = new ServicioTokenQR;
        $t = 1_800_000_010;
        $this->assertFalse($svc->validar($this->sesion(), $svc->generar($this->sesion(), $t), $t + 61));
    }

    public function test_token_de_otra_sesion_no_sirve(): void
    {
        $svc = new ServicioTokenQR;
        $t = 1_800_000_010;
        $token = $svc->generar($this->sesion(7, str_repeat('a', 64)), $t);
        $this->assertFalse($svc->validar($this->sesion(8, str_repeat('b', 64)), $token, $t));
    }

    public function test_token_con_formato_invalido(): void
    {
        $this->assertFalse((new ServicioTokenQR)->validar($this->sesion(), "' OR 1=1 --"));
    }
}
