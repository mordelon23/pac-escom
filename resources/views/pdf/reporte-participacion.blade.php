<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">@include('pdf.estilos')</head>
<body>
<div class="encabezado">
    <div class="marca">INSTITUTO POLITÉCNICO NACIONAL · ESCUELA SUPERIOR DE CÓMPUTO</div>
    <h1>Reporte de participación y asistencia</h1>
    <div class="sub">Portal de Actividades Culturales · Periodo {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}{{ $categoriaNombre ? ' · Categoría: '.$categoriaNombre : '' }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ $autor }}</div>
</div>
<table>
    <thead><tr><th>Actividad</th><th>Categoría</th><th class="num">Cupo</th><th class="num">Inscritos</th><th class="num">Ocupación</th><th class="num">Sesiones</th><th class="num">Asistencias</th><th class="num">Manuales</th><th class="num">% asistencia</th></tr></thead>
    <tbody>
    @forelse ($filas as $r)
        <tr><td>{{ $r->titulo }}</td><td>{{ $r->categoria }}</td><td class="num">{{ $r->cupo_maximo }}</td><td class="num">{{ $r->inscritos }}</td>
            <td class="num">{{ $r->ocupacion }} %</td><td class="num">{{ $r->sesiones }}</td><td class="num">{{ $r->asistencias }}</td>
            <td class="num">{{ $r->manuales }}</td><td class="num">{{ $r->porcentaje !== null ? $r->porcentaje.' %' : '—' }}</td></tr>
    @empty
        <tr><td colspan="9" class="centro">No hay actividades en el periodo seleccionado.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><td colspan="3">Totales</td><td class="num">{{ $totales['inscritos'] }}</td><td></td><td class="num">{{ $totales['sesiones'] }}</td>
        <td class="num">{{ $totales['asistencias'] }}</td><td class="num">{{ $totales['manuales'] }}</td><td class="num">{{ $totales['porcentaje'] !== null ? $totales['porcentaje'].' %' : '—' }}</td></tr></tfoot>
</table>
<p class="nota">% asistencia = asistencias ÷ (sesiones realizadas × inscritos activos). Los registros manuales corresponden al modo contingencia (RB23).</p>
</body></html>
