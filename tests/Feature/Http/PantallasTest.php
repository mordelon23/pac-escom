<?php

namespace Tests\Feature\Http;

use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreaDatos;
use Tests\TestCase;

/** Verifica que cada pantalla del prototipo responde para su rol. */
class PantallasTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
        $this->travelTo(now()->startOfWeek()->addDay()->setTime(9, 30));
    }

    public function test_pantallas_del_alumno(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad(['fecha_inicio' => now()->subWeek()->toDateString()]);
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $this->actingAs($alumno);

        foreach (['/inicio', '/actividades', "/actividades/{$a->id}", '/mis-actividades', '/calendario', '/calendario?mias=1', '/agenda', '/muro', '/asistencia/escanear'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post("/agenda/{$a->id}")->assertRedirect();
        $this->assertDatabaseHas('agenda', ['usuario_id' => $alumno->id, 'actividad_id' => $a->id]);
    }

    public function test_pantallas_de_coordinacion_y_csv(): void
    {
        $this->actingAs($this->usuario(RolSistema::COORDINADOR));
        $a = $this->actividad();
        foreach (['/coordinacion', '/coordinacion/actividades', '/coordinacion/actividades/create', "/coordinacion/actividades/{$a->id}",
            "/coordinacion/actividades/{$a->id}/edit", '/coordinacion/reportes', '/coordinacion/reportes?formato=imprimir', '/coordinacion/muro', '/responsable'] as $url) {
            $this->get($url)->assertOk();
        }
        $csv = $this->get('/coordinacion/reportes?formato=csv');
        $csv->assertOk();
        $this->assertStringContainsString('Actividad,Categoría', $csv->streamedContent());
        $this->get('/coordinacion/usuarios')->assertForbidden();   // solo superadministrador
    }

    public function test_superadministrador_ve_usuarios(): void
    {
        $this->actingAs($this->usuario(RolSistema::SUPERADMINISTRADOR))->get('/coordinacion/usuarios')->assertOk();
    }

    public function test_flujo_qr_responsable_y_alumno_por_http(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad(['fecha_inicio' => now()->subWeek()->toDateString()]);
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $horario = $a->horarios->firstWhere(fn ($h) => $h->dia_semana->value === now()->dayOfWeekIso);

        $this->actingAs($a->responsable)->post("/responsable/horarios/{$horario->id}/sesiones")->assertRedirect();
        $sesion = SesionAsistencia::first();
        $this->get("/responsable/sesiones/{$sesion->id}")->assertOk();
        $estado = $this->getJson("/responsable/sesiones/{$sesion->id}/estado")->assertOk()->json();
        $this->assertStringContainsString('asistencia/escanear?s='.$sesion->id, $estado['url']);

        $token = app(ServicioTokenQR::class)->generar($sesion);
        $this->actingAs($alumno)->postJson('/asistencia', ['sesion' => $sesion->id, 'token' => $token])->assertCreated()->assertJson(['ok' => true]);
        $this->actingAs($alumno)->postJson('/asistencia', ['sesion' => $sesion->id, 'token' => $token])->assertStatus(422);   // RB21

        // otro responsable no puede ver la sesión (RB17)
        $this->actingAs($this->usuario(RolSistema::RESPONSABLE))->get("/responsable/sesiones/{$sesion->id}")->assertForbidden();
    }

    public function test_crear_actividad_por_formulario(): void
    {
        Storage::fake('public');
        $coord = $this->usuario(RolSistema::COORDINADOR);
        $resp = $this->usuario(RolSistema::RESPONSABLE);
        $cat = Categoria::firstOrCreate(['nombre' => 'Danza']);

        $this->actingAs($coord)->post('/coordinacion/actividades', [
            'titulo' => 'Danza contemporánea', 'descripcion' => 'Exploración del movimiento y la expresión corporal.',
            'categoria_id' => $cat->id, 'responsable_id' => $resp->id, 'lugar' => 'Explanada',
            'fecha_inicio' => now()->addDay()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'cupo_maximo' => 20,
            'imagen' => UploadedFile::fake()->image('d.png'),
            'dias' => [2 => ['activo' => 1, 'inicio' => '13:00', 'fin' => '15:00'], 4 => ['activo' => 1, 'inicio' => '13:00', 'fin' => '15:00']],
        ])->assertRedirect();
        $this->assertDatabaseHas('actividades', ['titulo' => 'Danza contemporánea', 'estado' => 'BORRADOR']);
        $this->assertDatabaseCount('horarios', 2);

        // horario inválido: fin antes que inicio y sin días
        $this->post('/coordinacion/actividades', ['titulo' => 'X', 'dias' => [1 => ['activo' => 1, 'inicio' => '15:00', 'fin' => '14:00']]])
            ->assertSessionHasErrors(['titulo', 'dias.1', 'imagen']);
    }
}
