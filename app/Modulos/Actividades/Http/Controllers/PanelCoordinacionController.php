<?php

namespace App\Modulos\Actividades\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Modelos\Publicacion;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Panel de coordinación: indicadores y actividades que requieren atención. */
class PanelCoordinacionController extends Controller
{
    public function __invoke(): View
    {
        $porCategoria = DB::table('inscripciones as i')
            ->join('actividades as a', 'a.id', '=', 'i.actividad_id')
            ->join('categorias as c', 'c.id', '=', 'a.categoria_id')
            ->where('i.estado', EstadoInscripcion::ACTIVA->value)
            ->groupBy('c.nombre')->orderByDesc(DB::raw('count(*)'))
            ->selectRaw('c.nombre, count(*) as total')->get();

        return view('coordinacion.panel', [
            'kpi' => [
                'publicadas' => Actividad::where('estado', EstadoActividad::PUBLICADA)->count(),
                'borradores' => Actividad::where('estado', EstadoActividad::BORRADOR)->count(),
                'inscripciones' => DB::table('inscripciones')->where('estado', EstadoInscripcion::ACTIVA->value)->count(),
                'sesiones' => DB::table('sesiones_asistencia')->count(),
                'asistencias' => DB::table('asistencias')->count(),
                'pendientes' => Publicacion::where('estado_moderacion', EstadoModeracion::PENDIENTE)->count(),
            ],
            'porCategoria' => $porCategoria,
            'casiLlenas' => Actividad::publicadas()->with('categoria')
                ->whereRaw('cupo_ocupado * 100 >= cupo_maximo * 90')->orderByDesc('cupo_ocupado')->limit(5)->get(),
            'borradores' => Actividad::where('estado', EstadoActividad::BORRADOR)->latest()->limit(5)->get(),
        ]);
    }
}
