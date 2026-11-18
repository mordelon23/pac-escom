<?php

namespace App\Modulos\Inscripciones\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU7 — Agenda cultural: actividades marcadas como favoritas (RF10). */
class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $inscritas = $request->user()->inscripcionesActivas()->pluck('actividad_id')->all();

        return view('alumno.agenda', [
            'actividades' => $request->user()->agenda()->with(['categoria', 'horarios'])->orderBy('fecha_inicio')->get(),
            'inscritas' => $inscritas,
        ]);
    }

    public function alternar(Request $request, Actividad $actividad): RedirectResponse
    {
        abort_unless($actividad->estaPublicada(), 404);
        $cambios = $request->user()->agenda()->toggle($actividad->id);

        return back()->with('estado', $cambios['attached'] ? 'Agregada a tu agenda cultural.' : 'Quitada de tu agenda cultural.');
    }
}
