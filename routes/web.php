<?php

use App\Modulos\Actividades\Http\Controllers\CatalogoController;
use App\Modulos\Identidad\Http\Controllers\ContrasenaController;
use App\Modulos\Identidad\Http\Controllers\RegistroController;
use App\Modulos\Identidad\Http\Controllers\SesionController;
use App\Modulos\Inscripciones\Http\Controllers\InicioAlumnoController;
use App\Modulos\Inscripciones\Http\Controllers\InscripcionController;
use Illuminate\Support\Facades\Route;

/*
| Rutas del Portal de Actividades Culturales (PAC).
| Cada grupo corresponde a un módulo y está protegido por el middleware de rol (RBAC).
*/

Route::get('/', fn () => auth()->check()
    ? redirect(SesionController::rutaInicio(auth()->user()))
    : redirect()->route('login'));

/* ---------- Identidad y acceso (invitados) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [SesionController::class, 'create'])->name('login');
    Route::post('/login', [SesionController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/olvide-contrasena', [ContrasenaController::class, 'solicitar'])->name('password.request');
    Route::post('/olvide-contrasena', [ContrasenaController::class, 'enviar'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer/{token}', [ContrasenaController::class, 'formulario'])->name('password.reset');
    Route::post('/restablecer', [ContrasenaController::class, 'restablecer'])->name('password.update');
});

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    Route::post('/logout', [SesionController::class, 'destroy'])->name('logout');

    /* ---------- Alumno ---------- */
    Route::middleware('rol:alumno')->group(function () {
        Route::get('/inicio', InicioAlumnoController::class)->name('alumno.inicio');
        Route::get('/actividades', [CatalogoController::class, 'index'])->name('actividades.index');
        Route::get('/actividades/{actividad}', [CatalogoController::class, 'show'])->name('actividades.show');
        Route::post('/actividades/{actividad}/inscripciones', [InscripcionController::class, 'store'])
            ->middleware('throttle:30,1')->name('inscripciones.store');
        Route::delete('/inscripciones/{inscripcion}', [InscripcionController::class, 'destroy'])->name('inscripciones.destroy');
        Route::get('/mis-actividades', [InscripcionController::class, 'index'])->name('inscripciones.index');
    });

    /* ---------- Responsable de grupo (Sprint 4) ---------- */
    Route::middleware('rol:responsable,coordinador')->prefix('responsable')->name('responsable.')->group(function () {
        Route::view('/', 'pendiente', ['titulo' => 'Panel del responsable', 'sprint' => 'Sprint 4: sesiones de asistencia con QR y contingencia'])->name('inicio');
    });

    /* ---------- Coordinación y superadministración (Sprints 2 y 5) ---------- */
    Route::middleware('rol:coordinador')->prefix('coordinacion')->name('coordinacion.')->group(function () {
        Route::view('/', 'pendiente', ['titulo' => 'Panel de coordinación', 'sprint' => 'Sprint 2: gestión de actividades, horarios y cupos'])->name('inicio');
    });
});
