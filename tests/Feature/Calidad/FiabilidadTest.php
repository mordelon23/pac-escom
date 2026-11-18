<?php

namespace Tests\Feature\Calidad;

use App\Modulos\Actividades\Acciones\CancelarActividad;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\Support\CreaDatos;
use Tests\TestCase;

/** ISO 9126 · Fiabilidad › Tolerancia a fallos, capacidad de recuperación y madurez. */
class FiabilidadTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    /** Simula un servidor de correo caído. */
    private function correoCaido(): void
    {
        Mail::extend('caido', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Servidor SMTP no disponible');
            }

            public function __toString(): string
            {
                return 'caido://';
            }
        });
        config(['mail.mailers.caido' => ['transport' => 'caido'], 'mail.default' => 'caido']);
    }

    public function test_la_inscripcion_se_guarda_aunque_falle_el_correo(): void
    {
        $this->correoCaido();
        Log::spy();
        $alumno = $this->usuario();
        $a = $this->actividad();

        $this->actingAs($alumno)->post("/actividades/{$a->id}/inscripciones")->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inscripciones', ['usuario_id' => $alumno->id, 'actividad_id' => $a->id, 'estado' => 'ACTIVA']);
        $this->assertSame(1, $a->fresh()->cupo_ocupado);
        Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'InscripcionConfirmada'));
    }

    public function test_recuperar_contrasena_avisa_si_no_hay_correo(): void
    {
        $this->correoCaido();
        $u = $this->usuario();
        $this->post('/olvide-contrasena', ['correo' => $u->correo])->assertSessionHasErrors('correo');
    }

    public function test_d11_cancelar_actividad_deja_el_cupo_en_cero(): void
    {
        $a = $this->actividad();
        app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);
        app(CancelarActividad::class)->ejecutar($a->fresh());
        $this->assertSame(0, $a->fresh()->cupo_ocupado);
    }

    public function test_paginas_de_error_en_espanol_sin_detalles_internos(): void
    {
        config(['app.debug' => false]);
        $this->get('/no-existe')->assertNotFound()->assertSee('Página no encontrada');
        $this->actingAs($this->usuario())->get('/coordinacion')->assertForbidden()->assertSee('No tienes acceso');
        foreach ([419 => 'Tu sesión expiró', 429 => 'Demasiadas solicitudes', 500 => 'Algo salió mal', 503 => 'mantenimiento'] as $codigo => $texto) {
            $this->assertStringContainsString($texto, view("errors.{$codigo}")->render());
        }
    }

    public function test_monitor_de_salud_revisa_la_base_de_datos(): void
    {
        $this->get('/up')->assertOk();
    }
}
