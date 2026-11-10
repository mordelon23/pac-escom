<?php

namespace App\Modulos\Inscripciones\Acciones;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Illuminate\Support\Facades\DB;

/** CU5 — Cancelar inscripción (RB9). */
class CancelarInscripcion
{
    public function ejecutar(Usuario $alumno, Inscripcion $inscripcion): void
    {
        if ($inscripcion->usuario_id !== $alumno->id) {
            throw new ReglaNegocioException('RBAC', 'No puede cancelar una inscripción de otro alumno.');
        }

        DB::transaction(function () use ($inscripcion) {
            $actividad = Actividad::query()->lockForUpdate()->findOrFail($inscripcion->actividad_id);
            $inscripcion = Inscripcion::query()->lockForUpdate()->findOrFail($inscripcion->id);
            $inscripcion->setRelation('actividad', $actividad->load('horarios'));

            if (! $inscripcion->estaActiva()) {
                throw new ReglaNegocioException('RB9', 'La inscripción ya no está activa.');
            }
            if (! $inscripcion->esCancelable()) {
                throw new ReglaNegocioException('RB9', 'Solo puede cancelar hasta 24 horas antes del inicio de la actividad.');
            }

            $inscripcion->update(['estado' => EstadoInscripcion::CANCELADA, 'fecha_cancelacion' => now()]);
            $actividad->decrement('cupo_ocupado');
        });
    }
}
