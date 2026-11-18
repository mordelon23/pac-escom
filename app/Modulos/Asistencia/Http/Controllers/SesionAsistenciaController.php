<?php

namespace App\Modulos\Asistencia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Horario;
use App\Modulos\Asistencia\Acciones\AbrirSesionAsistencia;
use App\Modulos\Asistencia\Acciones\RegistrarContingencia;
use App\Modulos\Asistencia\Enums\EstadoSesion;
use App\Modulos\Asistencia\Enums\MotivoContingencia;
use App\Modulos\Asistencia\Modelos\SesionAsistencia;
use App\Modulos\Asistencia\Servicios\ServicioTokenQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU9 y CU10 — Sesión de asistencia con QR dinámico y modo contingencia (diagramas 2.3 y 2.4). */
class SesionAsistenciaController extends Controller
{
    public function store(Request $request, Horario $horario, AbrirSesionAsistencia $accion): RedirectResponse
    {
        try {
            $sesion = $accion->ejecutar($request->user(), $horario->load('actividad'));
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('responsable.sesiones.show', $sesion);
    }

    public function show(Request $request, SesionAsistencia $sesion): View
    {
        $this->autorizar($request, $sesion);
        $sesion->load('horario.actividad.inscripcionesActivas.alumno', 'asistencias.alumno', 'contingencias');
        $registrados = $sesion->asistencias->pluck('usuario_id');
        $pendientes = $sesion->horario->actividad->inscripcionesActivas->pluck('alumno')
            ->reject(fn ($a) => $registrados->contains($a->id))->sortBy('apellidos')->values();

        return view('responsable.sesion', [
            'sesion' => $sesion,
            'actividad' => $sesion->horario->actividad,
            'pendientes' => $pendientes,
            'motivos' => MotivoContingencia::cases(),
        ]);
    }

    /** Consulta periódica de la pantalla del QR: token vigente y asistentes registrados. */
    public function estado(Request $request, SesionAsistencia $sesion, ServicioTokenQR $tokens): JsonResponse
    {
        $this->autorizar($request, $sesion);
        $abierta = $sesion->estado === EstadoSesion::ABIERTA;
        $token = $abierta ? $tokens->generar($sesion) : null;
        $ultimos = $sesion->asistencias()->with('alumno')->latest('registrada_en')->limit(6)->get();

        return response()->json([
            'estado' => $sesion->estado->value,
            'url' => $abierta ? route('asistencia.escanear', ['s' => $sesion->id, 't' => $token]) : null,
            'segundos' => $tokens->segundosRestantes(),
            'ventana' => $tokens->segundosVentana(),
            'asistentes' => $sesion->asistencias()->count(),
            'inscritos' => $sesion->horario->actividad->inscripcionesActivas()->count(),
            'ultimos' => $ultimos->map(fn ($a) => ['nombre' => $a->alumno->nombreCompleto(), 'hora' => $a->registrada_en->format('H:i:s'), 'metodo' => $a->metodo->value]),
        ]);
    }

    public function contingencia(Request $request, SesionAsistencia $sesion, RegistrarContingencia $accion): RedirectResponse
    {
        $this->autorizar($request, $sesion);

        return $this->ejecutar(fn () => $accion->activar($sesion), 'Modo contingencia activado. Registre manualmente a los alumnos presentes.');
    }

    public function reanudar(Request $request, SesionAsistencia $sesion, RegistrarContingencia $accion): RedirectResponse
    {
        $this->autorizar($request, $sesion);

        return $this->ejecutar(fn () => $accion->reanudar($sesion), 'Se reanudó el registro con código QR.');
    }

    public function registrarManual(Request $request, SesionAsistencia $sesion, RegistrarContingencia $accion): RedirectResponse
    {
        $this->autorizar($request, $sesion);
        $datos = $request->validate([
            'alumno_id' => ['required', 'integer'],
            'motivo' => ['required', Rule::enum(MotivoContingencia::class)],
            'detalle' => ['nullable', 'string', 'max:255', 'required_if:motivo,OTRO'],
        ], ['detalle.required_if' => 'Describa el motivo de la contingencia (RB23).']);

        return $this->ejecutar(fn () => $accion->ejecutar($request->user(), $sesion, (int) $datos['alumno_id'],
            MotivoContingencia::from($datos['motivo']), $datos['detalle'] ?? null), 'Asistencia registrada en modo contingencia.');
    }

    public function cerrar(Request $request, SesionAsistencia $sesion, RegistrarContingencia $accion): RedirectResponse
    {
        $this->autorizar($request, $sesion);
        $accion->cerrar($sesion);

        return redirect()->route('responsable.sesiones.show', $sesion)->with('estado', 'Pase de lista finalizado. Este es el resumen de asistencia.');
    }

    private function ejecutar(callable $fn, string $ok): RedirectResponse
    {
        try {
            $fn();
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', $ok);
    }

    /** RB17 / RBAC: solo el responsable asignado o la coordinación. */
    private function autorizar(Request $request, SesionAsistencia $sesion): void
    {
        $u = $request->user();
        abort_unless($u->esCoordinador() || $sesion->horario->actividad->responsable_id === $u->id, 403);
    }
}
