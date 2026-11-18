@extends('layouts.app', ['titulo' => 'Ayuda'])
@section('contenido')
@php
    $u = auth()->user();
    $r = config('pac.reglas');
    // Secciones visibles según el rol: cada quien ve primero lo que usa.
    $secciones = match (true) {
        $u->esAlumno() => ['alumno', 'cuenta'],
        $u->esSuperadministrador() => ['coordinacion', 'superadmin', 'responsable', 'cuenta'],
        $u->esCoordinador() => ['coordinacion', 'responsable', 'cuenta'],
        default => ['responsable', 'cuenta'],
    };
    $preguntas = [
        'alumno' => ['Alumno', [
            ['¿Cómo me inscribo a una actividad?', 'Entra a <strong>Catálogo</strong>, abre la actividad que te interese y pulsa <strong>Inscribirme</strong>. Confirma y recibirás un correo con el horario.'],
            ['¿Cuántas actividades puedo tomar?', "Hasta <strong>{$r['max_inscripciones_activas']}</strong> al mismo tiempo. Cuando una termina o la cancelas, se libera un lugar en tu límite."],
            ['¿Puedo cancelar mi inscripción?', "Sí, desde <strong>Mis actividades</strong> o desde el detalle de la actividad, hasta <strong>{$r['horas_minimas_cancelacion']} horas</strong> antes de que empiece. Tu lugar queda libre para otro alumno."],
            ['¿Cómo registro mi asistencia?', "El responsable proyecta un código QR que cambia cada {$r['segundos_ventana_qr']} segundos. Escanéalo con la <strong>cámara de tu teléfono</strong> o con <strong>Escanear asistencia</strong> en tu inicio. Debes haber iniciado sesión y estar inscrito en la actividad."],
            ['El QR dice «código expirado», ¿qué hago?', 'Vuelve a escanear el código que está en pantalla en ese momento; no uses fotos ni capturas. Si no tienes datos o el sistema falla, pide al responsable que te registre en <strong>modo contingencia</strong>.'],
            ['¿Para qué sirve la agenda?', 'Para guardar actividades que te interesan sin inscribirte todavía. Usa <strong>Guardar en mi agenda</strong> en el detalle de la actividad.'],
            ['¿Por qué mi publicación del muro no aparece?', 'Las publicaciones se revisan antes de mostrarse. Solo puedes publicar sobre actividades en las que estás inscrito. Si se rechaza, verás el motivo en el muro.'],
        ]],
        'responsable' => ['Responsable de grupo', [
            ['¿Cómo paso lista?', 'En <strong>Mis grupos</strong> aparecen las sesiones del día. Pulsa <strong>Abrir sesión de asistencia</strong> y proyecta el código QR; el contador se actualiza solo.'],
            ['¿Qué hago si no hay internet o falla el sistema?', 'Pulsa <strong>Activar modo contingencia</strong>. Podrás registrar a cada alumno manualmente indicando el motivo; todo queda en la bitácora. Cuando vuelva la conexión, usa <strong>Reanudar registro con QR</strong>.'],
            ['¿Cómo termino la sesión?', 'Pulsa <strong>Finalizar pase de lista</strong>. Verás el resumen con la hora y el método de cada registro. Después ya no se aceptan asistencias.'],
            ['Cerré el navegador sin terminar, ¿perdí la lista?', 'No. Regresa a <strong>Mis grupos</strong> y pulsa <strong>Continuar sesión</strong>; los registros se conservan.'],
        ]],
        'coordinacion' => ['Coordinación', [
            ['¿Cómo creo una actividad?', 'En <strong>Actividades → Nueva actividad</strong> captura los datos, la imagen y el horario de cada día (lunes a viernes). Se guarda como borrador; los alumnos la ven hasta que pulses <strong>Publicar</strong>.'],
            ['¿Qué pasa si cancelo una actividad?', 'Se cancelan todas sus inscripciones y cada alumno recibe un aviso por correo. Esta acción no se puede deshacer.'],
            ['¿Cómo obtengo el reporte del semestre?', 'En <strong>Reportes</strong> elige el periodo y la categoría. Puedes descargarlo en <strong>Excel (.xlsx)</strong>, en <strong>CSV</strong> o abrir la versión imprimible para guardarla como PDF.'],
            ['¿Cuándo recibo avisos de cupo?', "Cuando una actividad que creaste llega al <strong>{$r['porcentaje_aviso_cupo']} %</strong> de su cupo, recibes un correo."],
            ['¿Cómo modero el muro?', 'En <strong>Moderación</strong> aprueba o rechaza cada publicación. Si la rechazas, escribe el motivo: el alumno lo recibe por correo.'],
        ]],
        'superadmin' => ['Superadministrador', [
            ['¿Cómo doy de alta al personal del nuevo semestre?', 'En <strong>Usuarios</strong> captura nombre, apellidos y correo <strong>@ipn.mx</strong> y elige el rol. La persona recibe un correo para definir su contraseña.'],
            ['¿Cómo retiro el acceso a alguien que ya no participa?', 'Pulsa <strong>Suspender</strong> en su fila. La cuenta se conserva para los reportes históricos, pero ya no puede entrar. Puedes reactivarla después.'],
        ]],
        'cuenta' => ['Mi cuenta', [
            ['Olvidé mi contraseña', 'En la pantalla de inicio de sesión pulsa <strong>¿Olvidaste tu contraseña?</strong>. El enlace que recibes es válido por 10 minutos y se usa una sola vez.'],
            ['Me dice «Demasiados intentos»', "Por seguridad, tras {$r['intentos_por_minuto']} intentos fallidos el acceso se bloquea {$r['minutos_bloqueo']} minutos. Espera y vuelve a intentarlo, o restablece tu contraseña."],
            ['Me dice «Tu sesión expiró»', 'Pasó mucho tiempo sin actividad. Recarga la página e inicia sesión de nuevo; no se pierde ninguna inscripción ni asistencia ya registrada.'],
        ]],
    ];
@endphp
<h1 class="text-2xl font-bold sm:text-3xl">Ayuda</h1>
<p class="mt-1 text-gray-600">Respuestas rápidas para usar el portal como {{ mb_strtolower($u->rolSistema()->etiqueta()) }}.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-[14rem_1fr]">
    <nav aria-label="Secciones de ayuda" class="hidden lg:block">
        <ul class="sticky top-24 space-y-1 text-sm">
            @foreach ($secciones as $s)
                <li><a href="#{{ $s }}" class="block rounded-lg px-3 py-2 hover:bg-guinda-50">{{ $preguntas[$s][0] }}</a></li>
            @endforeach
        </ul>
    </nav>
    <div class="space-y-8">
        @foreach ($secciones as $s)
            <section id="{{ $s }}" aria-labelledby="titulo-{{ $s }}">
                <h2 id="titulo-{{ $s }}" class="text-lg font-semibold">{{ $preguntas[$s][0] }}</h2>
                <div class="tarjeta mt-3 divide-y divide-gray-100">
                    @foreach ($preguntas[$s][1] as [$pregunta, $respuesta])
                        <details class="group p-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium">
                                {{ $pregunta }}
                                <span class="text-guinda-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                            </summary>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700">{!! $respuesta !!}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach
        <p class="text-sm text-gray-600">¿No encontraste lo que buscabas? Acude a la Coordinación Cultural de la ESCOM.</p>
    </div>
</div>
@endsection
