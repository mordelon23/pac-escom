<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>Reporte de participación</title>
<style>
    body { font-family: Arial, sans-serif; color: #111; margin: 32px; font-size: 12px; }
    h1 { color: #6C1D45; font-size: 20px; margin: 0; } .sub { color: #555; margin: 4px 0 18px; }
    table { width: 100%; border-collapse: collapse; } th { background: #6C1D45; color: #fff; text-align: left; }
    th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; } tfoot td { font-weight: bold; border-top: 2px solid #6C1D45; }
    .nota { margin-top: 12px; color: #555; font-size: 11px; } @media print { .no-imprimir { display: none; } body { margin: 0; } }
</style></head>
<body>
<button class="no-imprimir" onclick="window.print()" style="float:right;padding:8px 14px">Imprimir / Guardar como PDF</button>
<h1>Reporte de participación y asistencia</h1>
<p class="sub">Portal de Actividades Culturales · ESCOM · Periodo {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombreCompleto() }}</p>
<table>
    <thead><tr><th>Actividad</th><th>Categoría</th><th>Inscritos</th><th>Ocupación</th><th>Sesiones</th><th>Asistencias</th><th>Manuales</th><th>% asistencia</th></tr></thead>
    <tbody>@foreach ($filas as $r)<tr><td>{{ $r->titulo }}</td><td>{{ $r->categoria }}</td><td>{{ $r->inscritos }}/{{ $r->cupo_maximo }}</td><td>{{ $r->ocupacion }} %</td>
        <td>{{ $r->sesiones }}</td><td>{{ $r->asistencias }}</td><td>{{ $r->manuales }}</td><td>{{ $r->porcentaje !== null ? $r->porcentaje . ' %' : '—' }}</td></tr>@endforeach</tbody>
    <tfoot><tr><td colspan="2">Totales</td><td>{{ $totales['inscritos'] }}</td><td></td><td>{{ $totales['sesiones'] }}</td><td>{{ $totales['asistencias'] }}</td><td>{{ $totales['manuales'] }}</td><td></td></tr></tfoot>
</table>
<p class="nota">% asistencia = asistencias ÷ (sesiones realizadas × inscritos activos). Los registros manuales corresponden al modo contingencia (RB23).</p>
</body></html>
