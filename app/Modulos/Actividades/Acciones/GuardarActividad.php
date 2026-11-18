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

        return DB::transaction(function () use ($coordinador, $datos, $imagen, $actividad) {
            if ($actividad) {
                // Se relee con bloqueo: el cupo ocupado pudo cambiar mientras se editaba el formulario
                $actividad = Actividad::query()->lockForUpdate()->findOrFail($actividad->id);
                if ($datos['cupo_maximo'] < $actividad->cupo_ocupado) {
                    throw new ReglaNegocioException('RB5', "El cupo no puede ser menor al número de inscritos ({$actividad->cupo_ocupado}).");
                }
                if (in_array($actividad->estado, [EstadoActividad::CANCELADA, EstadoActividad::FINALIZADA], true)) {
                    throw new ReglaNegocioException('RB8', 'No se puede editar una actividad cancelada o finalizada.');
                }
            }
            $campos = collect($datos)->except('horarios')->all();
            if ($imagen) {
                $campos['imagen_ruta'] = $imagen->store('actividades', 'public');   // nombre aleatorio
            }

            if ($actividad) {
                $actividad->update($campos);
                $accion = 'actividad.editar';
            } else {
                $actividad = Actividad::create($campos + ['creada_por' => $coordinador->id, 'estado' => EstadoActividad::BORRADOR]);
                $accion = 'actividad.crear';
            }
            $this->sincronizarHorarios($actividad, $datos['horarios']);
            Auditor::registrar($accion, 'actividades', $actividad->id, ['titulo' => $actividad->titulo]);

            return $actividad->load('horarios');
        });
    }

    /**
     * Actualiza los horarios conservando los que ya tienen sesiones de asistencia
     * (la llave foránea impide borrarlos y su historial debe conservarse).
     */
    private function sincronizarHorarios(Actividad $actividad, array $nuevos): void
    {
        $existentes = $actividad->horarios()->withCount('sesiones')->get();
        $restantes = collect($nuevos);

        foreach ($existentes as $h) {
            $i = $restantes->search(fn ($n) => (int) $n['dia_semana'] === $h->dia_semana->value);
            if ($i !== false) {
                $h->update($restantes->pull($i));
            } elseif ($h->sesiones_count > 0) {
                throw new ReglaNegocioException('RB26', 'No se puede quitar el horario del ' . $h->dia_semana->etiqueta() . ' porque ya tiene sesiones de asistencia registradas.');
            } else {
                $h->delete();
            }
        }
        foreach ($restantes as $h) {
            $actividad->horarios()->create($h);
        }
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
