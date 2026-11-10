<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Catálogo de actividades: categorías, actividades y horarios semanales (RB7, RB18, RB26).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->timestampsTz();
        });

        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('creada_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('titulo', 120);
            $table->text('descripcion');
            $table->string('lugar', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('cupo_maximo');
            $table->integer('cupo_ocupado')->default(0);
            $table->string('imagen_ruta', 255);
            $table->string('estado', 20)->default('BORRADOR');
            $table->timestampsTz();

            $table->index(['estado', 'fecha_inicio']);
            $table->index('categoria_id');
        });
        DB::statement('ALTER TABLE actividades ADD CONSTRAINT actividades_fechas_check CHECK (fecha_fin >= fecha_inicio)');
        DB::statement('ALTER TABLE actividades ADD CONSTRAINT actividades_cupo_max_check CHECK (cupo_maximo BETWEEN 1 AND 500)');
        // Última línea de defensa contra la sobreinscripción (RB5)
        DB::statement('ALTER TABLE actividades ADD CONSTRAINT actividades_cupo_ocupado_check CHECK (cupo_ocupado >= 0 AND cupo_ocupado <= cupo_maximo)');
        DB::statement("ALTER TABLE actividades ADD CONSTRAINT actividades_estado_check CHECK (estado IN ('BORRADOR','PUBLICADA','CANCELADA','FINALIZADA'))");

        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->smallInteger('dia_semana');          // 1 = lunes … 5 = viernes (ISO-8601)
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestampsTz();

            $table->index(['actividad_id', 'dia_semana']);
        });
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_dia_check CHECK (dia_semana BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT horarios_horas_check CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('actividades');
        Schema::dropIfExists('categorias');
    }
};
