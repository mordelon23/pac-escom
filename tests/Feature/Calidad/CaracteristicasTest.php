<?php

namespace Tests\Feature\Calidad;

use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreaDatos;
use Tests\TestCase;

/** ISO 9126 · Interoperabilidad, usabilidad (ayuda), mantenibilidad (reglas configurables) y portabilidad. */
class CaracteristicasTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Notification::fake();
    }

    public function test_reporte_en_excel_nativo(): void
    {
        $this->actividad(['titulo' => 'Coro universitario', 'fecha_inicio' => now()->subDay()->toDateString()]);
        $r = $this->actingAs($this->usuario(RolSistema::COORDINADOR))->get('/coordinacion/reportes?formato=xlsx&desde='.now()->subMonth()->toDateString());
        $r->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $r->getContent());   // contenedor ZIP de Office Open XML
        $hoja = SeguridadTest::hojaXlsx($r->getContent());
        $this->assertStringContainsString('Coro universitario', $hoja);
        $this->assertStringContainsString('Asistencia %', $hoja);
    }

    public function test_calendario_ics_para_google_y_outlook(): void
    {
        $alumno = $this->usuario();
        $a = $this->actividad(['titulo' => 'Ajedrez, nivel 1'], [2, 4]);
        app(InscribirAlumno::class)->ejecutar($alumno, $a->id);
        $ics = $this->actingAs($alumno)->get('/calendario/mis-actividades.ics')->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));   // martes y jueves
        $this->assertStringContainsString('SUMMARY:Ajedrez\, nivel 1', $ics);
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;UNTIL=', $ics);
        foreach (explode("\r\n", $ics) as $linea) {
            $this->assertLessThanOrEqual(75, strlen($linea));   // RFC 5545
        }
    }

    public function test_pantalla_de_ayuda_para_cada_rol(): void
    {
        foreach (RolSistema::cases() as $rol) {
            $this->actingAs($this->usuario($rol))->get('/ayuda')->assertOk()->assertSee('Mi cuenta');
        }
        $this->actingAs($this->usuario())->get('/ayuda')->assertSee('¿Cómo registro mi asistencia?')->assertDontSee('¿Cómo creo una actividad?');
    }

    public function test_las_reglas_se_cambian_por_configuracion(): void
    {
        config(['pac.reglas.max_inscripciones_activas' => 1, 'pac.reglas.segundos_ventana_qr' => 60]);
        $alumno = $this->usuario();
        app(InscribirAlumno::class)->ejecutar($alumno, $this->actividad()->id);
        try {
            app(InscribirAlumno::class)->ejecutar($alumno, $this->actividad()->id);
            $this->fail('Debió aplicar el límite configurado');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RB6', $e->regla);
            $this->assertStringContainsString('límite de 1', $e->getMessage());
        }
        $this->assertSame(60, app(ServicioTokenQR::class)->segundosVentana());
    }

    public function test_comando_de_verificacion_de_instalacion(): void
    {
        $this->artisan('pac:verificar')->expectsOutputToContain('PHP 8.3 o superior')->expectsOutputToContain('Conexión a PostgreSQL');
    }

    public function test_comando_de_respaldo(): void
    {
        $pgDump = trim((string) shell_exec('command -v pg_dump'));
        if ($pgDump === '') {
            $this->markTestSkipped('pg_dump no está instalado en este equipo.');
        }
        $dir = sys_get_temp_dir().'/pac-respaldos-'.uniqid();
        config(['pac.respaldos.ruta' => $dir, 'pac.respaldos.conservar' => 2]);
        $this->artisan('pac:respaldo')->assertSuccessful();
        $archivos = glob($dir.'/pac-*.sql');
        $this->assertCount(1, $archivos);
        $this->assertStringContainsString('CREATE TABLE public.actividades', file_get_contents($archivos[0]));
        array_map('unlink', $archivos);
        rmdir($dir);
    }
}
