<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Identidad y acceso: roles, permisos (RBAC), usuarios, recuperación de contraseña y sesiones.
 * Corresponde a las tablas usuarios, roles, permisos y rol_permiso del diccionario de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 30)->unique();     // alumno, responsable, coordinador, superadministrador
            $table->string('nombre', 40)->unique();
            $table->timestampsTz();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();     // p. ej. actividades.publicar
            $table->string('descripcion', 160);
            $table->timestampsTz();
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->string('matricula', 10)->nullable()->unique();   // solo alumnos (RB3)
            $table->string('nombre', 60);
            $table->string('apellidos', 80);
            $table->string('correo', 120)->unique();
            $table->timestampTz('correo_verificado_en')->nullable();
            $table->string('password');
            $table->string('estado', 20)->default('ACTIVA');
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->rememberToken();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_estado_check CHECK (estado IN ('ACTIVA','SUSPENDIDA'))");
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_matricula_check CHECK (matricula IS NULL OR matricula ~ '^[0-9]{10}$')");
        DB::statement('ALTER TABLE usuarios ADD CONSTRAINT usuarios_intentos_check CHECK (intentos_fallidos >= 0)');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();          // nombre exigido por el broker de Laravel; guarda el correo
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
    }
};
