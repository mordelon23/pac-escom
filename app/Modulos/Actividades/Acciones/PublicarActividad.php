<?php

namespace App\Modulos\Actividades\Acciones;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;

/** CU11 — Publicar una actividad (RB7, RB10, RB11, RB26). */
class PublicarActividad
{
    public function ejecutar(Actividad $actividad): Actividad
    {
        if ($actividad->estado !== EstadoActividad::BORRADOR) {
            throw new ReglaNegocioException('RB10', 'Solo se pueden publicar actividades en borrador.');
        }
        if (blank($actividad->imagen_ruta)) {
            throw new ReglaNegocioException('RB11', 'La actividad debe tener una imagen representativa.');
        }
        if ($actividad->horarios()->count() === 0) {
            throw new ReglaNegocioException('RB26', 'La actividad debe tener al menos un horario.');
        }
        if ($actividad->fecha_fin->endOfDay()->isPast()) {
            throw new ReglaNegocioException('RB18', 'El periodo de la actividad ya concluyó.');
        }

        $actividad->update(['estado' => EstadoActividad::PUBLICADA]);
        Auditor::registrar('actividad.publicar', 'actividades', $actividad->id);

        return $actividad;
    }
}
