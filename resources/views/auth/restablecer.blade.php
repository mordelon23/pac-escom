@extends('layouts.invitado')
@section('titulo', 'Nueva contraseña')
@section('contenido')
<h1 class="text-2xl font-bold">Define tu nueva contraseña</h1>
<form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="correo" class="etiqueta">Correo institucional</label>
        <input id="correo" name="correo" type="email" class="campo" value="{{ old('correo', $correo) }}" required>
        @error('correo')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="etiqueta">Nueva contraseña</label>
        <input id="password" name="password" type="password" class="campo" autocomplete="new-password" required>
        @error('password')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="etiqueta">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="campo" autocomplete="new-password" required>
    </div>
    <button class="btn-primario w-full">Guardar contraseña</button>
</form>
@endsection
