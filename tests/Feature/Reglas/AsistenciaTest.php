<?php

namespace Tests\Feature\Reglas;

use App\Modulos\Asistencia\Acciones\AbrirSesionAsistencia;
use App\Modulos\Asistencia\Acciones\RegistrarAsistenciaQR;
use App\Modulos\Asistencia\Acciones\RegistrarContingencia;
use App\Modulos\Asistencia\Enums\MetodoAsistencia;
use App\Modulos\Asistencia\Enums\MotivoContingencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaDatos;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
        $this->travelTo(now()->startOfWeek()->addDay()->setTime(9, 30));   // un martes a las 9:30
    }

    private function escenario(): array
    {
        $a = $this->actividad(['fecha_inicio' => now()->subWeek()->toDateString()]);
        $alumno = $this->usuario();
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $horario = $a->horarios->firstWhere(fn ($h) => $h->dia_semana->value === now()->dayOfWeekIso);
        $sesion = app(AbrirSesionAsistencia::class)->ejecutar($a->responsable, $horario);

        return [$a, $alumno, $sesion];
    }

    public function test_registro_con_token_vigente(): void
    {
        [, $alumno, $sesion] = $this->escenario();
        $token = app(ServicioTokenQR::class)->generar($sesion);

        $asistencia = app(RegistrarAsistenciaQR::class)->ejecutar($alumno, $sesion->id, $token);

        $this->assertSame(MetodoAsistencia::QR, $asistencia->metodo);
    }

    public function test_rb20_token_expirado(): void
    {
        [, $alumno, $sesion] = $this->escenario();
        $token = app(ServicioTokenQR::class)->generar($sesion);
        $this->travel(90)->seconds();

        $this->expectExceptionObject(new ReglaNegocioException('RB20', 'Código expirado, escanee nuevamente.'));
        app(RegistrarAsistenciaQR::class)->ejecutar($alumno, $sesion->id, $token);
    }

    public function test_rb21_asistencia_unica(): void
    {
        [, $alumno, $sesion] = $this->escenario();
        $token = app(ServicioTokenQR::class)->generar($sesion);
        app(RegistrarAsistenciaQR::class)->ejecutar($alumno, $sesion->id, $token);

        try {
            app(RegistrarAsistenciaQR::class)->ejecutar($alumno, $sesion->id, $token);
            $this->fail('Se esperaba RB21');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RB21', $e->regla);
        }
    }

    public function test_rb22_alumno_no_inscrito(): void
    {
        [, , $sesion] = $this->escenario();
        $otro = $this->usuario();

        try {
            app(RegistrarAsistenciaQR::class)->ejecutar($otro, $sesion->id, app(ServicioTokenQR::class)->generar($sesion));
            $this->fail('Se esperaba RB22');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RB22', $e->regla);
        }
    }

    public function test_rb23_contingencia_queda_en_bitacora(): void
    {
        [$a, $alumno, $sesion] = $this->escenario();
        $svc = app(RegistrarContingencia::class);
        $svc->activar($sesion);

        $asistencia = $svc->ejecutar($a->responsable, $sesion->fresh(), $alumno->id, MotivoContingencia::SIN_RED, null);

        $this->assertSame(MetodoAsistencia::MANUAL, $asistencia->metodo);
        $this->assertDatabaseHas('bitacora_contingencias', ['asistencia_id' => $asistencia->id, 'motivo' => 'SIN_RED']);
    }

    public function test_rb23_motivo_otro_exige_detalle(): void
    {
        [$a, $alumno, $sesion] = $this->escenario();

        $this->expectException(ReglaNegocioException::class);
        app(RegistrarContingencia::class)->ejecutar($a->responsable, $sesion, $alumno->id, MotivoContingencia::OTRO, '');
    }
}
