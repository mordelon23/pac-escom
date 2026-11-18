<?php

namespace App\Modulos\Asistencia\Acciones;

use App\Modulos\Asistencia\Enums\EstadoSesion;
use App\Modulos\Asistencia\Enums\MetodoAsistencia;
use App\Modulos\Asistencia\Enums\MotivoContingencia;
use App\Modulos\Asistencia\Modelos\Asistencia;
use App\Modulos\Asistencia\Modelos\BitacoraContingencia;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** CU10 — Registro manual de asistencia en modo contingencia (RB23). */
class RegistrarContingencia
{
    public function activar(SesionAsistencia $sesion): void
    {
        if ($sesion->estado === EstadoSesion::CERRADA) {
            throw new ReglaNegocioException('RB21', 'La sesión ya está cerrada.');
        }
        $sesion->update(['estado' => EstadoSesion::CONTINGENCIA]);
        Auditor::registrar('sesion.contingencia', 'sesiones_asistencia', $sesion->id);
    }

    public function ejecutar(Usuario $responsable, SesionAsistencia $sesion, int $alumnoId, MotivoContingencia $motivo, ?string $detalle): Asistencia
    {
        if ($sesion->estado === EstadoSesion::CERRADA) {
            throw new ReglaNegocioException('RB21', 'La sesión ya está cerrada.');
        }
        if ($motivo === MotivoContingencia::OTRO && blank($detalle)) {
            throw new ReglaNegocioException('RB23', 'Describa el motivo de la contingencia.');
        }
        $alumno = Usuario::findOrFail($alumnoId);
        if (! $alumno->inscripcionesActivas()->where('actividad_id', $sesion->actividadId())->exists()) {
            throw new ReglaNegocioException('RB22', 'El alumno no está inscrito en esta actividad.');
        }

        try {
            return DB::transaction(function () use ($responsable, $sesion, $alumno, $motivo, $detalle) {
                $asistencia = Asistencia::create([
                    'sesion_id' => $sesion->id,
                    'usuario_id' => $alumno->id,
                    'registrada_por' => $responsable->id,
                    'metodo' => MetodoAsistencia::MANUAL,
                    'registrada_en' => now(),
                ]);
                BitacoraContingencia::create([
                    'sesion_id' => $sesion->id,
                    'asistencia_id' => $asistencia->id,
                    'alumno_id' => $alumno->id,
                    'responsable_id' => $responsable->id,
                    'motivo' => $motivo,
                    'detalle' => $detalle,
                ]);
                Auditor::registrar('asistencia.contingencia', 'asistencias', $asistencia->id, ['motivo' => $motivo->value]);

                return $asistencia;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ReglaNegocioException('RB21', 'El alumno ya cuenta con asistencia en esta sesión.');
        }
    }

    /** Regresa de CONTINGENCIA a ABIERTA cuando se restablece la conexión (diagrama de estados 3.3). */
    public function reanudar(SesionAsistencia $sesion): void
    {
        if ($sesion->estado !== EstadoSesion::CONTINGENCIA) {
            throw new ReglaNegocioException('RB20', 'La sesión no está en modo contingencia.');
        }
        $sesion->update(['estado' => EstadoSesion::ABIERTA]);
    }

    public function cerrar(SesionAsistencia $sesion): void
    {
        $sesion->update(['estado' => EstadoSesion::CERRADA, 'fin' => now()]);
        Auditor::registrar('sesion.cerrar', 'sesiones_asistencia', $sesion->id, ['asistencias' => $sesion->asistencias()->count()]);
    }
}
