<?php

namespace App\Modulos\Reportes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Exportacion\HojaXlsx;
use App\Modulos\Reportes\Servicios\ReporteParticipacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CU12 — Generar reportes de participación (pantalla, impresión/PDF y Excel/CSV). */
class ReportesController extends Controller
{
    public function index(Request $request, ReporteParticipacion $reporte)
    {
        $f = $request->validate([
            'desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'], 'formato' => ['nullable', 'in:xlsx,csv,imprimir'],
        ]);
        $desde = CarbonImmutable::parse($f['desde'] ?? now()->startOfMonth()->subMonth());
        $hasta = CarbonImmutable::parse($f['hasta'] ?? now()->endOfMonth());
        $filas = $reporte->generar($desde, $hasta, $f['categoria'] ?? null);

        if (in_array($f['formato'] ?? null, ['csv', 'xlsx'], true)) {
            Auditor::registrar('reporte.exportar', 'actividades', null, ['formato' => $f['formato'], 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]);

            return $f['formato'] === 'xlsx' ? $this->xlsx($filas, $desde, $hasta) : $this->csv($filas, $desde, $hasta);
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

    private const COLUMNAS = ['Actividad', 'Categoría', 'Cupo', 'Inscritos', 'Ocupación %', 'Sesiones', 'Asistencias', 'Registros manuales', 'Asistencia %'];

    private static function valores(object $r): array
    {
        return [$r->titulo, $r->categoria, (int) $r->cupo_maximo, (int) $r->inscritos, (float) $r->ocupacion, (int) $r->sesiones,
            (int) $r->asistencias, (int) $r->manuales, $r->porcentaje === null ? null : (float) $r->porcentaje];
    }

    /** Excel nativo (.xlsx): los números se pueden sumar, filtrar y graficar directamente. */
    private function xlsx($filas, $desde, $hasta): Response
    {
        $total = fn (string $c) => (int) $filas->sum($c);
        $esperadas = $filas->sum(fn ($r) => $r->sesiones * $r->inscritos);
        $contenido = HojaXlsx::crear(
            'Reporte de participación y asistencia — PAC ESCOM',
            'Periodo: '.$desde->format('d/m/Y').' al '.$hasta->format('d/m/Y').' · Generado: '.now()->format('d/m/Y H:i'),
            self::COLUMNAS,
            $filas->map(fn ($r) => self::valores($r))->all(),
            ['Total', '', '', $total('inscritos'), null, $total('sesiones'), $total('asistencias'), $total('manuales'),
                $esperadas > 0 ? round($total('asistencias') * 100 / $esperadas, 1) : null],
            [34, 16, 8, 10, 12, 10, 12, 12, 13],
        );
        $nombre = 'reporte-participacion-'.$desde->format('Ymd').'-'.$hasta->format('Ymd').'.xlsx';

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    private function csv($filas, $desde, $hasta): StreamedResponse
    {
        $nombre = 'reporte-participacion-'.$desde->format('Ymd').'-'.$hasta->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // BOM para que Excel reconozca UTF-8
            fputcsv($out, self::COLUMNAS);
            foreach ($filas as $r) {
                // Un texto que inicia con = + - @ se antepone con ' para que Excel no lo ejecute como fórmula.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, self::valores($r)));
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
