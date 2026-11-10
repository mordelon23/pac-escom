<?php

namespace Tests\Feature\Reglas;

use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Inscripciones\Acciones\CancelarInscripcion;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Notificaciones\CupoPorAgotarse;
use App\Modulos\Inscripciones\Notificaciones\InscripcionConfirmada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaDatos;
use Tests\TestCase;

class InscripcionesTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
    }

    private function falla(string $regla, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Se esperaba el incumplimiento de {$regla}");
        } catch (ReglaNegocioException $e) {
            $this->assertSame($regla, $e->regla);
        }
    }

    public function test_inscripcion_exitosa_incrementa_cupo_y_envia_correo(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad();

        $i = app(InscribirAlumno::class)->ejecutar($alumno, $a->id);

        $this->assertSame(EstadoInscripcion::ACTIVA, $i->estado);
        $this->assertSame(1, $a->fresh()->cupo_ocupado);
        Notification::assertSentTo($alumno, InscripcionConfirmada::class);
    }

    public function test_rb4_no_puede_inscribirse_dos_veces(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad();
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);

        $this->falla('RB4', fn () => app(InscribirAlumno::class)->ejecutar($alumno, $a->id));
        $this->assertSame(1, $a->fresh()->cupo_ocupado);
    }

    public function test_rb5_no_hay_sobreinscripcion(): void
    {
        $a = $this->actividad(['cupo_maximo' => 2]);
        app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);
        app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);

        $this->falla('RB5', fn () => app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id));
        $this->assertSame(2, $a->fresh()->cupo_ocupado);
    }

    public function test_rb6_maximo_cinco_inscripciones_activas(): void
    {
        $alumno = $this->usuario();
        for ($k = 0; $k < 5; $k++) {
            app(InscribirAlumno::class)->ejecutar($alumno, $this->actividad()->id);
        }

        $this->falla('RB6', fn () => app(InscribirAlumno::class)->ejecutar($alumno, $this->actividad()->id));
    }

    public function test_rb19_aviso_al_coordinador_al_90_por_ciento(): void
    {
        $a = $this->actividad(['cupo_maximo' => 10]);
        for ($k = 0; $k < 9; $k++) {
            app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);
        }

        Notification::assertSentTimes(CupoPorAgotarse::class, 1);
    }

    public function test_rb9_cancelacion_con_24_horas_libera_lugar(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad();   // inicia en 3 días
        $i = app(InscribirAlumno::class)->ejecutar($alumno, $a->id);

        app(CancelarInscripcion::class)->ejecutar($alumno, $i);

        $this->assertSame(EstadoInscripcion::CANCELADA, $i->fresh()->estado);
        $this->assertSame(0, $a->fresh()->cupo_ocupado);
    }

    public function test_rb9_no_se_cancela_a_menos_de_24_horas(): void
    {
        $this->travelTo(now()->startOfWeek()->addDay()->setTime(8, 0));   // martes 08:00
        $alumno = $this->usuario();
        $a = $this->actividad(['fecha_inicio' => now()->toDateString()], [2]);   // martes 09:00
        $i = app(InscribirAlumno::class)->ejecutar($alumno, $a->id);

        $this->falla('RB9', fn () => app(CancelarInscripcion::class)->ejecutar($alumno, $i));
    }
}
