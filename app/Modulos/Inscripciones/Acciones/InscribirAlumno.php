<?php

namespace App\Modulos\Inscripciones\Acciones;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Compartido\Notificaciones\Notificador;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use App\Modulos\Inscripciones\Notificaciones\CupoPorAgotarse;
use App\Modulos\Inscripciones\Notificaciones\InscripcionConfirmada;
use Illuminate\Support\Facades\DB;

/**
 * CU4 — Inscribirse a una actividad (diagrama de secuencia 2.2).
 * La transacción bloquea la fila de la actividad (SELECT … FOR UPDATE) para evitar la sobreinscripción.
 */
class InscribirAlumno
{
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
            $maximo = config('pac.reglas.max_inscripciones_activas');
            if ($alumno->inscripcionesActivas()->count() >= $maximo) {
                throw new ReglaNegocioException('RB6', "Alcanzó el límite de {$maximo} actividades simultáneas.");
            }

            $porcentajeAntes = $actividad->porcentajeOcupacion();

            $inscripcion = Inscripcion::create([
                'usuario_id' => $alumno->id,
                'actividad_id' => $actividad->id,
                'estado' => EstadoInscripcion::ACTIVA,
                'fecha_inscripcion' => now(),
            ]);
            $actividad->increment('cupo_ocupado');

            $umbral = config('pac.reglas.porcentaje_aviso_cupo');
            $cruzoUmbral = $porcentajeAntes < $umbral && $actividad->porcentajeOcupacion() >= $umbral;

            return [$inscripcion, $actividad, $cruzoUmbral];
        });

        // Fuera de la transacción: si el correo falla, la inscripción ya quedó guardada.
        Notificador::enviar($alumno, new InscripcionConfirmada($inscripcion->load('actividad.horarios')));
        if ($cruzoUmbral) {
            if ($coordinador = Usuario::find($actividad->creada_por)) {
                Notificador::enviar($coordinador, new CupoPorAgotarse($actividad));
            }
        }

        return $inscripcion;
    }
}
