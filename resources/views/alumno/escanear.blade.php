@extends('layouts.app', ['titulo' => 'Registrar asistencia'])
@section('contenido')
<div class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold">Registrar asistencia</h1>

    @if ($sesion && $token)
        <div class="tarjeta mt-6 p-6 text-center">
            <p class="text-gray-700">Leíste un código de asistencia. Confirma para registrarte.</p>
            <form method="POST" action="{{ route('asistencia.store') }}" class="mt-5">
                @csrf
                <input type="hidden" name="sesion" value="{{ $sesion }}"><input type="hidden" name="token" value="{{ $token }}">
                <button class="btn-primario w-full py-3 text-base">Registrar mi asistencia</button>
            </form>
            <p class="mt-3 text-xs text-gray-500">El código es válido por menos de un minuto.</p>
        </div>
    @else
        <p class="mt-1 text-gray-600">Apunta la cámara al código QR que muestra el responsable.</p>
        <div data-escaner data-endpoint="{{ route('asistencia.store') }}" class="tarjeta mt-6 p-5">
            <div id="lector-qr" class="overflow-hidden rounded-lg bg-gray-900/5"></div>
            <button type="button" data-iniciar class="btn-primario mt-4 w-full py-3 text-base">Abrir cámara</button>
            <div data-resultado hidden role="status" aria-live="polite"></div>
        </div>
        <p class="mt-4 text-sm text-gray-600">¿Sin cámara o sin conexión? Avisa al responsable para que registre tu asistencia en modo contingencia.</p>
    @endif
</div>
@endsection
