<?php

namespace App\Modulos\Actividades\Acciones;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Notificaciones\ActividadCancelada;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * CU11 — Cancelar una actividad (diagrama de actividades 4.5): cancela inscripciones, registra en
 * auditoría y, tras confirmar la transacción, encola las notificaciones a los inscritos (RB8).
 */
class CancelarActividad
{
    public function ejecutar(Actividad $actividad): int
    {
        $alumnos = DB::transaction(function () use ($actividad) {
            $actividad = Actividad::query()->lockForUpdate()->findOrFail($actividad->id);
            if (in_array($actividad->estado, [EstadoActividad::CANCELADA, EstadoActividad::FINALIZADA], true)) {
                throw new ReglaNegocioException('RB8', 'La actividad ya está cancelada o finalizada.');
            }

            $ids = $actividad->inscripcionesActivas()->pluck('usuario_id');
            $actividad->inscripcionesActivas()->update([
                'estado' => EstadoInscripcion::CANCELADA->value,
                'fecha_cancelacion' => now(),
            ]);
            $actividad->update(['estado' => EstadoActividad::CANCELADA, 'cupo_ocupado' => 0]);
            Auditor::registrar('actividad.cancelar', 'actividades', $actividad->id, ['inscritos_afectados' => $ids->count()]);

            return Usuario::whereIn('id', $ids)->get();
        });

        Notification::send($alumnos, new ActividadCancelada($actividad->refresh()));

        return $alumnos->count();
    }
}
