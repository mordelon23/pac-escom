<?php

namespace App\Modulos\Actividades\Modelos;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

/**
 * @property EstadoActividad $estado
 * @property int $cupo_maximo
 * @property int $cupo_ocupado
 */
class Actividad extends Model
{
    protected $table = 'actividades';

    protected $attributes = ['estado' => 'BORRADOR', 'cupo_ocupado' => 0];

    protected $fillable = [
        'categoria_id', 'responsable_id', 'creada_por', 'titulo', 'descripcion', 'lugar',
        'fecha_inicio', 'fecha_fin', 'cupo_maximo', 'imagen_ruta', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoActividad::class,
            'cupo_maximo' => 'integer',
            'cupo_ocupado' => 'integer',
        ];
    }

    /* ---------------- Relaciones ---------------- */

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function inscripcionesActivas(): HasMany
    {
        return $this->inscripciones()->where('estado', EstadoInscripcion::ACTIVA);
    }

    public function sesiones(): HasManyThrough
    {
        return $this->hasManyThrough(SesionAsistencia::class, Horario::class);
    }

    /* ---------------- Consultas ---------------- */

    public function scopePublicadas(Builder $q): Builder
    {
        return $q->where('estado', EstadoActividad::PUBLICADA);
    }

    /* ---------------- Reglas del dominio ---------------- */

    public function tieneCupo(): bool
    {
        return $this->cupo_ocupado < $this->cupo_maximo;
    }

    public function cuposDisponibles(): int
    {
        return max(0, $this->cupo_maximo - $this->cupo_ocupado);
    }

    public function porcentajeOcupacion(): int
    {
        return (int) floor($this->cupo_ocupado * 100 / max(1, $this->cupo_maximo));
    }

    /** Subestado CupoLleno del diagrama de estados. */
    public function cupoLleno(): bool
    {
        return ! $this->tieneCupo();
    }

    public function estaPublicada(): bool
    {
        return $this->estado === EstadoActividad::PUBLICADA;
    }

    /** Subestado EnCurso: ya inició su periodo y sigue publicada. */
    public function enCurso(?CarbonInterface $hoy = null): bool
    {
        $hoy ??= now();

        return $this->estaPublicada() && $hoy->betweenIncluded($this->fecha_inicio->startOfDay(), $this->fecha_fin->endOfDay());
    }

    /** Horarios que corresponden a una fecha (sesionesDelDia del diagrama de clases). */
    public function horariosDelDia(CarbonInterface $fecha)
    {
        if ($fecha->lt($this->fecha_inicio->startOfDay()) || $fecha->gt($this->fecha_fin->endOfDay())) {
            return collect();
        }

        return $this->horarios->filter(fn (Horario $h) => $h->dia_semana->value === $fecha->dayOfWeekIso);
    }

    /**
     * Fecha y hora de la primera sesión: el primer día del periodo que coincide con un horario.
     * Se usa para la regla de cancelación con 24 h de anticipación (RB9).
     */
    public function inicioPrimeraSesion(): ?CarbonInterface
    {
        $dia = $this->fecha_inicio->copy()->startOfDay();
        for ($i = 0; $i < 7 && $dia->lte($this->fecha_fin); $i++, $dia = $dia->addDay()) {
            $horario = $this->horarios->first(fn (Horario $h) => $h->dia_semana->value === $dia->dayOfWeekIso);
            if ($horario) {
                [$h, $m] = explode(':', $horario->hora_inicio);

                return $dia->copy()->setTime((int) $h, (int) $m);
            }
        }

        return null;
    }

    public function urlImagen(): string
    {
        return str_starts_with($this->imagen_ruta, 'http')
            ? $this->imagen_ruta
            : Storage::disk('public')->url($this->imagen_ruta);
    }
}
