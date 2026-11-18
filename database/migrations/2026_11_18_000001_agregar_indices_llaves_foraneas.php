<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eficiencia: PostgreSQL no indexa automáticamente las llaves foráneas.
 * Se agregan índices a las columnas por las que se filtra o se une con frecuencia
 * (Mis grupos del responsable, historial de asistencias, bitácora, muro y usuarios por rol).
 */
return new class extends Migration
{
    private const INDICES = [
        'actividades' => ['responsable_id', 'creada_por'],
        'usuarios' => ['rol_id'],
        'asistencias' => ['usuario_id', 'registrada_por'],
        'sesiones_asistencia' => ['abierta_por'],
        'bitacora_contingencias' => ['sesion_id', 'alumno_id'],
        'publicaciones' => ['usuario_id', 'actividad_id'],
        'avisos' => ['actividad_id'],
        'auditoria' => ['usuario_id'],
    ];

    public function up(): void
    {
        foreach (self::INDICES as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $c) {
                    $t->index($c);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $c) {
                    $t->dropIndex([$c]);
                }
            });
        }
    }
};
