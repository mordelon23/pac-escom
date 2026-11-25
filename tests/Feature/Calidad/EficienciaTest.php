<?php

namespace Tests\Feature\Calidad;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * ISO 9126 · Eficiencia › Comportamiento en el tiempo y utilización de recursos.
 * Con los datos de demostración, ninguna pantalla debe hacer más de 15 consultas (sin N+1)
 * ni tardar más de 1 segundo en generarse. El modo estricto de Eloquent falla ante cargas perezosas.
 */
class EficienciaTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_CONSULTAS = 15;

    private const MAX_MS = 1000;

    public function test_consultas_y_tiempo_por_pantalla(): void
    {
        Notification::fake();
        $this->seed();   // roles, categorías y datos de demostración
        $alumno = Usuario::where('correo', 'alumno1@alumno.ipn.mx')->firstOrFail();
        $resp = Usuario::where('correo', 'chernandez@ipn.mx')->firstOrFail();
        $coord = Usuario::where('correo', 'coordinacion@ipn.mx')->firstOrFail();
        $a = Actividad::firstOrFail();
        $s = SesionAsistencia::firstOrFail();
        $mes = now()->subMonth()->toDateString();

        $pantallas = [
            [$alumno, ['/inicio', '/actividades', "/actividades/{$a->id}", '/mis-actividades', '/calendario', '/calendario?vista=semana', '/agenda', '/muro', '/ayuda', '/mi-cuenta', '/calendario/mis-actividades.ics']],
            [$resp, ['/responsable', "/responsable/sesiones/{$s->id}", "/responsable/sesiones/{$s->id}/estado", "/responsable/actividades/{$s->horario->actividad_id}/asistencia"]],
            [$coord, ['/coordinacion', '/coordinacion/actividades', "/coordinacion/actividades/{$a->id}", "/coordinacion/actividades/{$a->id}/edit",
                "/coordinacion/reportes?desde={$mes}", "/coordinacion/reportes?desde={$mes}&formato=xlsx", '/coordinacion/muro']],
        ];
        foreach ($pantallas as [$usuario, $urls]) {
            foreach ($urls as $url) {
                $this->actingAs($usuario);
                DB::flushQueryLog();
                DB::enableQueryLog();
                $t = hrtime(true);
                $this->get($url)->assertOk();
                $ms = (hrtime(true) - $t) / 1e6;
                $consultas = count(DB::getQueryLog());
                $this->assertLessThanOrEqual(self::MAX_CONSULTAS, $consultas, "{$url} hizo {$consultas} consultas");
                $this->assertLessThan(self::MAX_MS, $ms, "{$url} tardó {$ms} ms");
            }
        }
    }
}
