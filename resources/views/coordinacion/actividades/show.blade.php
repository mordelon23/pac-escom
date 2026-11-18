@extends('layouts.app', ['titulo' => $actividad->titulo])
@section('contenido')
<a href="{{ route('coordinacion.actividades.index') }}" class="text-sm font-medium text-guinda-700 hover:underline">← Actividades</a>
<div class="mt-2 flex flex-wrap items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">@include('partials.estado-actividad', ['estado' => $actividad->estado])
            <span class="chip bg-guinda-100 text-guinda-800">{{ $actividad->categoria->nombre }}</span></div>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $actividad->titulo }}</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        @if (in_array($actividad->estado->value, ['BORRADOR', 'PUBLICADA']))
            <a href="{{ route('coordinacion.actividades.edit', $actividad) }}" class="btn-secundario">Editar</a>
        @endif
        @if ($actividad->estado->value === 'BORRADOR')
            <form method="POST" action="{{ route('coordinacion.actividades.publicar', $actividad) }}">@csrf @method('PATCH')
                <button class="btn-primario">Publicar</button></form>
            <form method="POST" action="{{ route('coordinacion.actividades.destroy', $actividad) }}" onsubmit="return confirm('¿Eliminar este borrador?')">@csrf @method('DELETE')
                <button class="btn-secundario text-red-700">Eliminar</button></form>
        @endif
        @if ($actividad->estado->value === 'PUBLICADA')
            <button type="button" class="btn-peligro" onclick="document.getElementById('confirmar-cancelacion').showModal()">Cancelar actividad</button>
        @endif
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
    <div class="space-y-6">
        <section class="tarjeta p-5">
            <h2 class="font-semibold">Inscritos ({{ $actividad->inscripcionesActivas->count() }}/{{ $actividad->cupo_maximo }})</h2>
            <p class="text-xs text-gray-500">Visible solo para coordinación y el responsable asignado (RB17).</p>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500"><tr><th class="py-2 font-medium">Boleta</th><th class="py-2 font-medium">Nombre</th><th class="py-2 font-medium">Inscripción</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse ($actividad->inscripcionesActivas->sortBy('alumno.apellidos') as $i)
                        <tr><td class="py-2 font-mono">{{ $i->alumno->matricula }}</td><td class="py-2">{{ $i->alumno->apellidos }}, {{ $i->alumno->nombre }}</td>
                            <td class="py-2 text-gray-600">{{ $i->fecha_inscripcion->format('d/m/Y H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-600">Sin inscritos todavía.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="tarjeta p-5">
            <h2 class="font-semibold">Últimas sesiones de asistencia</h2>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                @forelse ($sesiones as $s)
                    <li class="flex justify-between py-2"><span>{{ $s->fecha->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
                        <span><strong>{{ $s->asistencias_count }}</strong> asistentes · {{ ucfirst(strtolower($s->estado->value)) }}</span></li>
                @empty
                    <li class="py-4 text-center text-gray-600">Aún no se han abierto sesiones.</li>
                @endforelse
            </ul>
        </section>
    </div>
    <aside class="tarjeta h-fit p-5">
        <img src="{{ $actividad->urlImagen() }}" alt="" class="aspect-[16/9] w-full rounded-lg object-cover">
        <dl class="mt-4 space-y-3 text-sm">
            <div><dt class="text-gray-500">Responsable</dt><dd class="font-medium">{{ $actividad->responsable->nombreCompleto() }}</dd></div>
            <div><dt class="text-gray-500">Lugar</dt><dd class="font-medium">{{ $actividad->lugar }}</dd></div>
            <div><dt class="text-gray-500">Periodo</dt><dd class="font-medium">{{ $actividad->fecha_inicio->format('d/m/Y') }} al {{ $actividad->fecha_fin->format('d/m/Y') }}</dd></div>
            <div><dt class="text-gray-500">Horario</dt>
                <dd>@foreach ($actividad->horarios as $h)<div class="font-medium">{{ $h->dia_semana->etiqueta() }} {{ $h->rango() }}</div>@endforeach</dd></div>
        </dl>
    </aside>
</div>

<dialog id="confirmar-cancelacion" class="m-auto w-[min(92vw,28rem)] rounded-xl p-0 shadow-xl backdrop:bg-black/40">
    <form method="POST" action="{{ route('coordinacion.actividades.cancelar', $actividad) }}" class="p-6">
        @csrf @method('PATCH')
        <h2 class="text-lg font-semibold">¿Cancelar «{{ $actividad->titulo }}»?</h2>
        <p class="mt-2 text-sm text-gray-600">Se cancelarán las {{ $actividad->inscripcionesActivas->count() }} inscripciones activas y se notificará por correo a cada alumno (RB8). Esta acción no se puede deshacer.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn-secundario" onclick="this.closest('dialog').close()">Volver</button>
            <button class="btn-peligro">Sí, cancelar actividad</button>
        </div>
    </form>
</dialog>
@endsection
