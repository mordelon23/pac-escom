@extends('layouts.app', ['titulo' => 'Inicio'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Hola, {{ auth()->user()->nombre }}</h1>
<p class="mt-1 text-gray-600">Tienes {{ $activas->count() }} de 5 inscripciones activas.</p>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <section class="lg:col-span-2" aria-labelledby="proximas">
        <div class="flex items-center justify-between">
            <h2 id="proximas" class="text-lg font-semibold">Próximas sesiones</h2>
            <a href="{{ route('inscripciones.index') }}" class="text-sm font-medium text-guinda-700 hover:underline">Ver mis actividades</a>
        </div>
        @forelse ($proximas as $p)
            @if ($loop->first)<ul class="tarjeta mt-3 divide-y divide-gray-100">@endif
            <li class="flex items-center gap-4 p-4">
                <div class="w-14 shrink-0 rounded-lg bg-guinda-50 py-1.5 text-center">
                    <div class="text-xs font-semibold uppercase text-guinda-700">{{ $p['fecha']->locale('es')->isoFormat('ddd') }}</div>
                    <div class="text-xl font-bold leading-none text-guinda-900">{{ $p['fecha']->day }}</div>
                </div>
                <div class="min-w-0">
                    <a href="{{ route('actividades.show', $p['actividad']) }}" class="font-semibold hover:text-guinda-700">{{ $p['actividad']->titulo }}</a>
                    <p class="text-sm text-gray-600">{{ $p['horario']->rango() }} · {{ $p['actividad']->lugar }}</p>
                </div>
                @if ($p['fecha']->isToday())<span class="chip ml-auto bg-guinda-700 text-white">Hoy</span>@endif
            </li>
            @if ($loop->last)</ul>@endif
        @empty
            <div class="tarjeta mt-3 p-8 text-center text-gray-600">
                Aún no tienes sesiones programadas esta semana.
                <a href="{{ route('actividades.index') }}" class="mt-3 block font-semibold text-guinda-700 hover:underline">Explorar el catálogo</a>
            </div>
        @endforelse
    </section>

    <aside class="tarjeta h-fit p-5">
        <h2 class="font-semibold">Registrar asistencia</h2>
        <p class="mt-1 text-sm text-gray-600">Cuando el responsable muestre el código QR, escanéalo desde tu teléfono.</p>
        <a href="{{ route('asistencia.escanear') }}" class="btn-primario mt-4 w-full">Escanear código QR</a>
        <p class="mt-2 text-xs text-gray-500">También puedes escanearlo con la app de cámara de tu teléfono.</p>
    </aside>
</div>

<section class="mt-10" aria-labelledby="recomendadas">
    <h2 id="recomendadas" class="text-lg font-semibold">Recomendadas para ti</h2>
    <p class="text-sm text-gray-600">Según las categorías de las actividades que has elegido.</p>
    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($recomendadas as $actividad)
            @include('partials.tarjeta-actividad', ['actividad' => $actividad])
        @empty
            <p class="text-gray-600">No hay recomendaciones por ahora.</p>
        @endforelse
    </div>
</section>
@endsection
