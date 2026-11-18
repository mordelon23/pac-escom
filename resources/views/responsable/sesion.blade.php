@extends('layouts.app', ['titulo' => 'Sesión de asistencia'])
@section('contenido')
@php($estado = $sesion->estado->value)
<a href="{{ route('responsable.inicio') }}" class="text-sm font-medium text-guinda-700 hover:underline">← Mis grupos</a>
<div class="mt-2 flex flex-wrap items-start justify-between gap-4">
    <div>
        <span @class(['chip', 'bg-green-100 text-green-800' => $estado === 'ABIERTA', 'bg-amber-100 text-amber-900' => $estado === 'CONTINGENCIA', 'bg-gray-200 text-gray-700' => $estado === 'CERRADA'])>
            {{ ['ABIERTA' => 'Registro con QR', 'CONTINGENCIA' => 'Modo contingencia', 'CERRADA' => 'Pase de lista finalizado'][$estado] }}</span>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $actividad->titulo }}</h1>
        <p class="text-gray-600">{{ $sesion->fecha->locale('es')->isoFormat('dddd D [de] MMMM') }} · {{ $sesion->horario->rango() }} · {{ $actividad->lugar }}</p>
    </div>
    @if ($estado !== 'CERRADA')
        <div class="flex flex-wrap gap-2">
            @if ($estado === 'ABIERTA')
                <form method="POST" action="{{ route('responsable.sesiones.contingencia', $sesion) }}">@csrf @method('PATCH')
                    <button class="btn-secundario">Activar modo contingencia</button></form>
            @else
                <form method="POST" action="{{ route('responsable.sesiones.reanudar', $sesion) }}">@csrf @method('PATCH')
                    <button class="btn-secundario">Reanudar con QR</button></form>
            @endif
            <form method="POST" action="{{ route('responsable.sesiones.cerrar', $sesion) }}" data-confirmar="¿Finalizar el pase de lista? Ya no se podrán registrar asistencias.">@csrf @method('PATCH')
                <button class="btn-primario">Finalizar pase de lista</button></form>
        </div>
    @endif
</div>

@if ($estado === 'ABIERTA')
    <div data-sesion-qr data-endpoint="{{ route('responsable.sesiones.estado', $sesion) }}" class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
        <section class="tarjeta flex flex-col items-center p-6 text-center">
            <p class="text-sm font-medium text-gray-600">Escanea con la cámara de tu teléfono o desde «Registrar asistencia» en el PAC</p>
            <canvas data-qr class="mt-4 max-w-full rounded-lg" aria-label="Código QR de asistencia" role="img"></canvas>
            <div class="mt-4 w-full max-w-sm">
                <div class="flex justify-between text-sm text-gray-600"><span>El código cambia en</span><span><strong data-cuenta>30</strong> s</span></div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100"><div data-barra class="h-full rounded-full bg-guinda-600 transition-all duration-1000" style="width:100%"></div></div>
            </div>
            <p data-aviso hidden class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">No hay conexión con el servidor. Si persiste, activa el modo contingencia.</p>
        </section>
        <aside class="tarjeta h-fit p-5">
            <h2 class="text-sm font-medium text-gray-600">Asistentes registrados</h2>
            <p class="mt-1 text-4xl font-bold text-guinda-800" data-asistentes aria-live="polite">{{ $sesion->asistencias->count() }}</p>
            <h3 class="mt-5 text-xs font-semibold uppercase tracking-wide text-gray-500">Últimos registros</h3>
            <ul data-ultimos class="mt-2 divide-y divide-gray-100 text-sm"></ul>
        </aside>
    </div>
@elseif ($estado === 'CONTINGENCIA')
    <section class="tarjeta mt-6 p-5" aria-labelledby="t-cont">
        <h2 id="t-cont" class="font-semibold">Registro manual ({{ $pendientes->count() }} alumnos sin registro)</h2>
        <p class="text-sm text-gray-600">Cada registro manual exige un motivo y queda en la bitácora de contingencias (RB23).</p>
        <ul class="mt-4 divide-y divide-gray-100">
            @forelse ($pendientes as $alumno)
                <li class="py-3">
                    <form method="POST" action="{{ route('responsable.sesiones.manual', $sesion) }}" class="grid items-center gap-3 sm:grid-cols-[1fr_14rem_14rem_auto]">
                        @csrf
                        <input type="hidden" name="alumno_id" value="{{ $alumno->id }}">
                        <div><div class="font-medium">{{ $alumno->apellidos }}, {{ $alumno->nombre }}</div><div class="font-mono text-xs text-gray-500">{{ $alumno->matricula }}</div></div>
                        <label><span class="sr-only">Motivo para {{ $alumno->nombre }}</span>
                            <select name="motivo" class="campo mt-0" required>
                                @foreach ($motivos as $m)<option value="{{ $m->value }}">{{ $m->etiqueta() }}</option>@endforeach
                            </select></label>
                        <label><span class="sr-only">Detalle</span><input name="detalle" class="campo mt-0" placeholder="Detalle (si elige «Otro»)" maxlength="255"></label>
                        <button class="btn-primario">Registrar</button>
                    </form>
                </li>
            @empty
                <li class="py-6 text-center text-gray-600">Todos los inscritos ya tienen asistencia registrada.</li>
            @endforelse
        </ul>
        @error('detalle')<p class="error">{{ $message }}</p>@enderror
    </section>
@endif

<section class="tarjeta mt-6 p-5" aria-labelledby="t-lista">
    <h2 id="t-lista" class="font-semibold">{{ $estado === 'CERRADA' ? 'Resumen de asistencia' : 'Asistencias registradas' }}
        <span class="font-normal text-gray-600">· {{ $sesion->asistencias->count() }} de {{ $actividad->inscripcionesActivas->count() }} inscritos</span></h2>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-gray-500"><tr><th class="py-2 font-medium">Boleta</th><th class="py-2 font-medium">Alumno</th><th class="py-2 font-medium">Hora</th><th class="py-2 font-medium">Método</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($sesion->asistencias->sortBy('registrada_en') as $a)
                <tr><td class="py-2 font-mono">{{ $a->alumno->matricula }}</td><td class="py-2">{{ $a->alumno->nombreCompleto() }}</td>
                    <td class="py-2">{{ $a->registrada_en->format('H:i:s') }}</td>
                    <td class="py-2"><span @class(['chip', 'bg-green-100 text-green-800' => $a->metodo->value === 'QR', 'bg-amber-100 text-amber-900' => $a->metodo->value === 'MANUAL'])>{{ $a->metodo->value === 'QR' ? 'QR' : 'Manual' }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-600">Sin registros todavía.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
