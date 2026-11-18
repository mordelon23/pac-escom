@extends('layouts.invitado')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<h1 class="text-2xl font-bold">Iniciar sesión</h1>
<p class="mt-1 text-sm text-gray-600">Ingresa con tu correo institucional.</p>

<form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5" novalidate>
    @csrf
    <div>
        <label for="correo" class="etiqueta">Correo institucional</label>
        <input id="correo" name="correo" type="email" class="campo" value="{{ old('correo') }}" autocomplete="username"
               placeholder="usuario@alumno.ipn.mx" required autofocus @error('correo') aria-invalid="true" aria-describedby="correo-error" @enderror>
        @error('correo')<p id="correo-error" class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="etiqueta">Contraseña</label>
            <a href="{{ route('password.request') }}" @class(['text-sm font-medium hover:underline',
               'text-guinda-700' => ! session('sugerir_restablecer'),
               'rounded bg-guinda-100 px-2 py-0.5 font-semibold text-guinda-800' => session('sugerir_restablecer')])>¿Olvidaste tu contraseña?</a>
        </div>
        <input id="password" name="password" type="password" class="campo" autocomplete="current-password" required>
    </div>
    @if (session('sugerir_restablecer'))
        <p class="rounded-lg bg-guinda-50 px-3 py-2 text-sm text-guinda-900">Llevas varios intentos fallidos. Si no recuerdas tu contraseña, puedes restablecerla.</p>
    @endif
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="recordar" class="h-4 w-4 rounded border-gray-300 accent-guinda-700"> Mantener la sesión iniciada
    </label>
    <button type="submit" class="btn-primario w-full">Entrar</button>
</form>

<p class="mt-8 text-center text-sm text-gray-600">¿Eres alumno y no tienes cuenta?
    <a href="{{ route('registro') }}" class="font-semibold text-guinda-700 hover:underline">Regístrate</a></p>

@if (app()->environment('local') || config('pac.datos_demo'))
<details class="mt-8 rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-600">
    <summary class="cursor-pointer font-medium text-gray-800">Cuentas de demostración</summary>
    <p class="mt-2">Contraseña de todas: <code class="rounded bg-gray-100 px-1">Pac2026demo</code></p>
    <ul class="mt-2 space-y-1">
        <li>Alumno: <code>alumno0@alumno.ipn.mx</code></li>
        <li>Responsable: <code>chernandez@ipn.mx</code></li>
        <li>Coordinadora: <code>coordinacion@ipn.mx</code></li>
    </ul>
</details>
@endif
@endsection
