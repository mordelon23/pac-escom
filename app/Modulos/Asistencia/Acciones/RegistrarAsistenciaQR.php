<?php

namespace App\Modulos\Asistencia\Acciones;

use App\Modulos\Asistencia\Enums\EstadoSesion;
use App\Modulos\Asistencia\Enums\MetodoAsistencia;
use App\Modulos\Asistencia\Modelos\Asistencia;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;

/** CU6 — Registrar asistencia mediante el código QR dinámico (diagrama de secuencia 2.3). */
class RegistrarAsistenciaQR
{
    public function __construct(private ServicioTokenQR $tokens) {}

    public function ejecutar(Usuario $alumno, int $sesionId, string $token): Asistencia
    {
        $sesion = SesionAsistencia::with('horario')->find($sesionId);
        if (! $sesion || ! $this->tokens->validar($sesion, $token)) {
            throw new ReglaNegocioException('RB20', 'Código expirado, escanee nuevamente.');
        }
        if ($sesion->estado !== EstadoSesion::ABIERTA) {
            throw new ReglaNegocioException('RB20', 'La sesión de asistencia no está recibiendo registros por QR.');
        }
        $inscrito = $alumno->inscripcionesActivas()->where('actividad_id', $sesion->actividadId())->exists();
        if (! $inscrito) {
            throw new ReglaNegocioException('RB22', 'No está inscrito en esta actividad.');
        }

        try {
            return Asistencia::create([
                'sesion_id' => $sesion->id,
                'usuario_id' => $alumno->id,
                'registrada_por' => $alumno->id,
                'metodo' => MetodoAsistencia::QR,
                'registrada_en' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new ReglaNegocioException('RB21', 'Su asistencia ya fue registrada.');
        }
    }
}
