<?php

namespace App\Modulos\Comunidad\Modelos;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publicacion extends Model
{
    protected $table = 'publicaciones';

    protected $attributes = ['estado_moderacion' => 'PENDIENTE'];

    protected $fillable = ['usuario_id', 'actividad_id', 'contenido', 'imagen_ruta', 'estado_moderacion', 'motivo_rechazo', 'moderada_por'];

    protected function casts(): array
    {
        return ['estado_moderacion' => EstadoModeracion::class];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }
}
