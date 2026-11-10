<?php

namespace App\Modulos\Inscripciones\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pantalla de inicio del alumno: próximas sesiones y recomendaciones (RF12). */
class InicioAlumnoController extends Controller
{
    public function __invoke(Request $request): View
    {
        $alumno = $request->user();
        $activas = $alumno->inscripcionesActivas()->with('actividad.horarios', 'actividad.categoria')->get();

        // Próximas sesiones de la semana a partir de los horarios
        $proximas = collect();
        for ($d = 0; $d < 7; $d++) {
            $fecha = today()->addDays($d);
            foreach ($activas as $i) {
                foreach ($i->actividad->horariosDelDia($fecha) as $h) {
                    $proximas->push(['fecha' => $fecha, 'horario' => $h, 'actividad' => $i->actividad]);
                }
            }
        }

        // Recomendaciones: actividades de las categorías que el alumno ya eligió, en las que no está inscrito
        $categorias = $alumno->inscripciones()->join('actividades', 'actividades.id', '=', 'inscripciones.actividad_id')
            ->pluck('actividades.categoria_id')->unique();
        $recomendadas = Actividad::publicadas()->with('categoria', 'horarios')
            ->where('fecha_fin', '>=', today())
            ->whereNotIn('id', $activas->pluck('actividad_id'))
            ->whereColumn('cupo_ocupado', '<', 'cupo_maximo')
            ->when($categorias->isNotEmpty(), fn ($q) => $q->orderByRaw(
                'CASE WHEN categoria_id IN (' . $categorias->map(fn ($c) => (int) $c)->implode(',') . ') THEN 0 ELSE 1 END'))
            ->orderBy('fecha_inicio')->limit(3)->get();

        return view('alumno.inicio', [
            'proximas' => $proximas->sortBy(fn ($p) => $p['fecha']->format('Ymd') . $p['horario']->hora_inicio)->take(6),
            'activas' => $activas,
            'recomendadas' => $recomendadas,
        ]);
    }
}
