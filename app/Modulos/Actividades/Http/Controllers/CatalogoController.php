<?php

namespace App\Modulos\Actividades\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Enums\DiaSemana;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Modelos\Categoria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU3 — Consultar catálogo con filtros y búsqueda (RF3, RF6). */
class CatalogoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'dia' => ['nullable', 'integer', 'between:1,5'],
            'fecha' => ['nullable', 'date'],
            'lugar' => ['nullable', 'string', 'max:120'],
            'disponibles' => ['nullable', 'boolean'],
        ]);

        $actividades = Actividad::publicadas()
            ->with(['categoria', 'horarios'])
            ->where('fecha_fin', '>=', today())
            ->when($filtros['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w
                ->whereRaw('titulo ILIKE ?', ["%{$t}%"])
                ->orWhereRaw('descripcion ILIKE ?', ["%{$t}%"])
                ->orWhereRaw('lugar ILIKE ?', ["%{$t}%"])))
            ->when($filtros['categoria'] ?? null, fn ($q, $c) => $q->where('categoria_id', $c))
            ->when($filtros['dia'] ?? null, fn ($q, $d) => $q->whereHas('horarios', fn ($h) => $h->where('dia_semana', $d)))
            ->when($filtros['lugar'] ?? null, fn ($q, $l) => $q->where('lugar', $l))
            // Fecha: actividades vigentes ese día y con sesión en ese día de la semana.
            ->when($filtros['fecha'] ?? null, fn ($q, $f) => $q->whereDate('fecha_inicio', '<=', $f)->whereDate('fecha_fin', '>=', $f)
                ->whereHas('horarios', fn ($h) => $h->where('dia_semana', Carbon::parse($f)->dayOfWeekIso)))
            ->when($filtros['disponibles'] ?? false, fn ($q) => $q->whereColumn('cupo_ocupado', '<', 'cupo_maximo'))
            ->orderBy('fecha_inicio')->orderBy('titulo')
            ->paginate(9)->withQueryString();

        $inscritas = $request->user()->inscripcionesActivas()->pluck('actividad_id')->all();

        return view('alumno.catalogo', [
            'actividades' => $actividades,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'dias' => DiaSemana::cases(),
            'lugares' => Actividad::publicadas()->where('fecha_fin', '>=', today())->distinct()->orderBy('lugar')->pluck('lugar'),
            'filtros' => $filtros,
            'inscritas' => $inscritas,
        ]);
    }

    public function show(Request $request, Actividad $actividad): View
    {
        abort_unless($actividad->estaPublicada() || $request->user()->esCoordinador(), 404);
        $actividad->load(['categoria', 'horarios', 'responsable']);
        $inscripcion = $request->user()->inscripcionesActivas()->where('actividad_id', $actividad->id)->first();

        return view('alumno.actividad', [
            'actividad' => $actividad,
            'inscripcion' => $inscripcion,
            'activas' => $request->user()->inscripcionesActivas()->count(),
            'enAgenda' => $request->user()->agenda()->where('actividades.id', $actividad->id)->exists(),
        ]);
    }
}
