@extends('layouts.invitado')
@section('titulo', 'Recuperar contraseña')
@section('contenido')
<h2 class="text-2xl font-bold">Recuperar contraseña</h2>
<p class="mt-1 text-sm text-gray-600">Te enviaremos un enlace válido durante 10 minutos.</p>
<form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
    @csrf
    <div>
        <label for="correo" class="etiqueta">Correo institucional</label>
        <input id="correo" name="correo" type="email" class="campo" value="{{ old('correo') }}" required autofocus>
        @error('correo')<p class="error">{{ $message }}</p>@enderror
    </div>
    <button class="btn-primario w-full">Enviar enlace</button>
</form>
<p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-guinda-700 hover:underline">Volver a iniciar sesión</a></p>
@endsection
