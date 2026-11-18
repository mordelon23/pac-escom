{{-- Páginas de error propias: no consultan la base de datos ni dependen de los estilos compilados,
     para que se muestren aun cuando PostgreSQL o el servidor fallen (tolerancia a fallos). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('titulo') · PAC ESCOM</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        *{box-sizing:border-box} body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f9fafb;color:#111827;
        font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;padding:16px}
        .caja{max-width:30rem;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
        .marca{display:flex;align-items:center;gap:8px;font-weight:700;color:#6C1D45;margin-bottom:24px}
        .logo{display:grid;place-items:center;width:36px;height:36px;border-radius:8px;background:#6C1D45;color:#fff;font-size:13px}
        .codigo{font-size:13px;font-weight:600;color:#6C1D45;letter-spacing:.05em} h1{margin:4px 0 8px;font-size:24px}
        p{margin:0 0 20px;color:#4b5563;line-height:1.5} a.boton{display:inline-block;background:#6C1D45;color:#fff;text-decoration:none;
        padding:10px 18px;border-radius:8px;font-weight:600} a.boton:focus-visible{outline:3px solid #b35a85;outline-offset:2px}
    </style>
</head>
<body>
<main class="caja">
    <div class="marca"><span class="logo" aria-hidden="true">PAC</span> Actividades Culturales</div>
    <div class="codigo">Error @yield('codigo')</div>
    <h1>@yield('titulo')</h1>
    <p>@yield('mensaje')</p>
    <a class="boton" href="@yield('enlace', url('/'))">@yield('boton', 'Volver al inicio')</a>
</main>
</body>
</html>
