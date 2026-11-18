@extends('layouts.app', ['titulo' => 'Usuarios y roles'])
@section('contenido')
<h1 class="text-2xl font-bold sm:text-3xl">Usuarios y roles</h1>
<p class="mt-1 text-gray-600">Alta y rotación semestral de coordinadores y responsables de grupo (RB25).</p>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
    <section>
        <form method="GET" class="flex flex-wrap gap-2">
            <label class="sr-only" for="q">Buscar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" class="campo mt-0 max-w-xs" placeholder="Nombre, correo o boleta">
            <select name="rol" class="campo mt-0 w-auto" aria-label="Rol"><option value="">Todos los roles</option>
                @foreach (\App\Modulos\Identidad\Enums\RolSistema::cases() as $r)<option value="{{ $r->value }}" @selected($rol === $r)>{{ $r->etiqueta() }} ({{ $conteos[$r->value] ?? 0 }})</option>@endforeach
            </select>
            <button class="btn-secundario">Filtrar</button>
        </form>
        <div class="tarjeta mt-4 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-gray-50 text-gray-600"><tr><th class="px-4 py-3 font-medium">Usuario</th><th class="px-4 py-3 font-medium">Rol</th><th class="px-4 py-3 font-medium">Estado</th><th class="px-4 py-3 font-medium">Acciones</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                @foreach ($usuarios as $u)
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium">{{ $u->nombreCompleto() }}</div><div class="text-xs text-gray-500">{{ $u->correo }}{{ $u->matricula ? ' · ' . $u->matricula : '' }}</div></td>
                        <td class="px-4 py-3">
                            @if (in_array($u->rol->clave, ['responsable', 'coordinador']) && $u->id !== auth()->id())
                                <form method="POST" action="{{ route('coordinacion.usuarios.update', $u) }}">@csrf @method('PATCH')
                                    <select name="rol" class="campo mt-0 py-1.5 text-sm" onchange="this.form.submit()" aria-label="Rol de {{ $u->nombre }}">
                                        <option value="responsable" @selected($u->rol->clave === 'responsable')>Responsable de grupo</option>
                                        <option value="coordinador" @selected($u->rol->clave === 'coordinador')>Coordinador</option>
                                    </select></form>
                            @else
                                {{ $u->rol->nombre }}
                            @endif
                        </td>
                        <td class="px-4 py-3"><span @class(['chip', 'bg-green-100 text-green-800' => $u->estaActiva(), 'bg-red-100 text-red-800' => ! $u->estaActiva()])>{{ $u->estaActiva() ? 'Activa' : 'Suspendida' }}</span></td>
                        <td class="px-4 py-3">
                            @if ($u->id !== auth()->id() && ! $u->esSuperadministrador())
                                <form method="POST" action="{{ route('coordinacion.usuarios.update', $u) }}"
                                      onsubmit="return confirm('{{ $u->estaActiva() ? '¿Suspender' : '¿Reactivar' }} la cuenta de {{ e($u->nombreCompleto()) }}?')">@csrf @method('PATCH')
                                    <input type="hidden" name="estado" value="{{ $u->estaActiva() ? 'SUSPENDIDA' : 'ACTIVA' }}">
                                    <button class="text-sm font-medium {{ $u->estaActiva() ? 'text-red-700' : 'text-green-700' }} hover:underline">{{ $u->estaActiva() ? 'Suspender' : 'Reactivar' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $usuarios->links() }}</div>
    </section>

    <aside>
        <form method="POST" action="{{ route('coordinacion.usuarios.store') }}" class="tarjeta space-y-4 p-5">
            @csrf
            <h2 class="font-semibold">Dar de alta personal</h2>
            <div><label for="nombre" class="etiqueta">Nombre(s)</label><input id="nombre" name="nombre" class="campo" value="{{ old('nombre') }}" required>@error('nombre')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label for="apellidos" class="etiqueta">Apellidos</label><input id="apellidos" name="apellidos" class="campo" value="{{ old('apellidos') }}" required>@error('apellidos')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label for="correo" class="etiqueta">Correo institucional</label><input id="correo" name="correo" type="email" class="campo" value="{{ old('correo') }}" placeholder="usuario@ipn.mx" required>@error('correo')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label for="rol" class="etiqueta">Rol</label>
                <select id="rol" name="rol" class="campo"><option value="responsable">Responsable de grupo</option><option value="coordinador" @selected(old('rol') === 'coordinador')>Coordinador</option></select></div>
            <button class="btn-primario w-full">Crear cuenta</button>
            <p class="text-xs text-gray-500">El usuario recibe un correo para definir su contraseña.</p>
        </form>
    </aside>
</div>
@endsection
