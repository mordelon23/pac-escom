<?php

namespace App\Modulos\Actividades\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** RF7 — Calendario mensual y semanal generado a partir de los horarios semanales de cada actividad. */
class CalendarioController extends Controller
{
    public function __invoke(Request $request): View
    {
        $vista = $request->query('vista') === 'semana' ? 'semana' : 'mes';
        $soloMias = $request->boolean('mias');
        $u = $request->user();

        if ($vista === 'semana') {
            $base = rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', (string) $request->query('fecha'))->startOfDay(), CarbonImmutable::today(), false);
            if (! $request->filled('fecha') && $base->isWeekend()) {
                $base = $base->next(CarbonImmutable::MONDAY);   // en fin de semana se muestra la semana que viene
            }
            $inicio = $base->startOfWeek();
            $fin = $inicio->addDays(4);   // las actividades se imparten de lunes a viernes
            $mes = $inicio->startOfMonth();
        } else {
            $mes = rescue(fn () => CarbonImmutable::createFromFormat('Y-m', (string) $request->query('mes'))->startOfMonth(),
                CarbonImmutable::today()->startOfMonth(), false);
            $inicio = $mes->startOfWeek();
            $fin = $mes->endOfMonth()->endOfWeek();
        }

        $ids = $soloMias ? $u->inscripcionesActivas()->pluck('actividad_id')->merge($u->agenda()->pluck('actividades.id'))->unique() : null;
        $actividades = Actividad::publicadas()->with(['horarios', 'categoria'])
            ->where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $inicio)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))->get();
        $inscritas = $u->inscripcionesActivas()->pluck('actividad_id')->all();

        $dias = [];
        for ($d = $inicio; $d->lte($fin); $d = $d->addDay()) {
            $eventos = [];
            foreach ($actividades as $a) {
                foreach ($a->horariosDelDia($d) as $h) {
                    $eventos[] = ['actividad' => $a, 'horario' => $h, 'inscrito' => in_array($a->id, $inscritas)];
                }
            }
            usort($eventos, fn ($x, $y) => strcmp($x['horario']->hora_inicio, $y['horario']->hora_inicio));
            $dias[] = ['fecha' => $d, 'delMes' => $vista === 'semana' || $d->month === $mes->month, 'eventos' => $eventos];
        }

        return view('alumno.calendario', compact('vista', 'mes', 'inicio', 'fin', 'dias', 'soloMias'));
    }
}
