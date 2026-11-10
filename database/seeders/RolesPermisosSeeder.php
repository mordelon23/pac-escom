<?php

namespace Database\Seeders;

use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Permiso;
use App\Modulos\Identidad\Modelos\Rol;
use Illuminate\Database\Seeder;

/** Catálogo RBAC: roles y permisos (RF15, RF16). */
class RolesPermisosSeeder extends Seeder
{
    public const PERMISOS = [
        'catalogo.consultar' => 'Consultar el catálogo y el calendario',
        'inscripciones.gestionar' => 'Inscribirse y cancelar inscripciones propias',
        'asistencia.registrar_qr' => 'Registrar asistencia escaneando el QR',
        'agenda.gestionar' => 'Gestionar la agenda cultural',
        'muro.publicar' => 'Publicar en el muro social',
        'sesiones.gestionar' => 'Abrir y cerrar sesiones de asistencia',
        'asistencia.contingencia' => 'Registrar asistencia en modo contingencia',
        'inscritos.consultar' => 'Consultar la lista de inscritos (RB17)',
        'actividades.gestionar' => 'Crear, editar, publicar y cancelar actividades',
        'reportes.generar' => 'Generar reportes de participación',
        'muro.moderar' => 'Moderar publicaciones del muro',
        'usuarios.gestionar' => 'Gestionar usuarios y roles del personal',
    ];

    public const MATRIZ = [
        'alumno' => ['catalogo.consultar', 'inscripciones.gestionar', 'asistencia.registrar_qr', 'agenda.gestionar', 'muro.publicar'],
        'responsable' => ['catalogo.consultar', 'sesiones.gestionar', 'asistencia.contingencia', 'inscritos.consultar'],
        'coordinador' => ['catalogo.consultar', 'sesiones.gestionar', 'asistencia.contingencia', 'inscritos.consultar',
            'actividades.gestionar', 'reportes.generar', 'muro.moderar'],
        // El superadministrador especializa al coordinador y añade la gestión de usuarios
        'superadministrador' => ['catalogo.consultar', 'sesiones.gestionar', 'asistencia.contingencia', 'inscritos.consultar',
            'actividades.gestionar', 'reportes.generar', 'muro.moderar', 'usuarios.gestionar'],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $clave => $descripcion) {
            Permiso::updateOrCreate(['clave' => $clave], ['descripcion' => $descripcion]);
        }
        foreach (RolSistema::cases() as $rol) {
            $modelo = Rol::updateOrCreate(['clave' => $rol->value], ['nombre' => $rol->etiqueta()]);
            $modelo->permisos()->sync(Permiso::whereIn('clave', self::MATRIZ[$rol->value])->pluck('id'));
        }
    }
}
