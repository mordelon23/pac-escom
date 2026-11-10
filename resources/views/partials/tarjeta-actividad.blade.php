{{-- Tarjeta de actividad: $actividad, $inscrito (bool) --}}
@php($pct = $actividad->porcentajeOcupacion())
<article class="tarjeta group flex flex-col overflow-hidden transition hover:shadow-md">
    <a href="{{ route('actividades.show', $actividad) }}" class="block aspect-[16/9] overflow-hidden bg-guinda-100">
        <img src="{{ $actividad->urlImagen() }}" alt="" class="h-full w-full object-cover transition group-hover:scale-[1.02]" loading="lazy">
    </a>
    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-center gap-2">
            <span class="chip bg-guinda-100 text-guinda-800">{{ $actividad->categoria->nombre }}</span>
            @if ($inscrito ?? false)
                <span class="chip bg-green-100 text-green-800">Inscrito</span>
            @elseif ($actividad->cupoLleno())
                <span class="chip bg-red-100 text-red-800">Cupo lleno</span>
            @elseif ($pct >= 90)
                <span class="chip bg-amber-100 text-amber-900">Últimos lugares</span>
            @endif
        </div>
        <h3 class="mt-2 text-lg font-semibold leading-snug">
            <a href="{{ route('actividades.show', $actividad) }}" class="hover:text-guinda-700">{{ $actividad->titulo }}</a>
        </h3>
        <p class="mt-1 text-sm text-gray-600">{{ $actividad->lugar }}</p>
        <p class="mt-2 text-sm text-gray-700">
            {{ $actividad->horarios->map(fn ($h) => $h->dia_semana->abreviatura())->unique()->implode(', ') }}
            · {{ $actividad->horarios->first()?->rango() }}
        </p>
        <div class="mt-auto pt-4">
            <div class="flex justify-between text-xs text-gray-600">
                <span>{{ $actividad->cuposDisponibles() }} lugares disponibles</span><span>{{ $actividad->cupo_ocupado }}/{{ $actividad->cupo_maximo }}</span>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100" role="presentation">
                <div @class(['h-full rounded-full', 'bg-red-600' => $pct >= 100, 'bg-amber-500' => $pct >= 90 && $pct < 100, 'bg-guinda-600' => $pct < 90]) style="width: {{ min(100, $pct) }}%"></div>
            </div>
        </div>
    </div>
</article>
