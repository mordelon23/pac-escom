<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF8: marca de envío del recordatorio 24 h antes del inicio (evita duplicados).
 * RNF8: evidencia de la aceptación expresa del aviso de privacidad al registrarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $t) {
            $t->timestamp('recordatorio_enviado_en')->nullable();
        });
        Schema::table('usuarios', function (Blueprint $t) {
            $t->timestamp('aviso_privacidad_aceptado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', fn (Blueprint $t) => $t->dropColumn('recordatorio_enviado_en'));
        Schema::table('usuarios', fn (Blueprint $t) => $t->dropColumn('aviso_privacidad_aceptado_en'));
    }
};
