<?php

namespace App\Modulos\Comunidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Comunidad\Acciones\PublicarEnMuro;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Modelos\Publicacion;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU8 — Muro social del alumno. */
class MuroController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();

        return view('alumno.muro', [
            'publicaciones' => Publicacion::with(['autor', 'actividad.categoria'])
                ->where('estado_moderacion', EstadoModeracion::APROBADA)->latest()->paginate(10),
            'mias' => Publicacion::with('actividad')->where('usuario_id', $u->id)
                ->where('estado_moderacion', '!=', EstadoModeracion::APROBADA)->latest()->limit(5)->get(),
            'actividades' => Actividad::whereIn('id', $u->inscripciones()->pluck('actividad_id'))->orderBy('titulo')->get(['id', 'titulo']),
        ]);
    }

    public function store(Request $request, PublicarEnMuro $accion): RedirectResponse
    {
        $datos = $request->validate([
            'actividad_id' => ['required', 'integer'],
            'contenido' => ['required', 'string', 'min:1', 'max:500'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], ['contenido' => 'comentario', 'actividad_id' => 'actividad', 'foto' => 'fotografía']);

        try {
            $accion->ejecutar($request->user(), (int) $datos['actividad_id'], $datos['contenido'], $request->file('foto'));
        } catch (ReglaNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Tu publicación será visible cuando la coordinación la apruebe.');
    }

    public function destroy(Request $request, Publicacion $publicacion): RedirectResponse
    {
        abort_unless($publicacion->usuario_id === $request->user()->id, 403);
        $publicacion->delete();

        return back()->with('estado', 'Publicación eliminada.');
    }
}
