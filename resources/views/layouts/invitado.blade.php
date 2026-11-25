<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Iniciar sesión') · PAC ESCOM</title>
    <meta name="theme-color" content="#6C1D45">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">
    <aside class="relative hidden overflow-hidden bg-guinda-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-32 -left-20 h-[28rem] w-[28rem] rounded-full bg-white/5"></div>
        <div class="relative flex items-center gap-3 text-lg font-bold">
            <span class="grid h-10 w-10 place-items-center rounded-lg bg-white/15">PAC</span> ESCOM · IPN
        </div>
        <div class="relative">
            <p class="text-4xl font-bold leading-tight">Portal de<br>Actividades Culturales</p>
            <p class="mt-4 max-w-md text-white/80">Consulta talleres, conciertos, torneos y exposiciones; inscríbete en línea y registra tu asistencia con tu teléfono.</p>
        </div>
        <p class="relative text-sm text-white/60">Escuela Superior de Cómputo</p>
    </aside>
    <main class="flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-2 font-bold text-guinda-700 lg:hidden">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-guinda-700 text-sm text-white">PAC</span> Actividades Culturales
            </div>
            @include('partials.flash')
            @yield('contenido')
            <p class="mt-8 text-center text-xs text-gray-500"><a href="{{ route('aviso-privacidad') }}" class="underline hover:text-guinda-700">Aviso de privacidad</a></p>
        </div>
    </main>
</div>
</body>
</html>
