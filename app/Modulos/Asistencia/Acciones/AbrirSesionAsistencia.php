<?php

namespace App\Modulos\Asistencia\Acciones;

use App\Modulos\Actividades\Modelos\Horario;
use App\Modulos\Asistencia\Enums\EstadoSesion;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;

/** CU9 — Abrir la sesión de asistencia del día para un horario. */
class AbrirSesionAsistencia
{
    public function ejecutar(Usuario $responsable, Horario $horario): SesionAsistencia
    {
        $actividad = $horario->actividad;
        if ($actividad->responsable_id !== $responsable->id && ! $responsable->esCoordinador()) {
            throw new ReglaNegocioException('RBAC', 'Solo el responsable asignado puede abrir la sesión.');
        }
        if (! $actividad->estaPublicada()) {
            throw new ReglaNegocioException('RB8', 'No es posible abrir una sesión para esta actividad.');
        }
        $hoy = now();
        if ($horario->dia_semana->value !== $hoy->dayOfWeekIso
            || $hoy->lt($actividad->fecha_inicio->startOfDay()) || $hoy->gt($actividad->fecha_fin->endOfDay())) {
            throw new ReglaNegocioException('RB26', 'Este horario no corresponde al día de hoy.');
        }

        $sesion = SesionAsistencia::firstOrCreate(
            ['horario_id' => $horario->id, 'fecha' => $hoy->toDateString()],
            ['abierta_por' => $responsable->id, 'inicio' => $hoy, 'secreto_hmac' => ServicioTokenQR::nuevoSecreto(), 'estado' => EstadoSesion::ABIERTA],
        );
        if ($sesion->estado === EstadoSesion::CERRADA) {
            throw new ReglaNegocioException('RB21', 'La sesión de hoy ya fue cerrada.');
        }

        return $sesion;
    }
}
