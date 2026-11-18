<?php

use App\Modulos\Compartido\Http\Middleware\CabecerasSeguridad;
use App\Modulos\Identidad\Http\Controllers\SesionController;
use App\Modulos\Identidad\Http\Middleware\CuentaActiva;
use App\Modulos\Identidad\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => VerificarRol::class,
            'cuenta.activa' => CuentaActiva::class,
        ]);
        $middleware->append(CabecerasSeguridad::class);
        // En el hosting la aplicación suele estar detrás de un proxy que termina HTTPS.
        // Solo se confía en él si se declara (si no, un atacante podría falsificar su IP y evadir RB24).
        if ($proxies = env('PAC_PROXIES_CONFIABLES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => SesionController::rutaInicio($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
