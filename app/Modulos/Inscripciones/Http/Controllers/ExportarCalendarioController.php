<?php

namespace App\Modulos\Inscripciones\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Inscripciones\Servicios\CalendarioIcs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Descarga de las actividades inscritas en formato iCalendar (.ics). */
class ExportarCalendarioController extends Controller
{
    public function __invoke(Request $request, CalendarioIcs $ics): Response
    {
        return response($ics->generar($request->user()), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="mis-actividades-pac.ics"',
        ]);
    }
}
