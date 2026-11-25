<?php

namespace Tests\Feature\Requisitos;

use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Asistencia\Modelos\Asistencia;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Acciones\CancelarInscripcion;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use App\Modulos\Inscripciones\Notificaciones\RecordatorioActividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaDatos;
use Tests\TestCase;

/** Requisitos del plan que se completaron en el cierre del desarrollo: RF6, RF7, RF8, RF9, RF12 y RNF8. */
class CompletitudTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
    }

    public function test_rf8_recordatorio_24_horas_antes_una_sola_vez(): void
    {
        Carbon::setTestNow(Carbon::parse('next monday 10:00'));
        $manana = $this->actividad(['fecha_inicio' => now()->addDay()->toDateString()], [2]);    // martes 09:00 → 23 h
        $lejana = $this->actividad(['fecha_inicio' => now()->addDays(3)->toDateString()], [4]);  // jueves → fuera de rango
        [$a1, $a2, $a3] = [$this->usuario(), $this->usuario(), $this->usuario()];
        foreach ([$a1, $a2, $a3] as $al) {
            app(InscribirAlumno::class)->ejecutar($al, $manana->id);
        }
        app(InscribirAlumno::class)->ejecutar($a1, $lejana->id);
        Carbon::setTestNow(now()->subDays(2));   // RB9: cancelar con más de 24 h de anticipación
        app(CancelarInscripcion::class)->ejecutar($a3, $a3->inscripciones()->first());
        Carbon::setTestNow(Carbon::parse('next monday 10:00'));

        $this->artisan('pac:recordatorios')->expectsOutputToContain('Recordatorios enviados: 2')->assertSuccessful();
        Notification::assertSentTo([$a1, $a2], RecordatorioActividad::class, fn ($n) => $n->actividad->is($manana));
        Notification::assertNotSentTo($a3, RecordatorioActividad::class);
        Notification::assertSentToTimes($a1, RecordatorioActividad::class, 1);

        $this->artisan('pac:recordatorios')->expectsOutputToContain('Recordatorios enviados: 0');   // no se duplica
        Carbon::setTestNow();
    }

    public function test_rnf8_aviso_de_privacidad_publico_y_aceptacion_registrada(): void
    {
        $this->get('/aviso-de-privacidad')->assertOk()->assertSee('Derechos ARCO');
        $this->get('/registro')->assertSee(route('aviso-privacidad'), false);

        $this->post('/registro', [
            'matricula' => '2024630999', 'nombre' => 'Ana', 'apellidos' => 'López', 'correo' => 'ana@alumno.ipn.mx',
            'password' => 'Segura2026', 'password_confirmation' => 'Segura2026', 'aviso_privacidad' => '1',
        ])->assertRedirect();
        $this->assertNotNull(Usuario::where('correo', 'ana@alumno.ipn.mx')->value('aviso_privacidad_aceptado_en'));
    }

    public function test_rf9_reporte_en_pdf_generado_por_el_servidor(): void
    {
        $this->actividad(['titulo' => 'Coro universitario', 'fecha_inicio' => now()->subDay()->toDateString()]);
        $r = $this->actingAs($this->usuario(RolSistema::COORDINADOR))
            ->get('/coordinacion/reportes?formato=pdf&desde='.now()->subMonth()->toDateString());
        $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertDatabaseHas('auditoria', ['accion' => 'reporte.exportar']);
    }

    public function test_rf9_lista_de_asistencia_por_actividad(): void
    {
        $a = $this->actividad(['fecha_inicio' => now()->subWeek()->toDateString()]);
        [$al1, $al2] = [$this->usuario(), $this->usuario()];
        app(InscribirAlumno::class)->ejecutar($al1, $a->id);
        app(InscribirAlumno::class)->ejecutar($al2, $a->id);
        $resp = Usuario::find($a->responsable_id);
        foreach ([now()->subDays(2), now()->subDay()] as $i => $dia) {
            $s = SesionAsistencia::create(['horario_id' => $a->horarios->first()->id, 'abierta_por' => $resp->id, 'fecha' => $dia->toDateString(),
                'inicio' => $dia, 'secreto_hmac' => str_repeat('a', 64), 'estado' => 'CERRADA']);
            Asistencia::create(['sesion_id' => $s->id, 'usuario_id' => $al1->id, 'registrada_por' => $resp->id, 'metodo' => $i ? 'MANUAL' : 'QR']);
        }

        $html = $this->actingAs($resp)->get("/responsable/actividades/{$a->id}/asistencia")->assertOk()->getContent();
        $this->assertStringContainsString($al1->apellidos.' '.$al1->nombre, $html);
        $this->assertStringContainsString('100 %', $html);   // al1: 2/2
        $this->assertStringContainsString('0 %', $html);     // al2: 0/2

        $this->get("/responsable/actividades/{$a->id}/asistencia?formato=xlsx")->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $pdf = $this->get("/responsable/actividades/{$a->id}/asistencia?formato=pdf")->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($this->usuario(RolSistema::RESPONSABLE))->get("/responsable/actividades/{$a->id}/asistencia")->assertForbidden();   // RB17
        $this->actingAs($al1)->get("/responsable/actividades/{$a->id}/asistencia")->assertForbidden();
        $this->actingAs($this->usuario(RolSistema::COORDINADOR))->get("/responsable/actividades/{$a->id}/asistencia")->assertOk();
    }

    public function test_rf7_vista_semanal_del_calendario(): void
    {
        $a = $this->actividad(['titulo' => 'Danza contemporánea'], [3]);
        $miercoles = $a->fecha_inicio->copy()->addWeek()->startOfWeek()->addDays(2);
        $r = $this->actingAs($this->usuario())->get('/calendario?vista=semana&fecha='.$miercoles->toDateString())->assertOk();
        $r->assertSee('Semana del')->assertSee('Danza contemporánea')->assertSee('09:00');
        $this->assertCount(5, $r->viewData('dias'));   // lunes a viernes
    }

    public function test_rf6_filtros_por_lugar_y_fecha(): void
    {
        $lunes = $this->actividad(['titulo' => 'Pintura al óleo', 'lugar' => 'Auditorio A'], [1]);
        $this->actividad(['titulo' => 'Guitarra clásica', 'lugar' => 'Aula 1'], [3]);
        $this->actingAs($this->usuario());

        $this->get('/actividades?lugar=Auditorio+A')->assertSee('Pintura al óleo')->assertDontSee('Guitarra clásica');
        $fecha = $lunes->fecha_inicio->copy()->next(Carbon::MONDAY)->toDateString();
        $this->get('/actividades?fecha='.$fecha)->assertSee('Pintura al óleo')->assertDontSee('Guitarra clásica');
    }

    public function test_rf12_recomendaciones_consideran_la_agenda(): void
    {
        $teatro = Categoria::firstOrCreate(['nombre' => 'Teatro']);
        $favorita = $this->actividad(['titulo' => 'Improvisación', 'categoria_id' => $teatro->id]);
        $this->actividad(['titulo' => 'Coro', 'fecha_inicio' => now()->addDay()->toDateString()]);   // Música, empieza antes
        $this->actividad(['titulo' => 'Pantomima', 'categoria_id' => $teatro->id, 'fecha_inicio' => now()->addDays(5)->toDateString()]);
        $alumno = $this->usuario();
        $alumno->agenda()->attach($favorita->id);

        $recomendadas = $this->actingAs($alumno)->get('/inicio')->assertOk()->viewData('recomendadas');
        $this->assertSame($teatro->id, $recomendadas->first()->categoria_id);
    }

    public function test_mi_cuenta_cambio_de_contrasena(): void
    {
        $u = $this->usuario();
        $this->actingAs($u)->get('/mi-cuenta')->assertOk()->assertSee($u->correo);

        $this->put('/mi-cuenta/contrasena', ['actual' => 'Incorrecta1', 'password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])
            ->assertSessionHasErrors('actual');
        $this->put('/mi-cuenta/contrasena', ['actual' => 'Secreta123', 'password' => 'debil', 'password_confirmation' => 'debil'])
            ->assertSessionHasErrors('password');
        $this->put('/mi-cuenta/contrasena', ['actual' => 'Secreta123', 'password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Nueva2026x', $u->fresh()->password));
        $this->assertDatabaseHas('auditoria', ['accion' => 'cuenta.cambiar_contrasena', 'entidad_id' => $u->id]);
    }
}
