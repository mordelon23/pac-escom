@extends('layouts.app', ['titulo' => 'Muro social'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Muro social</h1>
<p class="mt-1 text-gray-600">Comparte tu experiencia en las actividades.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
    <section aria-labelledby="t-pub" class="space-y-4 lg:order-1">
        <h2 id="t-pub" class="sr-only">Publicaciones</h2>
        @forelse ($publicaciones as $p)
            <article class="tarjeta p-5">
                <header class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-guinda-100 text-sm font-bold text-guinda-800">{{ $p->autor->iniciales() }}</span>
                    <div><div class="font-semibold">{{ $p->autor->nombreCompleto() }}</div>
                        <div class="text-xs text-gray-500">{{ $p->actividad->titulo }} · {{ $p->created_at->diffForHumans() }}</div></div>
                </header>
                <p class="mt-3 whitespace-pre-line text-gray-800">{{ $p->contenido }}</p>
                @if ($p->imagen_ruta)<img src="{{ asset('storage/' . $p->imagen_ruta) }}" alt="Foto compartida por {{ $p->autor->nombre }}" class="mt-3 max-h-96 w-full rounded-lg object-cover" loading="lazy">@endif
            </article>
        @empty
            <div class="tarjeta p-10 text-center text-gray-600">Todavía no hay publicaciones. ¡Sé el primero en compartir!</div>
        @endforelse
        {{ $publicaciones->links() }}
    </section>

    <aside class="space-y-4 lg:order-2">
        <form method="POST" action="{{ route('muro.store') }}" enctype="multipart/form-data" class="tarjeta space-y-4 p-5">
            @csrf
            <h2 class="font-semibold">Nueva publicación</h2>
            @if ($actividades->isEmpty())
                <p class="text-sm text-gray-600">Podrás publicar cuando te inscribas a alguna actividad.</p>
            @else
                <div><label for="actividad_id" class="etiqueta">Actividad</label>
                    <select id="actividad_id" name="actividad_id" class="campo" required>
                        @foreach ($actividades as $a)<option value="{{ $a->id }}" @selected(old('actividad_id') == $a->id)>{{ $a->titulo }}</option>@endforeach
                    </select></div>
                <div><label for="comentario" class="etiqueta">Comentario</label>
                    <textarea id="comentario" name="contenido" rows="4" maxlength="500" class="campo" required>{{ old('contenido') }}</textarea>
                    @error('contenido')<p class="error">{{ $message }}</p>@enderror</div>
                <div><label for="foto" class="etiqueta">Fotografía (opcional)</label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-guinda-100 file:px-3 file:py-2 file:font-semibold file:text-guinda-800">
                    @error('foto')<p class="error">{{ $message }}</p>@enderror</div>
                <button class="btn-primario w-full">Publicar</button>
                <p class="text-xs text-gray-500">La coordinación revisa cada publicación antes de mostrarla (RB16).</p>
            @endif
        </form>
        @if ($mias->isNotEmpty())
            <section class="tarjeta p-5">
                <h2 class="font-semibold">Mis publicaciones en revisión</h2>
                <ul class="mt-3 space-y-3 text-sm">
                    @foreach ($mias as $p)
                        <li class="border-l-4 pl-3 {{ $p->estado_moderacion->value === 'RECHAZADA' ? 'border-red-400' : 'border-amber-400' }}">
                            <div class="line-clamp-2">{{ $p->contenido }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $p->actividad->titulo }} ·
                                {{ $p->estado_moderacion->value === 'RECHAZADA' ? 'Rechazada: ' . $p->motivo_rechazo : 'Pendiente de revisión' }}</div>
                            <form method="POST" action="{{ route('muro.destroy', $p) }}" class="mt-1">@csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-700 hover:underline">Eliminar</button></form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </aside>
</div>
@endsection
