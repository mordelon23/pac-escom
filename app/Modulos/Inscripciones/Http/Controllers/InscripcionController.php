<?php

namespace App\Modulos\Inscripciones\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Inscripciones\Acciones\CancelarInscripcion;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU4 y CU5 — El controlador solo traduce la petición; las reglas viven en las acciones. */
class InscripcionController extends Controller
{
    public function index(Request $request): View
    {
        $inscripciones = $request->user()->inscripciones()
            ->with(['actividad.categoria', 'actividad.horarios'])
            ->orderByRaw("CASE estado WHEN 'ACTIVA' THEN 0 ELSE 1 END")
            ->latest('fecha_inscripcion')->get();

        return view('alumno.mis-actividades', [
            'activas' => $inscripciones->where('estado', EstadoInscripcion::ACTIVA),
            'historial' => $inscripciones->where('estado', '!=', EstadoInscripcion::ACTIVA),
        ]);
    }

    public function store(Request $request, Actividad $actividad, InscribirAlumno $accion): RedirectResponse
    {
        try {
            $accion->ejecutar($request->user(), $actividad->id);
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Inscripción realizada. Te enviamos un correo de confirmación.');
    }

    public function destroy(Request $request, Inscripcion $inscripcion, CancelarInscripcion $accion): RedirectResponse
    {
        try {
            $accion->ejecutar($request->user(), $inscripcion);
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Inscripción cancelada. Se liberó tu lugar.');
    }
}
