<?php

namespace App\Modulos\Actividades\Acciones;

use App\Modulos\Actividades\Enums\DiaSemana;
use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Modelos\Horario;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * CU11 — Registrar o editar una actividad con sus horarios semanales (diagrama de secuencia 2.5).
 * Los datos ya llegan validados por GuardarActividadRequest; aquí se aplican las reglas que dependen del estado.
 */
class GuardarActividad
{
    /**
     * @param  array{categoria_id:int, responsable_id:int, titulo:string, descripcion:string, lugar:string,
     *               fecha_inicio:string, fecha_fin:string, cupo_maximo:int,
     *               horarios: array<int, array{dia_semana:int, hora_inicio:string, hora_fin:string}>}  $datos
     */
    public function ejecutar(Usuario $coordinador, array $datos, ?UploadedFile $imagen, ?Actividad $actividad = null): Actividad
    {
        self::verificarTraslapes($datos['horarios']);

        if ($actividad && $datos['cupo_maximo'] < $actividad->cupo_ocupado) {
            throw new ReglaNegocioException('RB5', "El cupo no puede ser menor al número de inscritos ({$actividad->cupo_ocupado}).");
        }
        if ($actividad && in_array($actividad->estado, [EstadoActividad::CANCELADA, EstadoActividad::FINALIZADA], true)) {
            throw new ReglaNegocioException('RB8', 'No se puede editar una actividad cancelada o finalizada.');
        }

        return DB::transaction(function () use ($coordinador, $datos, $imagen, $actividad) {
            $campos = collect($datos)->except('horarios')->all();
            if ($imagen) {
                $campos['imagen_ruta'] = $imagen->store('actividades', 'public');   // nombre aleatorio
            }

            if ($actividad) {
                $actividad->update($campos);
                $actividad->horarios()->delete();
                $accion = 'actividad.editar';
            } else {
                $actividad = Actividad::create($campos + ['creada_por' => $coordinador->id, 'estado' => EstadoActividad::BORRADOR]);
                $accion = 'actividad.crear';
            }

            foreach ($datos['horarios'] as $h) {
                $actividad->horarios()->create($h);
            }
            Auditor::registrar($accion, 'actividades', $actividad->id, ['titulo' => $actividad->titulo]);

            return $actividad->load('horarios');
        });
    }

    /** RB26: no puede haber dos horarios traslapados el mismo día. */
    public static function verificarTraslapes(array $horarios): void
    {
        $lista = array_values($horarios);
        for ($i = 0; $i < count($lista); $i++) {
            for ($j = $i + 1; $j < count($lista); $j++) {
                if (Horario::intervalosSeTraslapan(
                    DiaSemana::from((int) $lista[$i]['dia_semana']), $lista[$i]['hora_inicio'], $lista[$i]['hora_fin'],
                    DiaSemana::from((int) $lista[$j]['dia_semana']), $lista[$j]['hora_inicio'], $lista[$j]['hora_fin'],
                )) {
                    throw new ReglaNegocioException('RB26', 'Hay horarios traslapados el ' . DiaSemana::from((int) $lista[$i]['dia_semana'])->etiqueta() . '.');
                }
            }
        }
    }
}
