@extends('layouts.app', ['titulo' => 'Catálogo'])
@section('contenido')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">Catálogo de actividades</h1>
        <p class="mt-1 text-gray-600">{{ $actividades->total() }} actividades disponibles</p>
    </div>
</div>

<form method="GET" class="tarjeta mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end" role="search">
    <div class="sm:col-span-2">
        <label for="q" class="etiqueta">Buscar</label>
        <input id="q" name="q" type="search" class="campo" value="{{ $filtros['q'] ?? '' }}" placeholder="Título, lugar o palabra clave">
    </div>
    <div>
        <label for="categoria" class="etiqueta">Categoría</label>
        <select id="categoria" name="categoria" class="campo">
            <option value="">Todas</option>
            @foreach ($categorias as $c)
                <option value="{{ $c->id }}" @selected(($filtros['categoria'] ?? null) == $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="lugar" class="etiqueta">Lugar</label>
        <select id="lugar" name="lugar" class="campo">
            <option value="">Todos</option>
            @foreach ($lugares as $l)
                <option value="{{ $l }}" @selected(($filtros['lugar'] ?? null) === $l)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="dia" class="etiqueta">Día</label>
        <select id="dia" name="dia" class="campo">
            <option value="">Cualquiera</option>
            @foreach ($dias as $d)
                <option value="{{ $d->value }}" @selected(($filtros['dia'] ?? null) == $d->value)>{{ $d->etiqueta() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="fecha" class="etiqueta">Fecha</label>
        <input id="fecha" name="fecha" type="date" class="campo" value="{{ $filtros['fecha'] ?? '' }}" min="{{ today()->toDateString() }}">
    </div>
    <div class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-6">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="disponibles" value="1" class="h-4 w-4 accent-guinda-700" @checked($filtros['disponibles'] ?? false)> Solo con lugares
        </label>
        <div class="ml-auto flex gap-2">
            @if (array_filter($filtros))<a href="{{ route('actividades.index') }}" class="btn-secundario">Limpiar</a>@endif
            <button class="btn-primario">Filtrar</button>
        </div>
    </div>
</form>

<h2 class="sr-only">Actividades encontradas</h2>
<div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($actividades as $actividad)
        @include('partials.tarjeta-actividad', ['actividad' => $actividad, 'inscrito' => in_array($actividad->id, $inscritas)])
    @empty
        <div class="tarjeta col-span-full p-10 text-center text-gray-600">No se encontraron actividades con los filtros seleccionados.</div>
    @endforelse
</div>
<div class="mt-8">{{ $actividades->links() }}</div>
@endsection
