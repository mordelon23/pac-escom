@extends('layouts.app', ['titulo' => 'Actividades'])
@section('contenido')
<div class="flex flex-wrap items-end justify-between gap-4">
    <h1 class="text-2xl font-bold sm:text-3xl">Actividades</h1>
    <a href="{{ route('coordinacion.actividades.create') }}" class="btn-primario">+ Nueva actividad</a>
</div>

<nav class="mt-6 flex flex-wrap gap-2" aria-label="Filtrar por estado">
    <a href="{{ route('coordinacion.actividades.index') }}" @class(['chip px-3 py-1.5 text-sm', 'bg-guinda-700 text-white' => ! $estado, 'bg-white border border-gray-300 text-gray-700' => $estado])>Todas</a>
    @foreach (\App\Modulos\Actividades\Enums\EstadoActividad::cases() as $e)
        <a href="{{ route('coordinacion.actividades.index', ['estado' => $e->value]) }}" @class(['chip px-3 py-1.5 text-sm', 'bg-guinda-700 text-white' => $estado === $e, 'bg-white border border-gray-300 text-gray-700' => $estado !== $e])>
            {{ $e->etiqueta() }} ({{ $conteos[$e->value] ?? 0 }})</a>
    @endforeach
</nav>

<div class="tarjeta mt-4 overflow-x-auto">
    <table class="w-full min-w-[46rem] text-left text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr><th class="px-4 py-3 font-medium">Actividad</th><th class="px-4 py-3 font-medium">Horario</th><th class="px-4 py-3 font-medium">Responsable</th>
                <th class="px-4 py-3 font-medium">Cupo</th><th class="px-4 py-3 font-medium">Estado</th><th class="px-4 py-3"><span class="sr-only">Acciones</span></th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        @forelse ($actividades as $a)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <a href="{{ route('coordinacion.actividades.show', $a) }}" class="font-semibold hover:text-guinda-700">{{ $a->titulo }}</a>
                    <div class="text-xs text-gray-500">{{ $a->categoria->nombre }} · {{ $a->fecha_inicio->format('d/m/Y') }} – {{ $a->fecha_fin->format('d/m/Y') }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $a->horarios->map(fn ($h) => $h->dia_semana->abreviatura())->implode(', ') }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $a->responsable->nombreCompleto() }}</td>
                <td class="px-4 py-3">{{ $a->cupo_ocupado }}/{{ $a->cupo_maximo }}</td>
                <td class="px-4 py-3">@include('partials.estado-actividad', ['estado' => $a->estado])</td>
                <td class="px-4 py-3 text-right"><a href="{{ route('coordinacion.actividades.show', $a) }}" class="font-medium text-guinda-700 hover:underline">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-600">No hay actividades en este estado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $actividades->links() }}</div>
@endsection
