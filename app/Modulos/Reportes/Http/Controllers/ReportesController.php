<?php

namespace App\Modulos\Reportes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Reportes\Servicios\ReporteParticipacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CU12 — Generar reportes de participación (pantalla, impresión/PDF y Excel/CSV). */
class ReportesController extends Controller
{
    public function index(Request $request, ReporteParticipacion $reporte)
    {
        $f = $request->validate([
            'desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'], 'formato' => ['nullable', 'in:csv,imprimir'],
        ]);
        $desde = CarbonImmutable::parse($f['desde'] ?? now()->startOfMonth()->subMonth());
        $hasta = CarbonImmutable::parse($f['hasta'] ?? now()->endOfMonth());
        $filas = $reporte->generar($desde, $hasta, $f['categoria'] ?? null);

        if (($f['formato'] ?? null) === 'csv') {
            Auditor::registrar('reporte.exportar', 'actividades', null, ['formato' => 'csv', 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]);

            return $this->csv($filas, $desde, $hasta);
        }

        $vista = ($f['formato'] ?? null) === 'imprimir' ? 'coordinacion.reporte-imprimible' : 'coordinacion.reportes';
        if ($vista === 'coordinacion.reporte-imprimible') {
            Auditor::registrar('reporte.exportar', 'actividades', null, ['formato' => 'pdf']);
        }

        return view($vista, [
            'filas' => $filas, 'desde' => $desde, 'hasta' => $hasta,
            'categorias' => Categoria::orderBy('nombre')->get(), 'categoria' => $f['categoria'] ?? null,
            'totales' => [
                'inscritos' => $filas->sum('inscritos'), 'sesiones' => $filas->sum('sesiones'),
                'asistencias' => $filas->sum('asistencias'), 'manuales' => $filas->sum('manuales'),
            ],
        ]);
    }

    private function csv($filas, $desde, $hasta): StreamedResponse
    {
        $nombre = 'reporte-participacion-' . $desde->format('Ymd') . '-' . $hasta->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel reconozca UTF-8
            fputcsv($out, ['Actividad', 'Categoría', 'Cupo', 'Inscritos', 'Ocupación %', 'Sesiones', 'Asistencias', 'Registros manuales', 'Asistencia %']);
            foreach ($filas as $r) {
                fputcsv($out, [$r->titulo, $r->categoria, $r->cupo_maximo, $r->inscritos, $r->ocupacion, $r->sesiones, $r->asistencias, $r->manuales, $r->porcentaje ?? '']);
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
