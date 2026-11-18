<?php

namespace App\Modulos\Asistencia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Panel del responsable: sus grupos, los horarios de hoy y las últimas sesiones. */
class GruposController extends Controller
{
    public function __invoke(Request $request): View
    {
        $u = $request->user();
        $actividades = Actividad::publicadas()->with(['horarios', 'categoria'])
            ->when(! $u->esCoordinador(), fn ($q) => $q->where('responsable_id', $u->id))
            ->where('fecha_fin', '>=', today())->orderBy('titulo')->get();

        $hoy = $actividades->flatMap(fn (Actividad $a) => $a->horariosDelDia(today())->map(fn ($h) => ['actividad' => $a, 'horario' => $h]))
            ->sortBy(fn ($x) => $x['horario']->hora_inicio)->values();

        $sesionesHoy = SesionAsistencia::whereIn('horario_id', $hoy->pluck('horario.id'))->whereDate('fecha', today())->get()->keyBy('horario_id');

        $recientes = SesionAsistencia::with('horario.actividad')->withCount('asistencias')
            ->whereHas('horario.actividad', fn ($q) => $u->esCoordinador() ? $q : $q->where('responsable_id', $u->id))
            ->orderByDesc('fecha')->orderByDesc('inicio')->limit(8)->get();

        return view('responsable.grupos', compact('actividades', 'hoy', 'sesionesHoy', 'recientes'));
    }
}
