<?php

namespace App\Modulos\Asistencia\Modelos;

use App\Modulos\Actividades\Modelos\Horario;
use App\Modulos\Asistencia\Enums\EstadoSesion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoSesion $estado
 * @property string $secreto_hmac
 */
class SesionAsistencia extends Model
{
    protected $table = 'sesiones_asistencia';

    protected $fillable = ['horario_id', 'abierta_por', 'fecha', 'inicio', 'fin', 'secreto_hmac', 'estado'];

    protected $hidden = ['secreto_hmac'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'estado' => EstadoSesion::class,
        ];
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'sesion_id');
    }

    public function contingencias(): HasMany
    {
        return $this->hasMany(BitacoraContingencia::class, 'sesion_id');
    }

    public function estaAbierta(): bool
    {
        return $this->estado !== EstadoSesion::CERRADA;
    }

    public function actividadId(): int
    {
        return $this->horario->actividad_id;
    }
}
