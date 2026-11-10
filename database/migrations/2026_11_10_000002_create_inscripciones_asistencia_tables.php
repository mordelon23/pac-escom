<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos Inscripciones y Asistencia: inscripciones, sesiones, asistencias y bitácora de contingencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('actividad_id')->constrained('actividades')->restrictOnDelete();
            $table->string('estado', 20)->default('ACTIVA');
            $table->timestampTz('fecha_inscripcion')->useCurrent();
            $table->timestampTz('fecha_cancelacion')->nullable();
            $table->timestampsTz();

            $table->index(['usuario_id', 'estado']);
            $table->index(['actividad_id', 'estado']);
        });
        DB::statement("ALTER TABLE inscripciones ADD CONSTRAINT inscripciones_estado_check CHECK (estado IN ('ACTIVA','CANCELADA','CONCLUIDA'))");
        // RB4: una sola inscripción ACTIVA por alumno y actividad (índice único parcial)
        DB::statement("CREATE UNIQUE INDEX inscripciones_activa_unica ON inscripciones (usuario_id, actividad_id) WHERE estado = 'ACTIVA'");

        Schema::create('sesiones_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios')->restrictOnDelete();
            $table->foreignId('abierta_por')->constrained('usuarios')->restrictOnDelete();
            $table->date('fecha');
            $table->timestampTz('inicio');
            $table->timestampTz('fin')->nullable();
            $table->string('secreto_hmac', 64);
            $table->string('estado', 20)->default('ABIERTA');
            $table->timestampsTz();

            $table->unique(['horario_id', 'fecha']);   // una sesión por horario y día
        });
        DB::statement("ALTER TABLE sesiones_asistencia ADD CONSTRAINT sesiones_estado_check CHECK (estado IN ('ABIERTA','CONTINGENCIA','CERRADA'))");

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_asistencia')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('registrada_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('metodo', 10);
            $table->timestampTz('registrada_en')->useCurrent();
            $table->timestampsTz();

            $table->unique(['sesion_id', 'usuario_id']);   // RB21: asistencia única por sesión
        });
        DB::statement("ALTER TABLE asistencias ADD CONSTRAINT asistencias_metodo_check CHECK (metodo IN ('QR','MANUAL'))");

        Schema::create('bitacora_contingencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_asistencia')->cascadeOnDelete();
            $table->foreignId('asistencia_id')->constrained('asistencias')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('motivo', 40);
            $table->string('detalle', 255)->nullable();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE bitacora_contingencias ADD CONSTRAINT bitacora_motivo_check CHECK (motivo IN ('SIN_RED','FALLA_DISPOSITIVO_ALUMNO','FALLA_PROYECTOR','OTRO'))");
        DB::statement("ALTER TABLE bitacora_contingencias ADD CONSTRAINT bitacora_detalle_check CHECK (motivo <> 'OTRO' OR detalle IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_contingencias');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('sesiones_asistencia');
        Schema::dropIfExists('inscripciones');
    }
};
