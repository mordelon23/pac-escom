<?php

namespace App\Modulos\Actividades\Http\Requests;

use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Entrada E3 — Alta y edición de actividad (RB7, RB11, RB18, RB26).
 * Los horarios llegan como dias[n][activo], dias[n][inicio], dias[n][fin] con n = 1…5 (lunes a viernes).
 */
class GuardarActividadRequest extends FormRequest
{
    public function rules(): array
    {
        $creando = $this->route('actividad') === null;

        return [
            'titulo' => ['required', 'string', 'min:5', 'max:120'],
            'descripcion' => ['required', 'string', 'min:20', 'max:2000'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'lugar' => ['required', 'string', 'min:3', 'max:120'],
            'responsable_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where(fn ($q) => $q->whereIn('rol_id',
                Rol::whereIn('clave', [RolSistema::RESPONSABLE->value, RolSistema::COORDINADOR->value])->pluck('id')))],
            'fecha_inicio' => array_filter(['required', 'date', $creando ? 'after_or_equal:today' : null]),
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cupo_maximo' => ['required', 'integer', 'between:1,500'],
            'imagen' => [$creando ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dias' => ['array'],
            'dias.*.activo' => ['nullable', 'boolean'],
            'dias.*.inicio' => ['nullable', 'date_format:H:i'],
            'dias.*.fin' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $horarios = $this->horarios();
            if (count($horarios) === 0) {
                $v->errors()->add('dias', 'Seleccione al menos un día con su horario (RB26).');
            }
            foreach ($this->input('dias', []) as $dia => $d) {
                if (! empty($d['activo'])) {
                    if (empty($d['inicio']) || empty($d['fin'])) {
                        $v->errors()->add("dias.$dia", 'Indique la hora de inicio y de fin.');
                    } elseif ($d['fin'] <= $d['inicio']) {
                        $v->errors()->add("dias.$dia", 'La hora de fin debe ser posterior a la de inicio.');
                    }
                }
                if (! in_array((int) $dia, [1, 2, 3, 4, 5], true)) {
                    $v->errors()->add('dias', 'Solo se permiten días de lunes a viernes.');
                }
            }
        }];
    }

    /** @return array<int, array{dia_semana:int, hora_inicio:string, hora_fin:string}> */
    public function horarios(): array
    {
        $out = [];
        foreach ($this->input('dias', []) as $dia => $d) {
            if (! empty($d['activo']) && ! empty($d['inicio']) && ! empty($d['fin'])) {
                $out[] = ['dia_semana' => (int) $dia, 'hora_inicio' => $d['inicio'], 'hora_fin' => $d['fin']];
            }
        }

        return $out;
    }

    public function datosActividad(): array
    {
        return $this->safe()->only(['titulo', 'descripcion', 'categoria_id', 'lugar', 'responsable_id',
            'fecha_inicio', 'fecha_fin', 'cupo_maximo']) + ['horarios' => $this->horarios()];
    }

    public function attributes(): array
    {
        return ['dias' => 'horarios'];
    }
}
