@extends('layouts.invitado')
@section('titulo', 'Registro de alumno')
@section('contenido')
<h2 class="text-2xl font-bold">Crear cuenta de alumno</h2>
<p class="mt-1 text-sm text-gray-600">Usa tu boleta y tu correo <strong>@alumno.ipn.mx</strong>.</p>

<form method="POST" action="{{ url('/registro') }}" class="mt-8 space-y-5" novalidate>
    @csrf
    <div>
        <label for="matricula" class="etiqueta">Boleta</label>
        <input id="matricula" name="matricula" class="campo" inputmode="numeric" maxlength="10" pattern="\d{10}"
               value="{{ old('matricula') }}" placeholder="2023630101" required>
        <p class="mt-1 text-xs text-gray-500">10 dígitos, sin espacios.</p>
        @error('matricula')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="nombre" class="etiqueta">Nombre(s)</label>
            <input id="nombre" name="nombre" class="campo" value="{{ old('nombre') }}" autocomplete="given-name" required>
            @error('nombre')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="apellidos" class="etiqueta">Apellidos</label>
            <input id="apellidos" name="apellidos" class="campo" value="{{ old('apellidos') }}" autocomplete="family-name" required>
            @error('apellidos')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="correo" class="etiqueta">Correo institucional</label>
        <input id="correo" name="correo" type="email" class="campo" value="{{ old('correo') }}" placeholder="usuario@alumno.ipn.mx" autocomplete="email" required>
        @error('correo')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="etiqueta">Contraseña</label>
        <input id="password" name="password" type="password" class="campo" autocomplete="new-password" required>
        <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres, con mayúsculas, minúsculas y al menos un número.</p>
        @error('password')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="etiqueta">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="campo" autocomplete="new-password" required>
    </div>
    <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" name="aviso_privacidad" value="1" class="mt-0.5 h-4 w-4 accent-guinda-700" @checked(old('aviso_privacidad'))>
        <span>He leído y acepto el aviso de privacidad. Mis datos se usarán solo para la gestión de actividades culturales.</span>
    </label>
    @error('aviso_privacidad')<p class="error">{{ $message }}</p>@enderror
    <button type="submit" class="btn-primario w-full">Crear cuenta</button>
</form>
<p class="mt-8 text-center text-sm text-gray-600">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-guinda-700 hover:underline">Inicia sesión</a></p>
@endsection
