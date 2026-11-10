<?php

namespace App\Modulos\Inscripciones\Modelos;

use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EstadoInscripcion $estado
 */
class Inscripcion extends Model
{
    public const HORAS_MINIMAS_CANCELACION = 24;   // RB9

    protected $table = 'inscripciones';

    protected $attributes = ['estado' => 'ACTIVA'];

    protected $fillable = ['usuario_id', 'actividad_id', 'estado', 'fecha_inscripcion', 'fecha_cancelacion'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoInscripcion::class,
            'fecha_inscripcion' => 'datetime',
            'fecha_cancelacion' => 'datetime',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function estaActiva(): bool
    {
        return $this->estado === EstadoInscripcion::ACTIVA;
    }

    /** RB9: solo se puede cancelar hasta 24 horas antes del inicio de la actividad. */
    public function esCancelable(?CarbonInterface $ahora = null): bool
    {
        if (! $this->estaActiva()) {
            return false;
        }
        $inicio = $this->actividad->inicioPrimeraSesion();

        return $inicio === null || ($ahora ?? now())->diffInHours($inicio, false) >= self::HORAS_MINIMAS_CANCELACION;
    }
}
