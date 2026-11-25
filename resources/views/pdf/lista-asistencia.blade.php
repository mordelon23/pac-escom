<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">@include('pdf.estilos')
<style> td.c, th.c { text-align: center; padding: 3px 1px; } .qr { color: #047857; font-weight: bold; } .m { color: #b45309; font-weight: bold; } .f { color: #999; } </style>
</head>
<body>
<div class="encabezado">
    <div class="marca">INSTITUTO POLITÉCNICO NACIONAL · ESCUELA SUPERIOR DE CÓMPUTO</div>
    <h1>Lista de asistencia — {{ $actividad->titulo }}</h1>
    <div class="sub">{{ $actividad->categoria?->nombre }} · {{ $actividad->lugar }} · Responsable: {{ $actividad->responsable?->nombreCompleto() ?? '—' }} · Periodo {{ $actividad->fecha_inicio->format('d/m/Y') }} al {{ $actividad->fecha_fin->format('d/m/Y') }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ $autor }}</div>
</div>
@if ($sesiones->isEmpty() || $alumnos->isEmpty())
    <p>No hay sesiones o alumnos inscritos para mostrar.</p>
@else
<table>
    <thead><tr><th>Boleta</th><th>Alumno</th>@foreach ($sesiones as $s)<th class="c">{{ $s->fecha->format('d/m') }}</th>@endforeach<th class="num">Total</th><th class="num">%</th></tr></thead>
    <tbody>
    @foreach ($alumnos as $a)
        <tr><td>{{ $a->matricula }}</td><td>{{ $a->nombre }}</td>
            @foreach ($a->celdas as $m)<td class="c">@if ($m === 'QR')<span class="qr">✓</span>@elseif ($m === 'MANUAL')<span class="m">M</span>@else<span class="f">—</span>@endif</td>@endforeach
            <td class="num">{{ $a->asistencias }}/{{ $sesiones->count() }}</td><td class="num">{{ $a->porcentaje }} %</td></tr>
    @endforeach
    </tbody>
</table>
<p class="nota">✓ = asistencia registrada con código QR · M = registro manual en modo contingencia (RB23) · — = falta.</p>
@endif
</body></html>
