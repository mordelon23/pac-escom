<?php

namespace App\Modulos\Asistencia\Servicios;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Asistencia\Modelos\Asistencia;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Illuminate\Support\Collection;

/**
 * RF9 — Lista de asistencia de una actividad: alumnos inscritos × sesiones realizadas.
 * Cada celda indica si el alumno asistió por QR (✓), de forma manual (M) o faltó (—).
 */
class ListaAsistencia
{
    /** @return array{sesiones: Collection, alumnos: Collection} */
    public function generar(Actividad $actividad): array
    {
        $sesiones = SesionAsistencia::whereHas('horario', fn ($q) => $q->where('actividad_id', $actividad->id))
            ->orderBy('fecha')->orderBy('inicio')->get(['id', 'fecha', 'inicio']);

        // Alumnos con inscripción vigente o concluida (los que cancelaron no forman parte del grupo).
        $inscripciones = Inscripcion::with('alumno:id,matricula,nombre,apellidos')
            ->where('actividad_id', $actividad->id)
            ->whereIn('estado', [EstadoInscripcion::ACTIVA, EstadoInscripcion::CONCLUIDA])
            ->get();

        $marcas = Asistencia::whereIn('sesion_id', $sesiones->pluck('id'))->get(['sesion_id', 'usuario_id', 'metodo'])
            ->groupBy('usuario_id')
            ->map(fn ($xs) => $xs->mapWithKeys(fn ($x) => [$x->sesion_id => $x->metodo->value]));

        $total = max(1, $sesiones->count());
        $alumnos = $inscripciones->map(function (Inscripcion $i) use ($sesiones, $marcas, $total) {
            $suyas = $marcas->get($i->usuario_id, collect());
            $celdas = $sesiones->map(fn ($s) => $suyas->get($s->id));

            return (object) [
                'matricula' => $i->alumno->matricula,
                'nombre' => $i->alumno->apellidos.' '.$i->alumno->nombre,
                'celdas' => $celdas->all(),
                'asistencias' => $celdas->filter()->count(),
                'porcentaje' => $sesiones->isEmpty() ? null : round($celdas->filter()->count() * 100 / $total, 1),
            ];
        })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return ['sesiones' => $sesiones, 'alumnos' => $alumnos];
    }
}
