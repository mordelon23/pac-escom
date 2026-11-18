<?php

namespace App\Modulos\Comunidad\Acciones;

use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Modelos\Publicacion;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Http\UploadedFile;

/** CU8 — Publicar en el muro social. La publicación queda PENDIENTE hasta su moderación (RB16). */
class PublicarEnMuro
{
    public function ejecutar(Usuario $alumno, int $actividadId, string $contenido, ?UploadedFile $foto): Publicacion
    {
        // Solo se publica sobre actividades en las que el alumno estuvo inscrito
        if (! $alumno->inscripciones()->where('actividad_id', $actividadId)->exists()) {
            throw new ReglaNegocioException('RB16', 'Solo puedes publicar sobre actividades en las que te inscribiste.');
        }

        return Publicacion::create([
            'usuario_id' => $alumno->id,
            'actividad_id' => $actividadId,
            'contenido' => trim($contenido),
            'imagen_ruta' => $foto?->store('muro', 'public'),
            'estado_moderacion' => EstadoModeracion::PENDIENTE,
        ]);
    }
}
