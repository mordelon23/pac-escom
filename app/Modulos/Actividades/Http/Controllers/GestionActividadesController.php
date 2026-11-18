<?php

namespace App\Modulos\Actividades\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Acciones\CancelarActividad;
use App\Modulos\Actividades\Acciones\GuardarActividad;
use App\Modulos\Actividades\Acciones\PublicarActividad;
use App\Modulos\Actividades\Enums\DiaSemana;
use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Http\Requests\GuardarActividadRequest;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU11 — Gestionar actividades y cupos (panel de coordinación). */
class GestionActividadesController extends Controller
{
    public function index(Request $request): View
    {
        $estado = EstadoActividad::tryFrom((string) $request->query('estado'));
        $actividades = Actividad::with(['categoria', 'responsable', 'horarios'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($request->query('q'), fn ($q, $t) => $q->whereRaw('titulo ILIKE ?', ["%{$t}%"]))
            ->orderByRaw("CASE estado WHEN 'BORRADOR' THEN 0 WHEN 'PUBLICADA' THEN 1 ELSE 2 END")
            ->orderBy('fecha_inicio')->paginate(12)->withQueryString();

        return view('coordinacion.actividades.index', [
            'actividades' => $actividades,
            'estado' => $estado,
            'conteos' => Actividad::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
        ]);
    }

    public function create(): View
    {
        return view('coordinacion.actividades.formulario', $this->datosFormulario(new Actividad([
            'fecha_inicio' => now()->next('Monday'), 'fecha_fin' => now()->next('Monday')->addMonths(3), 'cupo_maximo' => 25,
        ])));
    }

    public function store(GuardarActividadRequest $request, GuardarActividad $accion): RedirectResponse
    {
        try {
            $actividad = $accion->ejecutar($request->user(), $request->datosActividad(), $request->file('imagen'));
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('coordinacion.actividades.show', $actividad)
            ->with('estado', 'Actividad guardada como borrador. Revísala y publícala cuando esté lista.');
    }

    public function show(Actividad $actividad): View
    {
        $actividad->load(['categoria', 'responsable', 'horarios', 'inscripcionesActivas.alumno']);
        $sesiones = $actividad->sesiones()->withCount('asistencias')->orderByDesc('fecha')->limit(10)->get();

        return view('coordinacion.actividades.show', compact('actividad', 'sesiones'));
    }

    public function edit(Actividad $actividad): View
    {
        abort_if(in_array($actividad->estado, [EstadoActividad::CANCELADA, EstadoActividad::FINALIZADA], true), 403,
            'No se puede editar una actividad cancelada o finalizada.');

        return view('coordinacion.actividades.formulario', $this->datosFormulario($actividad->load('horarios')));
    }

    public function update(GuardarActividadRequest $request, Actividad $actividad, GuardarActividad $accion): RedirectResponse
    {
        try {
            $accion->ejecutar($request->user(), $request->datosActividad(), $request->file('imagen'), $actividad);
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('coordinacion.actividades.show', $actividad)->with('estado', 'Cambios guardados correctamente.');
    }

    public function publicar(Actividad $actividad, PublicarActividad $accion): RedirectResponse
    {
        try {
            $accion->ejecutar($actividad->load('horarios'));
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Actividad publicada. Ya es visible en el catálogo.');
    }

    public function cancelar(Actividad $actividad, CancelarActividad $accion): RedirectResponse
    {
        try {
            $n = $accion->ejecutar($actividad);
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', "Actividad cancelada. Se notificó a {$n} alumno(s) inscrito(s).");
    }

    public function destroy(Actividad $actividad): RedirectResponse
    {
        if ($actividad->estado !== EstadoActividad::BORRADOR || $actividad->inscripciones()->exists()) {
            return back()->with('error', 'Solo se pueden eliminar borradores sin inscripciones; utilice la opción Cancelar.');
        }
        Auditor::registrar('actividad.eliminar', 'actividades', $actividad->id, ['titulo' => $actividad->titulo]);
        $actividad->delete();

        return redirect()->route('coordinacion.actividades.index')->with('estado', 'Borrador eliminado.');
    }

    private function datosFormulario(Actividad $actividad): array
    {
        return [
            'actividad' => $actividad,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'responsables' => Usuario::whereHas('rol', fn ($q) => $q->whereIn('clave', [RolSistema::RESPONSABLE->value, RolSistema::COORDINADOR->value]))
                ->where('estado', 'ACTIVA')->orderBy('nombre')->get(),
            'dias' => DiaSemana::cases(),
            'horarios' => $actividad->exists ? $actividad->horarios->keyBy(fn ($h) => $h->dia_semana->value) : collect(),
        ];
    }
}
