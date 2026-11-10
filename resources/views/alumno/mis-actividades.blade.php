@extends('layouts.app', ['titulo' => 'Mis actividades'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Mis actividades</h1>
<p class="mt-1 text-gray-600">{{ $activas->count() }} de 5 inscripciones activas.</p>

<section class="mt-6" aria-labelledby="activas">
    <h2 id="activas" class="sr-only">Inscripciones activas</h2>
    <div class="grid gap-4">
        @forelse ($activas as $i)
            <article class="tarjeta flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                <img src="{{ $i->actividad->urlImagen() }}" alt="" class="h-24 w-full rounded-lg object-cover sm:w-40">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('actividades.show', $i->actividad) }}" class="text-lg font-semibold hover:text-guinda-700">{{ $i->actividad->titulo }}</a>
                    <p class="text-sm text-gray-600">{{ $i->actividad->lugar }}</p>
                    <p class="mt-1 text-sm text-gray-700">
                        @foreach ($i->actividad->horarios as $h){{ $h->dia_semana->abreviatura() }} {{ $h->rango() }}@if (! $loop->last) · @endif @endforeach
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Folio {{ str_pad($i->id, 6, '0', STR_PAD_LEFT) }} · inscrito el {{ $i->fecha_inscripcion->format('d/m/Y') }}</p>
                </div>
                @if ($i->esCancelable())
                    <form method="POST" action="{{ route('inscripciones.destroy', $i) }}" onsubmit="return confirm('¿Cancelar tu inscripción? Se liberará tu lugar.')">
                        @csrf @method('DELETE')
                        <button class="btn-secundario w-full sm:w-auto">Cancelar</button>
                    </form>
                @else
                    <span class="text-xs text-gray-500 sm:w-36 sm:text-right">No cancelable: la actividad ya inició o faltan menos de 24 h</span>
                @endif
            </article>
        @empty
            <div class="tarjeta p-10 text-center text-gray-600">No tienes inscripciones activas.
                <a href="{{ route('actividades.index') }}" class="mt-3 block font-semibold text-guinda-700 hover:underline">Explorar el catálogo</a></div>
        @endforelse
    </div>
</section>

@if ($historial->isNotEmpty())
<section class="mt-10" aria-labelledby="historial">
    <h2 id="historial" class="text-lg font-semibold">Historial</h2>
    <div class="tarjeta mt-3 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="px-4 py-3 font-medium">Actividad</th><th class="px-4 py-3 font-medium">Estado</th><th class="px-4 py-3 font-medium">Fecha</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            @foreach ($historial as $i)
                <tr><td class="px-4 py-3">{{ $i->actividad->titulo }}</td>
                    <td class="px-4 py-3"><span class="chip bg-gray-100 text-gray-700">{{ ucfirst(strtolower($i->estado->value)) }}</span></td>
                    <td class="px-4 py-3 text-gray-600">{{ ($i->fecha_cancelacion ?? $i->fecha_inscripcion)->format('d/m/Y') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
