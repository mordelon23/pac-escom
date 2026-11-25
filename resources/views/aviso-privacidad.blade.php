{{-- RNF8 — Aviso de privacidad integral (público). Texto modelo del prototipo: debe validarlo
     la Unidad de Transparencia del IPN antes de la puesta en producción. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aviso de privacidad · PAC ESCOM</title>
    <meta name="theme-color" content="#6C1D45">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
<header class="bg-guinda-700 text-white">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2 font-bold">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/15 text-sm">PAC</span> Actividades Culturales
        </a>
        <a href="{{ auth()->check() ? url('/') : route('registro') }}" class="text-sm font-medium hover:underline">{{ auth()->check() ? 'Volver al portal' : 'Ir al registro' }}</a>
    </div>
</header>
<main class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-2xl font-bold sm:text-3xl">Aviso de privacidad integral</h1>
    <p class="mt-1 text-sm text-gray-600">Portal de Actividades Culturales (PAC) · Escuela Superior de Cómputo del Instituto Politécnico Nacional</p>

    <div class="tarjeta mt-6 space-y-6 p-6 text-sm leading-relaxed text-gray-700 sm:p-8">
        <section>
            <h2 class="text-base font-semibold text-gray-900">1. Responsable del tratamiento</h2>
            <p class="mt-2">La Escuela Superior de Cómputo (ESCOM) del Instituto Politécnico Nacional, a través de su área de actividades culturales, es responsable del tratamiento de los datos personales que se recaban en este portal, conforme a la Ley General de Protección de Datos Personales en Posesión de Sujetos Obligados y demás normativa aplicable.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">2. Datos personales que se recaban</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Identificación: nombre, apellidos y número de boleta.</li>
                <li>Contacto: correo electrónico institucional.</li>
                <li>Participación: actividades en las que te inscribes, asistencias registradas, actividades guardadas en tu agenda y publicaciones del muro, incluidas las fotografías que decidas compartir.</li>
                <li>Seguridad: fecha y hora de acceso e intentos de inicio de sesión.</li>
            </ul>
            <p class="mt-2">No se recaban datos personales sensibles. La contraseña se almacena cifrada con una función de un solo sentido y nadie, ni el personal del portal, puede conocerla.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">3. Finalidades</h2>
            <p class="mt-2">Tus datos se usan exclusivamente para: (a) crear y administrar tu cuenta; (b) gestionar tus inscripciones y el control de cupos; (c) registrar tu asistencia; (d) enviarte confirmaciones, recordatorios y avisos de cancelación; (e) moderar y mostrar tus publicaciones del muro; y (f) generar estadísticas de participación, que se presentan de forma agregada.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">4. Transferencias</h2>
            <p class="mt-2">Tus datos no se transfieren a terceros, salvo las excepciones previstas en la ley o por requerimiento fundado y motivado de una autoridad competente. Los responsables de grupo solo ven la lista de inscritos de sus propias actividades.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">5. Derechos ARCO</h2>
            <p class="mt-2">Puedes ejercer tus derechos de Acceso, Rectificación, Cancelación y Oposición, así como la portabilidad de tus datos, ante la Unidad de Transparencia del Instituto Politécnico Nacional o mediante la Plataforma Nacional de Transparencia. En la sección <strong>Mi cuenta</strong> del portal puedes consultar los datos que tenemos registrados.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">6. Conservación y seguridad</h2>
            <p class="mt-2">Los datos se conservan mientras tengas la calidad de alumno y durante el plazo necesario para los reportes del ciclo escolar. Se aplican medidas de seguridad administrativas, técnicas y físicas: comunicación cifrada, control de acceso por rol, bitácora de auditoría y respaldos periódicos.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-900">7. Cambios al aviso</h2>
            <p class="mt-2">Cualquier cambio a este aviso se publicará en esta misma página. Al registrarte, el portal guarda la fecha y hora en que lo aceptaste.</p>
        </section>
        <p class="border-t border-gray-100 pt-4 text-xs text-gray-500">Última actualización: noviembre de 2026. Texto del prototipo académico sujeto a validación por la Unidad de Transparencia del IPN.</p>
    </div>
</main>
</body>
</html>
