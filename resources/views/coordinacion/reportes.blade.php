@extends('layouts.app', ['titulo' => 'Reportes'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Reportes de participación</h1>
<form method="GET" class="tarjeta mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
    <div><label for="desde" class="etiqueta">Desde</label><input id="desde" type="date" name="desde" class="campo" value="{{ $desde->toDateString() }}"></div>
    <div><label for="hasta" class="etiqueta">Hasta</label><input id="hasta" type="date" name="hasta" class="campo" value="{{ $hasta->toDateString() }}"></div>
    <div><label for="categoria" class="etiqueta">Categoría</label>
        <select id="categoria" name="categoria" class="campo"><option value="">Todas</option>
            @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected($categoria == $c->id)>{{ $c->nombre }}</option>@endforeach</select></div>
    <button class="btn-primario">Generar</button>
</form>
@error('hasta')<p class="error mt-2">{{ $message }}</p>@enderror

<div class="mt-6 flex flex-wrap items-center justify-between gap-3">
    <dl class="flex flex-wrap gap-6 text-sm">
        <div><dt class="text-gray-500">Inscritos</dt><dd class="text-xl font-bold">{{ $totales['inscritos'] }}</dd></div>
        <div><dt class="text-gray-500">Sesiones</dt><dd class="text-xl font-bold">{{ $totales['sesiones'] }}</dd></div>
        <div><dt class="text-gray-500">Asistencias</dt><dd class="text-xl font-bold">{{ $totales['asistencias'] }}</dd></div>
        <div><dt class="text-gray-500">Registros manuales</dt><dd class="text-xl font-bold">{{ $totales['manuales'] }}</dd></div>
    </dl>
    <div class="flex gap-2">
        <a class="btn-secundario" href="{{ request()->fullUrlWithQuery(['formato' => 'xlsx']) }}">Descargar Excel (.xlsx)</a>
        <a class="btn-secundario" href="{{ request()->fullUrlWithQuery(['formato' => 'csv']) }}">CSV</a>
        <a class="btn-secundario" href="{{ request()->fullUrlWithQuery(['formato' => 'pdf']) }}">Descargar PDF</a>
        <a class="btn-secundario" target="_blank" href="{{ request()->fullUrlWithQuery(['formato' => 'imprimir']) }}">Versión imprimible</a>
    </div>
</div>

<div class="tarjeta mt-4 overflow-x-auto" tabindex="0" role="region" aria-label="Tabla del reporte (desplazable)">
    <table class="w-full min-w-[48rem] text-left text-sm">
        <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 font-medium">Actividad</th><th class="px-4 py-3 font-medium">Inscritos</th><th class="px-4 py-3 font-medium">Ocupación</th>
            <th class="px-4 py-3 font-medium">Sesiones</th><th class="px-4 py-3 font-medium">Asistencias</th><th class="px-4 py-3 font-medium">Manuales</th><th class="px-4 py-3 font-medium">% asistencia</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse ($filas as $r)
            <tr><td class="px-4 py-3"><div class="font-medium">{{ $r->titulo }}</div><div class="text-xs text-gray-500">{{ $r->categoria }}</div></td>
                <td class="px-4 py-3">{{ $r->inscritos }}/{{ $r->cupo_maximo }}</td><td class="px-4 py-3">{{ $r->ocupacion }} %</td>
                <td class="px-4 py-3">{{ $r->sesiones }}</td><td class="px-4 py-3">{{ $r->asistencias }}</td><td class="px-4 py-3">{{ $r->manuales }}</td>
                <td class="px-4 py-3">@if ($r->porcentaje !== null)
                    <div class="flex items-center gap-2"><div class="h-2 w-20 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-guinda-600" style="width: {{ min(100, $r->porcentaje) }}%"></div></div>{{ $r->porcentaje }} %</div>
                    @else <span class="text-gray-400">—</span> @endif</td></tr>
        @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-600">No hay información para el periodo seleccionado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<p class="mt-2 text-xs text-gray-500">% asistencia = asistencias ÷ (sesiones realizadas × inscritos activos).</p>
@endsection
