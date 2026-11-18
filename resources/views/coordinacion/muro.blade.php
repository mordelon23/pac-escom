@extends('layouts.app', ['titulo' => 'Moderación del muro'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Moderación del muro</h1>
<p class="mt-1 text-gray-600">{{ $pendientes->count() }} publicación(es) esperando revisión.</p>
@error('motivo')<p class="error mt-2">{{ $message }}</p>@enderror

<div class="mt-6 space-y-4">
    @forelse ($pendientes as $p)
        <article class="tarjeta grid gap-4 p-5 md:grid-cols-[1fr_18rem]">
            <div>
                <div class="text-sm text-gray-500"><strong class="text-gray-900">{{ $p->autor->nombreCompleto() }}</strong> ({{ $p->autor->matricula }}) sobre <strong class="text-gray-900">{{ $p->actividad->titulo }}</strong> · {{ $p->created_at->diffForHumans() }}</div>
                <p class="mt-2 whitespace-pre-line">{{ $p->contenido }}</p>
                @if ($p->imagen_ruta)<img src="{{ asset('storage/' . $p->imagen_ruta) }}" alt="Foto adjunta" class="mt-3 max-h-60 rounded-lg">@endif
            </div>
            <div class="space-y-3">
                <form method="POST" action="{{ route('coordinacion.muro.aprobar', $p) }}">@csrf @method('PATCH')
                    <button class="btn-primario w-full">Aprobar</button></form>
                <form method="POST" action="{{ route('coordinacion.muro.rechazar', $p) }}" class="space-y-2">@csrf @method('PATCH')
                    <label class="sr-only" for="motivo-{{ $p->id }}">Motivo del rechazo</label>
                    <input id="motivo-{{ $p->id }}" name="motivo" class="campo" placeholder="Motivo del rechazo" required minlength="5" maxlength="255">
                    <button class="btn-secundario w-full text-red-700">Rechazar</button></form>
            </div>
        </article>
    @empty
        <div class="tarjeta p-10 text-center text-gray-600">No hay publicaciones pendientes. ✓</div>
    @endforelse
</div>

@if ($recientes->isNotEmpty())
<section class="mt-10">
    <h2 class="text-lg font-semibold">Moderadas recientemente</h2>
    <ul class="tarjeta mt-3 divide-y divide-gray-100 text-sm">
        @foreach ($recientes as $p)
            <li class="flex items-start justify-between gap-4 p-4"><div><span class="font-medium">{{ $p->autor->nombreCompleto() }}</span> · {{ \Illuminate\Support\Str::limit($p->contenido, 90) }}</div>
                <span @class(['chip shrink-0', 'bg-green-100 text-green-800' => $p->estado_moderacion->value === 'APROBADA', 'bg-red-100 text-red-800' => $p->estado_moderacion->value === 'RECHAZADA'])>{{ ucfirst(strtolower($p->estado_moderacion->value)) }}</span></li>
        @endforeach
    </ul>
</section>
@endif
@endsection
