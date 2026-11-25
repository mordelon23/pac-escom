<?php

namespace App\Modulos\Asistencia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Asistencia\Servicios\ListaAsistencia;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Exportacion\DocumentoPdf;
use App\Modulos\Compartido\Exportacion\HojaXlsx;
use Illuminate\Http\Request;

/** RF9 — Lista de asistencia por actividad (pantalla, Excel y PDF). Solo su responsable o coordinación (RB17). */
class ListaAsistenciaController extends Controller
{
    public function __invoke(Request $request, Actividad $actividad, ListaAsistencia $lista)
    {
        $u = $request->user();
        abort_unless($u->esCoordinador() || $actividad->responsable_id === $u->id, 403);
        $formato = $request->validate(['formato' => ['nullable', 'in:xlsx,pdf']])['formato'] ?? null;

        ['sesiones' => $sesiones, 'alumnos' => $alumnos] = $lista->generar($actividad);
        $actividad->loadMissing('responsable', 'categoria');
        $nombre = 'lista-asistencia-'.str($actividad->titulo)->slug().'-'.now()->format('Ymd');

        if ($formato) {
            Auditor::registrar('lista_asistencia.exportar', 'actividades', $actividad->id, ['formato' => $formato]);
        }

        if ($formato === 'xlsx') {
            $simbolo = fn (?string $m) => match ($m) {
                'QR' => '✓', 'MANUAL' => 'M', default => '—'
            };
            $contenido = HojaXlsx::crear(
                'Lista de asistencia — '.$actividad->titulo,
                'Responsable: '.($actividad->responsable?->nombreCompleto() ?? '—').' · ✓ = QR · M = registro manual · — = falta · Generado: '.now()->format('d/m/Y H:i'),
                ['Boleta', 'Alumno', ...$sesiones->map(fn ($s) => $s->fecha->format('d/m')), 'Asistencias', 'Asistencia %'],
                $alumnos->map(fn ($a) => [$a->matricula, $a->nombre, ...array_map($simbolo, $a->celdas), $a->asistencias, $a->porcentaje])->all(),
                null,
                [12, 32, ...array_fill(0, $sesiones->count(), 7), 12, 13],
            );

            return response($contenido, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$nombre.'.xlsx"',
            ]);
        }

        $datos = compact('actividad', 'sesiones', 'alumnos');
        if ($formato === 'pdf') {
            return DocumentoPdf::descarga(DocumentoPdf::desdeVista('pdf.lista-asistencia', $datos + ['autor' => $u->nombreCompleto()], horizontal: true), $nombre.'.pdf');
        }

        return view('responsable.lista-asistencia', $datos);
    }
}
