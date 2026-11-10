<?php

namespace App\Modulos\Asistencia\Modelos;

use App\Modulos\Asistencia\Enums\MotivoContingencia;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraContingencia extends Model
{
    protected $table = 'bitacora_contingencias';

    protected $fillable = ['sesion_id', 'asistencia_id', 'alumno_id', 'responsable_id', 'motivo', 'detalle'];

    protected function casts(): array
    {
        return ['motivo' => MotivoContingencia::class];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alumno_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }
}
