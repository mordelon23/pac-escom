<?php

namespace App\Modulos\Reportes\Servicios;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CU12 — Reporte de participación y asistencia por actividad en un periodo (RF9).
 * % de asistencia = asistencias / (sesiones realizadas × inscritos activos).
 */
class ReporteParticipacion
{
    public function generar(CarbonInterface $desde, CarbonInterface $hasta, ?int $categoriaId = null): Collection
    {
        $sesiones = DB::table('sesiones_asistencia as s')->join('horarios as h', 'h.id', '=', 's.horario_id')
            ->whereBetween('s.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('h.actividad_id')->selectRaw('h.actividad_id, count(*) as sesiones');
        $asistencias = DB::table('asistencias as x')->join('sesiones_asistencia as s', 's.id', '=', 'x.sesion_id')
            ->join('horarios as h', 'h.id', '=', 's.horario_id')
            ->whereBetween('s.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('h.actividad_id')
            ->selectRaw("h.actividad_id, count(*) as asistencias, count(*) filter (where x.metodo = 'MANUAL') as manuales");
        $inscritos = DB::table('inscripciones')->where('estado', 'ACTIVA')->groupBy('actividad_id')
            ->selectRaw('actividad_id, count(*) as inscritos');

        return DB::table('actividades as a')
            ->join('categorias as c', 'c.id', '=', 'a.categoria_id')
            ->leftJoinSub($sesiones, 's', 's.actividad_id', '=', 'a.id')
            ->leftJoinSub($asistencias, 'x', 'x.actividad_id', '=', 'a.id')
            ->leftJoinSub($inscritos, 'i', 'i.actividad_id', '=', 'a.id')
            ->whereIn('a.estado', ['PUBLICADA', 'FINALIZADA'])
            ->where('a.fecha_inicio', '<=', $hasta)->where('a.fecha_fin', '>=', $desde)
            ->when($categoriaId, fn ($q) => $q->where('a.categoria_id', $categoriaId))
            ->orderBy('c.nombre')->orderBy('a.titulo')
            ->get(['a.id', 'a.titulo', 'c.nombre as categoria', 'a.cupo_maximo',
                DB::raw('coalesce(i.inscritos, 0) as inscritos'), DB::raw('coalesce(s.sesiones, 0) as sesiones'),
                DB::raw('coalesce(x.asistencias, 0) as asistencias'), DB::raw('coalesce(x.manuales, 0) as manuales')])
            ->map(function ($r) {
                $esperadas = $r->sesiones * $r->inscritos;
                $r->porcentaje = $esperadas > 0 ? round($r->asistencias * 100 / $esperadas, 1) : null;
                $r->ocupacion = round($r->inscritos * 100 / max(1, $r->cupo_maximo), 1);

                return $r;
            });
    }
}
