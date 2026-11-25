@extends('layouts.app', ['titulo' => 'Mi cuenta'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Mi cuenta</h1>
<p class="mt-1 text-gray-600">Datos personales registrados en el portal y seguridad de tu acceso.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="tarjeta p-6" aria-labelledby="t-datos">
        <h2 id="t-datos" class="font-semibold">Mis datos</h2>
        <dl class="mt-4 divide-y divide-gray-100 text-sm">
            @foreach (array_filter([
                'Nombre' => $u->nombreCompleto(),
                'Correo' => $u->correo,
                'Boleta' => $u->matricula,
                'Rol' => $u->rolSistema()->etiqueta(),
                'Cuenta creada' => $u->created_at?->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
                'Aviso de privacidad aceptado' => $u->aviso_privacidad_aceptado_en?->format('d/m/Y H:i'),
            ]) as $etiqueta => $valor)
                <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-600">{{ $etiqueta }}</dt><dd class="text-right font-medium">{{ $valor }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-4 text-xs text-gray-500">Para corregir tus datos o ejercer tus derechos ARCO, consulta el <a href="{{ route('aviso-privacidad') }}" class="underline hover:text-guinda-700">aviso de privacidad</a>.</p>
    </section>

    <section class="tarjeta p-6" aria-labelledby="t-contrasena">
        <h2 id="t-contrasena" class="font-semibold">Cambiar contraseña</h2>
        <form method="POST" action="{{ route('cuenta.contrasena') }}" class="mt-4 space-y-4" novalidate>
            @csrf
            @method('PUT')
            <div>
                <label for="actual" class="etiqueta">Contraseña actual</label>
                <input id="actual" name="actual" type="password" class="campo" autocomplete="current-password" required>
                @error('actual')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="etiqueta">Nueva contraseña</label>
                <input id="password" name="password" type="password" class="campo" autocomplete="new-password" required aria-describedby="ayuda-password">
                <p id="ayuda-password" class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres, con mayúsculas, minúsculas y un número.</p>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="etiqueta">Confirmar nueva contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="campo" autocomplete="new-password" required>
            </div>
            <button class="btn-primario">Actualizar contraseña</button>
        </form>
    </section>
</div>
@endsection
