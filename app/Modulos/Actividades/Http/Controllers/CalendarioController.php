<?php

namespace App\Modulos\Actividades\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** RF7 — Calendario mensual generado a partir de los horarios semanales de cada actividad. */
class CalendarioController extends Controller
{
    public function __invoke(Request $request): View
    {
        $mes = rescue(fn () => CarbonImmutable::createFromFormat('Y-m', (string) $request->query('mes'))->startOfMonth(),
            CarbonImmutable::today()->startOfMonth(), false);
        $soloMias = $request->boolean('mias');
        $u = $request->user();
        $ids = $soloMias ? $u->inscripcionesActivas()->pluck('actividad_id')->merge($u->agenda()->pluck('actividades.id'))->unique() : null;

        $actividades = Actividad::publicadas()->with(['horarios', 'categoria'])
            ->where('fecha_inicio', '<=', $mes->endOfMonth())->where('fecha_fin', '>=', $mes)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))->get();
        $inscritas = $u->inscripcionesActivas()->pluck('actividad_id')->all();

        $dias = [];
        for ($d = $mes->startOfWeek(); $d->lte($mes->endOfMonth()->endOfWeek()); $d = $d->addDay()) {
            $eventos = [];
            foreach ($actividades as $a) {
                foreach ($a->horariosDelDia($d) as $h) {
                    $eventos[] = ['actividad' => $a, 'horario' => $h, 'inscrito' => in_array($a->id, $inscritas)];
                }
            }
            usort($eventos, fn ($x, $y) => strcmp($x['horario']->hora_inicio, $y['horario']->hora_inicio));
            $dias[] = ['fecha' => $d, 'delMes' => $d->month === $mes->month, 'eventos' => $eventos];
        }

        return view('alumno.calendario', compact('mes', 'dias', 'soloMias'));
    }
}
