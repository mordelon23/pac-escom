<?php

namespace App\Modulos\Asistencia\Modelos;

use App\Modulos\Asistencia\Enums\MetodoAsistencia;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = ['sesion_id', 'usuario_id', 'registrada_por', 'metodo', 'registrada_en'];

    protected function casts(): array
    {
        return ['metodo' => MetodoAsistencia::class, 'registrada_en' => 'datetime'];
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionAsistencia::class, 'sesion_id');
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
