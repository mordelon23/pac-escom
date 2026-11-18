<?php

namespace Tests\Feature\Reglas;

use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Comunidad\Acciones\ModerarPublicacion;
use App\Modulos\Comunidad\Acciones\PublicarEnMuro;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Notificaciones\PublicacionRechazada;
use App\Modulos\Identidad\Acciones\GestionarUsuarios;
use App\Modulos\Identidad\Enums\EstadoCuenta;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaDatos;
use Tests\TestCase;

class ComunidadUsuariosTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
    }

    public function test_rb16_publicacion_queda_pendiente_y_requiere_inscripcion(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad();

        try {
            app(PublicarEnMuro::class)->ejecutar($alumno, $a->id, 'Hola', null);
            $this->fail('Debe exigir inscripción');
        } catch (ReglaNegocioException) {
        }

        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $p = app(PublicarEnMuro::class)->ejecutar($alumno, $a->id, '  Excelente taller  ', null);
        $this->assertSame(EstadoModeracion::PENDIENTE, $p->estado_moderacion);
        $this->assertSame('Excelente taller', $p->contenido);
    }

    public function test_cu13_rechazo_notifica_al_autor(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad();
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $p = app(PublicarEnMuro::class)->ejecutar($alumno, $a->id, 'Texto', null);

        app(ModerarPublicacion::class)->rechazar($this->usuario(RolSistema::COORDINADOR), $p, 'Lenguaje inapropiado');

        $this->assertSame(EstadoModeracion::RECHAZADA, $p->fresh()->estado_moderacion);
        Notification::assertSentTo($alumno, PublicacionRechazada::class);
    }

    public function test_cu15_alta_de_personal_y_cambio_de_rol(): void
    {
        $super = $this->usuario(RolSistema::SUPERADMINISTRADOR);
        [$nuevo] = app(GestionarUsuarios::class)->crear(['nombre' => 'Ana', 'apellidos' => 'López', 'correo' => 'ALopez@ipn.mx', 'rol' => 'responsable']);
        $this->assertSame('alopez@ipn.mx', $nuevo->correo);
        $this->assertTrue($nuevo->tieneRol(RolSistema::RESPONSABLE));

        app(GestionarUsuarios::class)->cambiarRol($super, $nuevo, RolSistema::COORDINADOR);
        $this->assertTrue($nuevo->fresh()->esCoordinador());
    }

    public function test_rb25_no_puede_modificarse_a_si_mismo_ni_volver_alumno_personal(): void
    {
        $super = $this->usuario(RolSistema::SUPERADMINISTRADOR);
        $svc = app(GestionarUsuarios::class);

        $this->assertLanza(fn () => $svc->cambiarEstado($super, $super, EstadoCuenta::SUSPENDIDA));
        $this->assertLanza(fn () => $svc->cambiarRol($super, $this->usuario(), RolSistema::COORDINADOR));   // un alumno no se vuelve personal
        $this->assertLanza(fn () => $svc->cambiarRol($super, $this->usuario(RolSistema::RESPONSABLE), RolSistema::SUPERADMINISTRADOR));
    }

    private function assertLanza(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Se esperaba RB25');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RB25', $e->regla);
        }
    }
}
