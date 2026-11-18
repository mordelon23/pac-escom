<?php

namespace Tests\Feature\Reglas;

use App\Modulos\Actividades\Acciones\CancelarActividad;
use App\Modulos\Actividades\Acciones\GuardarActividad;
use App\Modulos\Actividades\Acciones\PublicarActividad;
use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Notificaciones\ActividadCancelada;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreaDatos;
use Tests\TestCase;

class ActividadesTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
        Storage::fake('public');
    }

    private function datos(array $horarios = [[1, '14:00', '16:00'], [3, '14:00', '16:00']]): array
    {
        return [
            'categoria_id' => \App\Modulos\Actividades\Modelos\Categoria::firstOrCreate(['nombre' => 'Teatro'])->id,
            'responsable_id' => $this->usuario(RolSistema::RESPONSABLE)->id,
            'titulo' => 'Taller de improvisación', 'descripcion' => 'Descripción suficientemente larga.', 'lugar' => 'Auditorio',
            'fecha_inicio' => now()->addWeek()->toDateString(), 'fecha_fin' => now()->addMonths(2)->toDateString(), 'cupo_maximo' => 20,
            'horarios' => array_map(fn ($h) => ['dia_semana' => $h[0], 'hora_inicio' => $h[1], 'hora_fin' => $h[2]], $horarios),
        ];
    }

    public function test_cu11_crear_actividad_queda_en_borrador_con_horarios(): void
    {
        $coord = $this->usuario(RolSistema::COORDINADOR);
        $a = app(GuardarActividad::class)->ejecutar($coord, $this->datos(), UploadedFile::fake()->image('a.jpg'));

        $this->assertSame(EstadoActividad::BORRADOR, $a->estado);
        $this->assertCount(2, $a->horarios);
        Storage::disk('public')->assertExists($a->imagen_ruta);
        $this->assertDatabaseHas('auditoria', ['accion' => 'actividad.crear', 'entidad_id' => $a->id]);
    }

    public function test_rb10_publicar_hace_visible_la_actividad(): void
    {
        $a = app(GuardarActividad::class)->ejecutar($this->usuario(RolSistema::COORDINADOR), $this->datos(), UploadedFile::fake()->image('a.jpg'));
        app(PublicarActividad::class)->ejecutar($a);
        $this->assertSame(EstadoActividad::PUBLICADA, $a->fresh()->estado);

        $this->expectException(ReglaNegocioException::class);   // no se publica dos veces
        app(PublicarActividad::class)->ejecutar($a->fresh());
    }

    public function test_rb5_no_se_reduce_el_cupo_por_debajo_de_los_inscritos(): void
    {
        $coord = $this->usuario(RolSistema::COORDINADOR);
        $a = $this->actividad(['cupo_maximo' => 5]);
        app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);
        app(InscribirAlumno::class)->ejecutar($this->usuario(), $a->id);

        $this->expectException(ReglaNegocioException::class);
        app(GuardarActividad::class)->ejecutar($coord, array_merge($this->datos(), ['cupo_maximo' => 1]), null, $a);
    }

    public function test_rb26_no_se_quita_un_horario_con_sesiones(): void
    {
        $coord = $this->usuario(RolSistema::COORDINADOR);
        $a = $this->actividad([], [1, 2]);
        SesionAsistencia::create(['horario_id' => $a->horarios->first()->id, 'abierta_por' => $coord->id, 'fecha' => now()->toDateString(),
            'inicio' => now(), 'secreto_hmac' => ServicioTokenQR::nuevoSecreto(), 'estado' => 'CERRADA']);

        try {
            app(GuardarActividad::class)->ejecutar($coord, $this->datos([[2, '09:00', '11:00']]), null, $a);
            $this->fail('Se esperaba RB26');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RB26', $e->regla);
        }
        $this->assertCount(2, $a->fresh()->horarios);   // la transacción se revirtió
    }

    public function test_rb8_cancelar_actividad_cancela_inscripciones_y_notifica(): void
    {
        $a = $this->actividad();
        $alumnos = [$this->usuario(), $this->usuario()];
        foreach ($alumnos as $al) {
            app(InscribirAlumno::class)->ejecutar($al, $a->id);
        }

        $n = app(CancelarActividad::class)->ejecutar($a);

        $this->assertSame(2, $n);
        $this->assertSame(EstadoActividad::CANCELADA, $a->fresh()->estado);
        $this->assertSame(0, $a->inscripciones()->where('estado', EstadoInscripcion::ACTIVA)->count());
        Notification::assertSentTo($alumnos, ActividadCancelada::class);
    }
}
