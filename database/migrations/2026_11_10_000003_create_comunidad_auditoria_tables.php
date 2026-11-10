<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos Comunidad y Auditoría: agenda, muro social, avisos y bitácora de operaciones sensibles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['usuario_id', 'actividad_id']);
        });

        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('moderada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('contenido', 500);
            $table->string('imagen_ruta', 255)->nullable();
            $table->string('estado_moderacion', 20)->default('PENDIENTE');
            $table->string('motivo_rechazo', 255)->nullable();
            $table->timestampsTz();

            $table->index(['estado_moderacion', 'created_at']);
        });
        DB::statement("ALTER TABLE publicaciones ADD CONSTRAINT publicaciones_estado_check CHECK (estado_moderacion IN ('PENDIENTE','APROBADA','RECHAZADA'))");

        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('autor_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('titulo', 120);
            $table->text('cuerpo');
            $table->string('tipo', 20)->default('AVISO');
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE avisos ADD CONSTRAINT avisos_tipo_check CHECK (tipo IN ('AVISO','ENCUESTA'))");

        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('accion', 60);
            $table->string('entidad', 60);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->jsonb('datos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['entidad', 'entidad_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('avisos');
        Schema::dropIfExists('publicaciones');
        Schema::dropIfExists('agenda');
    }
};
