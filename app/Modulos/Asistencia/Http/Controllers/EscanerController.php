<?php

namespace App\Modulos\Asistencia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Asistencia\Acciones\RegistrarAsistenciaQR;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU6 — Registrar asistencia con el código QR.
 * El QR contiene una URL; si el alumno lo escanea con la cámara del teléfono llega a esta página con los parámetros
 * s (sesión) y t (token) y confirma con un botón (POST). También puede escanear desde el lector integrado.
 */
class EscanerController extends Controller
{
    public function create(Request $request): View
    {
        return view('alumno.escanear', [
            'sesion' => $request->integer('s') ?: null,
            'token' => preg_match('/^[a-f0-9]{64}$/', (string) $request->query('t')) ? $request->query('t') : null,
        ]);
    }

    public function store(Request $request, RegistrarAsistenciaQR $accion): JsonResponse|RedirectResponse
    {
        $datos = $request->validate(['sesion' => ['required', 'integer'], 'token' => ['required', 'string', 'size:64']]);

        try {
            $a = $accion->ejecutar($request->user(), (int) $datos['sesion'], $datos['token']);
            $a->load('sesion.horario.actividad');
            $msg = 'Asistencia registrada: ' . $a->sesion->horario->actividad->titulo . ' (' . $a->registrada_en->format('H:i') . ').';
            $ok = true;
        } catch (ReglaNegocioException $e) {
            $msg = $e->getMessage();
            $ok = false;
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'mensaje' => $msg], $ok ? 201 : 422);
        }

        return redirect()->route('asistencia.escanear')->with($ok ? 'estado' : 'error', $msg);
    }
}
