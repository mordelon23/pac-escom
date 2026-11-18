@extends('layouts.app', ['titulo' => 'Calendario'])
@section('contenido')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">Calendario</h1>
        <p class="mt-1 capitalize text-gray-600">{{ $mes->locale('es')->isoFormat('MMMM YYYY') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a class="btn-secundario" href="{{ route('calendario', ['mes' => $mes->subMonth()->format('Y-m'), 'mias' => $soloMias ?: null]) }}" aria-label="Mes anterior">←</a>
        <a class="btn-secundario" href="{{ route('calendario', ['mias' => $soloMias ?: null]) }}">Hoy</a>
        <a class="btn-secundario" href="{{ route('calendario', ['mes' => $mes->addMonth()->format('Y-m'), 'mias' => $soloMias ?: null]) }}" aria-label="Mes siguiente">→</a>
        <a href="{{ route('calendario', ['mes' => $mes->format('Y-m'), 'mias' => $soloMias ? null : 1]) }}" @class(['btn', 'bg-guinda-700 text-white' => $soloMias, 'btn-secundario' => ! $soloMias])>Solo mis actividades</a>
        <a class="btn-secundario" href="{{ route('calendario.ics') }}" title="Archivo .ics para Google Calendar, Outlook o el calendario del teléfono">Exportar a mi calendario</a>
    </div>
</div>

{{-- Vista de cuadrícula (pantallas medianas y grandes) --}}
<div class="tarjeta mt-6 hidden overflow-hidden md:block">
    <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase text-gray-600">
        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)<div class="py-2">{{ $d }}</div>@endforeach
    </div>
    <div class="grid grid-cols-7">
        @foreach ($dias as $dia)
            <div @class(['min-h-28 border-b border-r border-gray-100 p-1.5', 'bg-gray-50/70 text-gray-500' => ! $dia['delMes']])>
                <div @class(['mb-1 inline-grid h-6 w-6 place-items-center rounded-full text-xs font-semibold', 'bg-guinda-700 text-white' => $dia['fecha']->isToday()])>{{ $dia['fecha']->day }}</div>
                @foreach (array_slice($dia['eventos'], 0, 3) as $e)
                    <a href="{{ route('actividades.show', $e['actividad']) }}" title="{{ $e['actividad']->titulo }} · {{ $e['horario']->rango() }}"
                       @class(['mb-0.5 block truncate rounded px-1.5 py-0.5 text-[11px] leading-4', 'bg-guinda-700 text-white' => $e['inscrito'], 'bg-guinda-100 text-guinda-900 hover:bg-guinda-200' => ! $e['inscrito']])>
                        {{ substr($e['horario']->hora_inicio, 0, 5) }} {{ $e['actividad']->titulo }}</a>
                @endforeach
                @if (count($dia['eventos']) > 3)<span class="px-1 text-[11px] text-gray-500">+{{ count($dia['eventos']) - 3 }} más</span>@endif
            </div>
        @endforeach
    </div>
</div>

{{-- Vista de lista (teléfono) --}}
<ul class="mt-6 space-y-3 md:hidden">
    @foreach (array_filter($dias, fn ($d) => $d['delMes'] && $d['eventos']) as $dia)
        <li class="tarjeta p-4">
            <h2 @class(['text-sm font-semibold capitalize', 'text-guinda-700' => $dia['fecha']->isToday()])>{{ $dia['fecha']->locale('es')->isoFormat('dddd D') }}</h2>
            <ul class="mt-2 space-y-1.5">
                @foreach ($dia['eventos'] as $e)
                    <li class="flex gap-3 text-sm"><span class="w-12 shrink-0 text-gray-500">{{ substr($e['horario']->hora_inicio, 0, 5) }}</span>
                        <a href="{{ route('actividades.show', $e['actividad']) }}" @class(['font-medium', 'text-guinda-700' => $e['inscrito']])>{{ $e['actividad']->titulo }}</a></li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>
<p class="mt-4 flex items-center gap-4 text-xs text-gray-600">
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-guinda-700"></span> Inscrito</span>
    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-guinda-100"></span> Disponible</span>
</p>
@endsection
