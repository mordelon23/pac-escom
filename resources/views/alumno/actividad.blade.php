@extends('layouts.app', ['titulo' => $actividad->titulo])
@section('contenido')
<a href="{{ route('actividades.index') }}" class="text-sm font-medium text-guinda-700 hover:underline">← Volver al catálogo</a>

<div class="mt-4 grid gap-8 lg:grid-cols-[1fr_22rem]">
    <article>
        <img src="{{ $actividad->urlImagen() }}" alt="Imagen de {{ $actividad->titulo }}" class="aspect-[16/9] w-full rounded-xl object-cover">
        <span class="chip mt-6 bg-guinda-100 text-guinda-800">{{ $actividad->categoria->nombre }}</span>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $actividad->titulo }}</h1>
        <p class="mt-4 whitespace-pre-line leading-relaxed text-gray-700">{{ $actividad->descripcion }}</p>

        <h2 class="mt-8 text-lg font-semibold">Horario semanal</h2>
        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach ($actividad->horarios as $h)
                <li class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <span class="font-medium">{{ $h->dia_semana->etiqueta() }}</span><span class="text-gray-700">{{ $h->rango() }}</span>
                </li>
            @endforeach
        </ul>
    </article>

    <aside class="tarjeta h-fit p-6 lg:sticky lg:top-24">
        <dl class="space-y-3 text-sm">
            <div><dt class="text-gray-500">Lugar</dt><dd class="font-medium">{{ $actividad->lugar }}</dd></div>
            <div><dt class="text-gray-500">Periodo</dt><dd class="font-medium">{{ $actividad->fecha_inicio->format('d/m/Y') }} al {{ $actividad->fecha_fin->format('d/m/Y') }}</dd></div>
            <div><dt class="text-gray-500">Responsable</dt><dd class="font-medium">{{ $actividad->responsable->nombreCompleto() }}</dd></div>
            <div>
                <dt class="text-gray-500">Cupo</dt>
                <dd class="font-medium">{{ $actividad->cuposDisponibles() }} de {{ $actividad->cupo_maximo }} lugares disponibles</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('agenda.alternar', $actividad) }}" class="mt-4">@csrf
            <button class="btn-secundario w-full">{{ $enAgenda ? '★ Quitar de mi agenda' : '☆ Guardar en mi agenda' }}</button></form>

        <div class="mt-6 border-t border-gray-100 pt-6">
            @if ($inscripcion)
                <p class="rounded-lg bg-green-50 px-3 py-2 text-sm font-medium text-green-900">Estás inscrito en esta actividad.</p>
                @if ($inscripcion->setRelation('actividad', $actividad)->esCancelable())
                    <form method="POST" action="{{ route('inscripciones.destroy', $inscripcion) }}" class="mt-4"
                          data-confirmar="¿Cancelar tu inscripción? Se liberará tu lugar.">
                        @csrf @method('DELETE')
                        <button class="btn-secundario w-full">Cancelar inscripción</button>
                    </form>
                @else
                    <p class="mt-3 text-xs text-gray-500">Ya no es posible cancelar: la actividad ya inició o faltan menos de {{ config('pac.reglas.horas_minimas_cancelacion') }} horas para su inicio (RB9).</p>
                @endif
            @elseif ($actividad->cupoLleno())
                <button class="btn-primario w-full" disabled>Cupo lleno</button>
            @elseif ($activas >= 5)
                <button class="btn-primario w-full" disabled>Inscribirme</button>
                <p class="mt-2 text-xs text-gray-600">Ya tienes 5 inscripciones activas, el máximo permitido.</p>
            @else
                <form method="POST" action="{{ route('inscripciones.store', $actividad) }}"
                      data-confirmar="¿Confirmas tu inscripción a «{{ $actividad->titulo }}»?">
                    @csrf
                    <button class="btn-primario w-full">Inscribirme</button>
                </form>
                <p class="mt-2 text-xs text-gray-500">Podrás cancelar hasta {{ config('pac.reglas.horas_minimas_cancelacion') }} horas antes del inicio.</p>
            @endif
        </div>
    </aside>
</div>
@endsection
