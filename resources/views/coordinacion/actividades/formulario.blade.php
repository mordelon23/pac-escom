@extends('layouts.app', ['titulo' => $actividad->exists ? 'Editar actividad' : 'Nueva actividad'])
@section('contenido')
<a href="{{ $actividad->exists ? route('coordinacion.actividades.show', $actividad) : route('coordinacion.actividades.index') }}" class="text-sm font-medium text-guinda-700 hover:underline">← Volver</a>
<h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $actividad->exists ? 'Editar actividad' : 'Nueva actividad' }}</h1>
@if ($errors->any())
    <div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">Revise los campos marcados.</div>
@endif

<form method="POST" enctype="multipart/form-data" class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]"
      action="{{ $actividad->exists ? route('coordinacion.actividades.update', $actividad) : route('coordinacion.actividades.store') }}">
    @csrf
    @if ($actividad->exists) @method('PUT') @endif

    <div class="space-y-6">
        <section class="tarjeta space-y-5 p-5">
            <h2 class="font-semibold">Datos generales</h2>
            <div>
                <label for="titulo" class="etiqueta">Título</label>
                <input id="titulo" name="titulo" class="campo" value="{{ old('titulo', $actividad->titulo) }}" maxlength="120" required>
                @error('titulo')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="descripcion" class="etiqueta">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="5" class="campo" maxlength="2000" required>{{ old('descripcion', $actividad->descripcion) }}</textarea>
                @error('descripcion')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="categoria_id" class="etiqueta">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="campo" required>
                        <option value="">Seleccione…</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected(old('categoria_id', $actividad->categoria_id) == $c->id)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="responsable_id" class="etiqueta">Responsable de grupo</label>
                    <select id="responsable_id" name="responsable_id" class="campo" required>
                        <option value="">Seleccione…</option>
                        @foreach ($responsables as $r)
                            <option value="{{ $r->id }}" @selected(old('responsable_id', $actividad->responsable_id) == $r->id)>{{ $r->nombreCompleto() }}</option>
                        @endforeach
                    </select>
                    @error('responsable_id')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label for="lugar" class="etiqueta">Lugar</label>
                    <input id="lugar" name="lugar" class="campo" value="{{ old('lugar', $actividad->lugar) }}" required>
                    @error('lugar')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="fecha_inicio" class="etiqueta">Inicio del periodo</label>
                    <input id="fecha_inicio" name="fecha_inicio" type="date" class="campo" value="{{ old('fecha_inicio', $actividad->fecha_inicio?->toDateString()) }}" required>
                    @error('fecha_inicio')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="fecha_fin" class="etiqueta">Fin del periodo</label>
                    <input id="fecha_fin" name="fecha_fin" type="date" class="campo" value="{{ old('fecha_fin', $actividad->fecha_fin?->toDateString()) }}" required>
                    @error('fecha_fin')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cupo_maximo" class="etiqueta">Cupo máximo</label>
                    <input id="cupo_maximo" name="cupo_maximo" type="number" min="1" max="500" class="campo" value="{{ old('cupo_maximo', $actividad->cupo_maximo) }}" required>
                    @error('cupo_maximo')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="tarjeta p-5" aria-labelledby="t-hor">
            <h2 id="t-hor" class="font-semibold">Horario semanal</h2>
            <p class="mt-1 text-sm text-gray-600">Marque los días en que se imparte la actividad (solo de lunes a viernes).</p>
            @error('dias')<p class="error">{{ $message }}</p>@enderror
            <div class="mt-4 divide-y divide-gray-100">
                @foreach ($dias as $d)
                    @php($h = $horarios[$d->value] ?? null)
                    @php($activo = old("dias.{$d->value}.activo", $h ? 1 : 0))
                    <div class="grid grid-cols-[8rem_1fr_1fr] items-center gap-3 py-3">
                        <label class="flex items-center gap-2 font-medium">
                            <input type="checkbox" name="dias[{{ $d->value }}][activo]" value="1" class="h-4 w-4 accent-guinda-700" @checked($activo)
                                   onchange="this.closest('div').querySelectorAll('input[type=time]').forEach(i => i.disabled = ! this.checked)">
                            {{ $d->etiqueta() }}
                        </label>
                        <label class="text-sm"><span class="sr-only">Hora de inicio del {{ $d->etiqueta() }}</span>
                            <input type="time" name="dias[{{ $d->value }}][inicio]" class="campo mt-0" value="{{ old("dias.{$d->value}.inicio", $h ? substr($h->hora_inicio, 0, 5) : '14:00') }}" @disabled(! $activo)></label>
                        <label class="text-sm"><span class="sr-only">Hora de fin del {{ $d->etiqueta() }}</span>
                            <input type="time" name="dias[{{ $d->value }}][fin]" class="campo mt-0" value="{{ old("dias.{$d->value}.fin", $h ? substr($h->hora_fin, 0, 5) : '16:00') }}" @disabled(! $activo)></label>
                        @error("dias.{$d->value}")<p class="error col-span-3">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="tarjeta p-5">
            <h2 class="font-semibold">Imagen representativa</h2>
            @if ($actividad->exists)
                <img src="{{ $actividad->urlImagen() }}" alt="Imagen actual" class="mt-3 aspect-[16/9] w-full rounded-lg object-cover">
            @endif
            <label for="imagen" class="etiqueta mt-3">{{ $actividad->exists ? 'Reemplazar imagen (opcional)' : 'Imagen' }}</label>
            <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-guinda-100 file:px-3 file:py-2 file:font-semibold file:text-guinda-800">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG o WebP, máximo 2 MB (RB11).</p>
            @error('imagen')<p class="error">{{ $message }}</p>@enderror
        </section>
        <div class="tarjeta p-5">
            <button class="btn-primario w-full">{{ $actividad->exists ? 'Guardar cambios' : 'Guardar como borrador' }}</button>
            <p class="mt-2 text-xs text-gray-500">{{ $actividad->exists ? 'Los cambios se registran en la auditoría.' : 'La actividad no será visible para los alumnos hasta que la publiques (RB10).' }}</p>
        </div>
    </aside>
</form>
@endsection
