@extends('layouts.app', ['titulo' => 'Mi agenda cultural'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Mi agenda cultural</h1>
<p class="mt-1 text-gray-600">Actividades que guardaste para no perderlas de vista.</p>
<h2 class="sr-only">Actividades guardadas</h2>
<div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($actividades as $actividad)
        <div class="relative">
            @include('partials.tarjeta-actividad', ['actividad' => $actividad, 'inscrito' => in_array($actividad->id, $inscritas)])
            @if (! $actividad->estaPublicada())<span class="chip absolute left-3 top-3 bg-red-700 text-white">{{ $actividad->estado->etiqueta() }}</span>@endif
        </div>
    @empty
        <div class="tarjeta col-span-full p-10 text-center text-gray-600">Tu agenda está vacía. Usa «Guardar en mi agenda» en cualquier actividad del catálogo.
            <a href="{{ route('actividades.index') }}" class="mt-3 block font-semibold text-guinda-700 hover:underline">Explorar el catálogo</a></div>
    @endforelse
</div>
@endsection
