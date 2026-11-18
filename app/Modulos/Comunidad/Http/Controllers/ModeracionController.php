<?php

namespace App\Modulos\Comunidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Comunidad\Acciones\ModerarPublicacion;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Modelos\Publicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU13 — Moderar publicaciones del muro social. */
class ModeracionController extends Controller
{
    public function index(): View
    {
        return view('coordinacion.muro', [
            'pendientes' => Publicacion::with(['autor', 'actividad'])->where('estado_moderacion', EstadoModeracion::PENDIENTE)->oldest()->get(),
            'recientes' => Publicacion::with(['autor', 'actividad'])->where('estado_moderacion', '!=', EstadoModeracion::PENDIENTE)
                ->latest('updated_at')->limit(10)->get(),
        ]);
    }

    public function aprobar(Request $request, Publicacion $publicacion, ModerarPublicacion $accion): RedirectResponse
    {
        try {
            $accion->aprobar($request->user(), $publicacion);
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Publicación aprobada; ya aparece en el muro.');
    }

    public function rechazar(Request $request, Publicacion $publicacion, ModerarPublicacion $accion): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], [], ['motivo' => 'motivo del rechazo']);
        try {
            $accion->rechazar($request->user(), $publicacion, $datos['motivo']);
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('estado', 'Publicación rechazada; se notificó el motivo al autor.');
    }
}
