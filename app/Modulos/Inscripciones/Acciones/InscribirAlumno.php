<?php

namespace App\Modulos\Inscripciones\Acciones;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use App\Modulos\Inscripciones\Notificaciones\InscripcionConfirmada;
use App\Modulos\Inscripciones\Notificaciones\CupoPorAgotarse;
use Illuminate\Support\Facades\DB;

/**
 * CU4 — Inscribirse a una actividad (diagrama de secuencia 2.2).
 * La transacción bloquea la fila de la actividad (SELECT … FOR UPDATE) para evitar la sobreinscripción.
 */
class InscribirAlumno
{
    public const MAXIMO_INSCRIPCIONES_ACTIVAS = 5;   // RB6
    public const PORCENTAJE_AVISO_CUPO = 90;          // RB19

    public function ejecutar(Usuario $alumno, int $actividadId): Inscripcion
    {
        [$inscripcion, $actividad, $cruzoUmbral] = DB::transaction(function () use ($alumno, $actividadId) {
            /** @var Actividad $actividad */
            $actividad = Actividad::query()->lockForUpdate()->findOrFail($actividadId);

            if (! $actividad->estaPublicada()) {
                throw new ReglaNegocioException('RB10', 'Esta actividad no está disponible para inscripción.');
            }
            if ($actividad->fecha_fin->endOfDay()->isPast()) {
                throw new ReglaNegocioException('RB18', 'La actividad ya concluyó.');
            }
            if ($alumno->inscripcionesActivas()->where('actividad_id', $actividad->id)->exists()) {
                throw new ReglaNegocioException('RB4', 'Ya está inscrito en esta actividad.');
            }
            if (! $actividad->tieneCupo()) {
                throw new ReglaNegocioException('RB5', 'La actividad ya no tiene lugares disponibles.');
            }
            if ($alumno->inscripcionesActivas()->count() >= self::MAXIMO_INSCRIPCIONES_ACTIVAS) {
                throw new ReglaNegocioException('RB6', 'Alcanzó el límite de cinco actividades simultáneas.');
            }

            $porcentajeAntes = $actividad->porcentajeOcupacion();

            $inscripcion = Inscripcion::create([
                'usuario_id' => $alumno->id,
                'actividad_id' => $actividad->id,
                'estado' => EstadoInscripcion::ACTIVA,
                'fecha_inscripcion' => now(),
            ]);
            $actividad->increment('cupo_ocupado');

            $cruzoUmbral = $porcentajeAntes < self::PORCENTAJE_AVISO_CUPO
                && $actividad->porcentajeOcupacion() >= self::PORCENTAJE_AVISO_CUPO;

            return [$inscripcion, $actividad, $cruzoUmbral];
        });

        // Fuera de la transacción: los correos se encolan (no retrasan la respuesta).
        $alumno->notify(new InscripcionConfirmada($inscripcion->load('actividad.horarios')));
        if ($cruzoUmbral) {
            Usuario::find($actividad->creada_por)?->notify(new CupoPorAgotarse($actividad));
        }

        return $inscripcion;
    }
}
