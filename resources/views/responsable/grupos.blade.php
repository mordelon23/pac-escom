@extends('layouts.app', ['titulo' => 'Mis grupos'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">{{ auth()->user()->esCoordinador() ? 'Sesiones de asistencia' : 'Mis grupos' }}</h1>
<p class="mt-1 text-gray-600">Hoy es {{ today()->locale('es')->isoFormat('dddd D [de] MMMM') }}.</p>

<section class="mt-6" aria-labelledby="t-hoy">
    <h2 id="t-hoy" class="text-lg font-semibold">Sesiones de hoy</h2>
    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($hoy as $x)
            @php($s = $sesionesHoy[$x['horario']->id] ?? null)
            <article class="tarjeta flex flex-col p-5">
                <span class="chip w-fit bg-guinda-100 text-guinda-800">{{ $x['horario']->rango() }}</span>
                <h3 class="mt-2 font-semibold">{{ $x['actividad']->titulo }}</h3>
                <p class="text-sm text-gray-600">{{ $x['actividad']->lugar }} · {{ $x['actividad']->cupo_ocupado }} inscritos</p>
                <div class="mt-auto pt-4">
                    @if (! $s)
                        <form method="POST" action="{{ route('responsable.sesiones.store', $x['horario']) }}">@csrf
                            <button class="btn-primario w-full">Abrir sesión de asistencia</button></form>
                    @elseif ($s->estado->value === 'CERRADA')
                        <a href="{{ route('responsable.sesiones.show', $s) }}" class="btn-secundario w-full">Ver resumen</a>
                    @else
                        <a href="{{ route('responsable.sesiones.show', $s) }}" class="btn-primario w-full">Continuar sesión</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="tarjeta col-span-full p-8 text-center text-gray-600">No tienes sesiones programadas para hoy.</div>
        @endforelse
    </div>
</section>

<div class="mt-10 grid gap-6 lg:grid-cols-2">
    <section class="tarjeta p-5" aria-labelledby="t-grupos">
        <h2 id="t-grupos" class="font-semibold">{{ auth()->user()->esCoordinador() ? 'Actividades publicadas' : 'Actividades asignadas' }}</h2>
        <ul class="mt-3 divide-y divide-gray-100 text-sm">
            @forelse ($actividades as $a)
                <li class="py-2.5">
                    <div class="font-medium">{{ $a->titulo }}</div>
                    <div class="text-gray-600">{{ $a->horarios->map(fn ($h) => $h->dia_semana->abreviatura() . ' ' . $h->rango())->implode(' · ') }}</div>
                    <a href="{{ route('responsable.lista', $a) }}" class="mt-1 inline-block text-xs font-medium text-guinda-700 hover:underline">Lista de asistencia</a>
                </li>
            @empty
                <li class="py-4 text-gray-600">No tienes actividades asignadas.</li>
            @endforelse
        </ul>
    </section>
    <section class="tarjeta p-5" aria-labelledby="t-rec">
        <h2 id="t-rec" class="font-semibold">Sesiones recientes</h2>
        <ul class="mt-3 divide-y divide-gray-100 text-sm">
            @forelse ($recientes as $s)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <div><a href="{{ route('responsable.sesiones.show', $s) }}" class="font-medium hover:text-guinda-700">{{ $s->horario->actividad->titulo }}</a>
                        <div class="text-gray-600">{{ $s->fecha->format('d/m/Y') }}</div></div>
                    <span class="chip bg-gray-100 text-gray-700">{{ $s->asistencias_count }} asistentes</span>
                </li>
            @empty
                <li class="py-4 text-gray-600">Aún no has abierto sesiones.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
