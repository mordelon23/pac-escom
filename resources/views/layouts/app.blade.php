<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo ?? 'Inicio' }} · PAC ESCOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
@php
    $u = auth()->user();
    $nav = match (true) {
        $u->esAlumno() => [
            ['alumno.inicio', 'Inicio'], ['actividades.index', 'Catálogo'], ['inscripciones.index', 'Mis actividades'],
        ],
        $u->esCoordinador() => [['coordinacion.inicio', 'Coordinación'], ['responsable.inicio', 'Sesiones']],
        default => [['responsable.inicio', 'Mis grupos']],
    };
@endphp
<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Saltar al contenido</a>

<header class="sticky top-0 z-40 bg-guinda-700 text-white shadow">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 font-bold tracking-tight">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/15 text-sm">PAC</span>
            <span class="hidden sm:inline">Actividades Culturales</span>
        </a>
        <nav class="ml-4 hidden gap-1 md:flex" aria-label="Principal">
            @foreach ($nav as [$ruta, $texto])
                <a href="{{ route($ruta) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium hover:bg-white/10',
                    'bg-white/15' => request()->routeIs($ruta . '*')]) @if(request()->routeIs($ruta)) aria-current="page" @endif>{{ $texto }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-3">
            <div class="hidden text-right text-xs leading-tight sm:block">
                <div class="font-semibold">{{ $u->nombreCompleto() }}</div>
                <div class="text-white/75">{{ $u->rolSistema()->etiqueta() }}</div>
            </div>
            <span class="grid h-9 w-9 place-items-center rounded-full bg-white/20 text-sm font-bold" aria-hidden="true">{{ $u->iniciales() }}</span>
            <button type="button" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-white/10"
                    onclick="document.getElementById('confirmar-salida').showModal()">Salir</button>
        </div>
    </div>
    {{-- Navegación móvil --}}
    <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-2 py-1.5 md:hidden" aria-label="Principal móvil">
        @foreach ($nav as [$ruta, $texto])
            <a href="{{ route($ruta) }}" @class(['shrink-0 rounded-lg px-3 py-1.5 text-sm', 'bg-white/15 font-semibold' => request()->routeIs($ruta . '*')])>{{ $texto }}</a>
        @endforeach
    </nav>
</header>

{{-- RB14: confirmar antes de cerrar sesión --}}
<dialog id="confirmar-salida" class="m-auto w-[min(92vw,24rem)] rounded-xl p-0 shadow-xl backdrop:bg-black/40">
    <form method="POST" action="{{ route('logout') }}" class="p-6">
        @csrf
        <h2 class="text-lg font-semibold">¿Cerrar sesión?</h2>
        <p class="mt-2 text-sm text-gray-600">Tendrás que volver a ingresar tu correo y contraseña.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn-secundario" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="btn-primario">Cerrar sesión</button>
        </div>
    </form>
</dialog>

<main id="contenido" class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
    @include('partials.flash')
    {{ $slot ?? '' }}
    @yield('contenido')
</main>

<footer class="mx-auto max-w-6xl px-4 pb-8 pt-4 text-xs text-gray-500">
    Portal de Actividades Culturales · Escuela Superior de Cómputo · IPN
</footer>
</body>
</html>
