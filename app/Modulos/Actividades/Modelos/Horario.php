<?php

namespace App\Modulos\Actividades\Modelos;

use App\Modulos\Actividades\Enums\DiaSemana;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Día y horas en que se imparte una actividad. De cada horario se genera una sesión de asistencia por fecha.
 */
class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = ['dia_semana', 'hora_inicio', 'hora_fin'];

    protected function casts(): array
    {
        return ['dia_semana' => DiaSemana::class];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionAsistencia::class);
    }

    /** RB26: dos horarios del mismo día no pueden traslaparse. */
    public function seTraslapaCon(Horario $otro): bool
    {
        return self::intervalosSeTraslapan(
            $this->dia_semana, $this->hora_inicio, $this->hora_fin,
            $otro->dia_semana, $otro->hora_inicio, $otro->hora_fin,
        );
    }

    public static function intervalosSeTraslapan(DiaSemana $diaA, string $iniA, string $finA, DiaSemana $diaB, string $iniB, string $finB): bool
    {
        if ($diaA !== $diaB) {
            return false;
        }

        return substr($iniA, 0, 5) < substr($finB, 0, 5) && substr($iniB, 0, 5) < substr($finA, 0, 5);
    }

    public function rango(): string
    {
        return substr($this->hora_inicio, 0, 5) . '–' . substr($this->hora_fin, 0, 5);
    }
}
