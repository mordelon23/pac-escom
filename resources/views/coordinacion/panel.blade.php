@extends('layouts.app', ['titulo' => 'Panel de coordinación'])
@section('contenido')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">Panel de coordinación</h1>
        <p class="mt-1 text-gray-600">Resumen de la oferta cultural y deportiva.</p>
    </div>
    <a href="{{ route('coordinacion.actividades.create') }}" class="btn-primario">+ Nueva actividad</a>
</div>

<dl class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-6">
    @foreach ([['Publicadas', $kpi['publicadas']], ['Borradores', $kpi['borradores']], ['Inscripciones activas', $kpi['inscripciones']],
               ['Sesiones realizadas', $kpi['sesiones']], ['Asistencias', $kpi['asistencias']], ['Publicaciones por moderar', $kpi['pendientes']]] as [$t, $v])
        <div class="tarjeta p-4">
            <dt class="text-xs font-medium text-gray-500">{{ $t }}</dt>
            <dd class="mt-1 text-2xl font-bold text-guinda-800">{{ number_format($v) }}</dd>
        </div>
    @endforeach
</dl>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="tarjeta p-5" aria-labelledby="t-cat">
        <h2 id="t-cat" class="font-semibold">Inscripciones activas por categoría</h2>
        @php($max = max(1, $porCategoria->max('total') ?? 1))
        <ul class="mt-4 space-y-3">
            @forelse ($porCategoria as $c)
                <li>
                    <div class="flex justify-between text-sm"><span>{{ $c->nombre }}</span><span class="font-semibold">{{ $c->total }}</span></div>
                    <div class="mt-1 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-guinda-600" style="width: {{ round($c->total * 100 / $max) }}%"></div></div>
                </li>
            @empty
                <li class="text-sm text-gray-600">Aún no hay inscripciones.</li>
            @endforelse
        </ul>
    </section>

    <section class="tarjeta p-5" aria-labelledby="t-atn">
        <h2 id="t-atn" class="font-semibold">Requieren atención</h2>
        <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Cupo al 90 % o más</h3>
        <ul class="mt-2 divide-y divide-gray-100">
            @forelse ($casiLlenas as $a)
                <li class="flex items-center justify-between py-2 text-sm">
                    <a href="{{ route('coordinacion.actividades.show', $a) }}" class="font-medium hover:text-guinda-700">{{ $a->titulo }}</a>
                    <span class="chip {{ $a->cupoLleno() ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-900' }}">{{ $a->cupo_ocupado }}/{{ $a->cupo_maximo }}</span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-600">Ninguna actividad cerca de llenarse.</li>
            @endforelse
        </ul>
        <h3 class="mt-5 text-xs font-semibold uppercase tracking-wide text-gray-500">Borradores sin publicar</h3>
        <ul class="mt-2 divide-y divide-gray-100">
            @forelse ($borradores as $a)
                <li class="flex items-center justify-between py-2 text-sm">
                    <a href="{{ route('coordinacion.actividades.show', $a) }}" class="font-medium hover:text-guinda-700">{{ $a->titulo }}</a>
                    <span class="text-gray-500">{{ $a->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-600">No hay borradores pendientes.</li>
            @endforelse
        </ul>
        @if ($kpi['pendientes'] > 0)
            <a href="{{ route('coordinacion.muro.index') }}" class="btn-secundario mt-5 w-full">Moderar {{ $kpi['pendientes'] }} publicación(es)</a>
        @endif
    </section>
</div>
@endsection
