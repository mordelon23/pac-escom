<?php

use App\Modulos\Actividades\Http\Controllers\CalendarioController;
use App\Modulos\Actividades\Http\Controllers\CatalogoController;
use App\Modulos\Actividades\Http\Controllers\GestionActividadesController;
use App\Modulos\Actividades\Http\Controllers\PanelCoordinacionController;
use App\Modulos\Asistencia\Http\Controllers\EscanerController;
use App\Modulos\Asistencia\Http\Controllers\GruposController;
use App\Modulos\Asistencia\Http\Controllers\SesionAsistenciaController;
use App\Modulos\Comunidad\Http\Controllers\ModeracionController;
use App\Modulos\Comunidad\Http\Controllers\MuroController;
use App\Modulos\Identidad\Http\Controllers\UsuariosController;
use App\Modulos\Inscripciones\Http\Controllers\AgendaController;
use App\Modulos\Reportes\Http\Controllers\ReportesController;
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
        Route::get('/calendario', CalendarioController::class)->name('calendario');
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::post('/agenda/{actividad}', [AgendaController::class, 'alternar'])->name('agenda.alternar');
        Route::get('/muro', [MuroController::class, 'index'])->name('muro.index');
        Route::post('/muro', [MuroController::class, 'store'])->middleware('throttle:10,1')->name('muro.store');
        Route::delete('/muro/{publicacion}', [MuroController::class, 'destroy'])->name('muro.destroy');
        Route::get('/asistencia/escanear', [EscanerController::class, 'create'])->name('asistencia.escanear');
        Route::post('/asistencia', [EscanerController::class, 'store'])->middleware('throttle:20,1')->name('asistencia.store');
    });

    /* ---------- Responsable de grupo (y coordinación) ---------- */
    Route::middleware('rol:responsable,coordinador')->prefix('responsable')->name('responsable.')->group(function () {
        Route::get('/', GruposController::class)->name('inicio');
        Route::post('/horarios/{horario}/sesiones', [SesionAsistenciaController::class, 'store'])->name('sesiones.store');
        Route::get('/sesiones/{sesion}', [SesionAsistenciaController::class, 'show'])->name('sesiones.show');
        Route::get('/sesiones/{sesion}/estado', [SesionAsistenciaController::class, 'estado'])->name('sesiones.estado');
        Route::patch('/sesiones/{sesion}/contingencia', [SesionAsistenciaController::class, 'contingencia'])->name('sesiones.contingencia');
        Route::patch('/sesiones/{sesion}/reanudar', [SesionAsistenciaController::class, 'reanudar'])->name('sesiones.reanudar');
        Route::post('/sesiones/{sesion}/manual', [SesionAsistenciaController::class, 'registrarManual'])->name('sesiones.manual');
        Route::patch('/sesiones/{sesion}/cerrar', [SesionAsistenciaController::class, 'cerrar'])->name('sesiones.cerrar');
    });

    /* ---------- Coordinación (el superadministrador hereda el acceso) ---------- */
    Route::middleware('rol:coordinador')->prefix('coordinacion')->name('coordinacion.')->group(function () {
        Route::get('/', PanelCoordinacionController::class)->name('inicio');
        Route::resource('actividades', GestionActividadesController::class)->parameters(['actividades' => 'actividad']);
        Route::patch('/actividades/{actividad}/publicar', [GestionActividadesController::class, 'publicar'])->name('actividades.publicar');
        Route::patch('/actividades/{actividad}/cancelar', [GestionActividadesController::class, 'cancelar'])->name('actividades.cancelar');
        Route::get('/reportes', [ReportesController::class, 'index'])->name('reportes');
        Route::get('/muro', [ModeracionController::class, 'index'])->name('muro.index');
        Route::patch('/muro/{publicacion}/aprobar', [ModeracionController::class, 'aprobar'])->name('muro.aprobar');
        Route::patch('/muro/{publicacion}/rechazar', [ModeracionController::class, 'rechazar'])->name('muro.rechazar');

        /* ---------- Solo superadministrador (CU15) ---------- */
        Route::middleware('rol:superadministrador')->group(function () {
            Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
            Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
            Route::patch('/usuarios/{usuario}', [UsuariosController::class, 'update'])->name('usuarios.update');
        });
    });
});
