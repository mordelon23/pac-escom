<?php

namespace App\Modulos\Compartido\Auditoria;

use Illuminate\Support\Facades\DB;

/** Registra operaciones sensibles en la tabla auditoria (sección 22.5 del plan). */
class Auditor
{
    public static function registrar(string $accion, string $entidad, ?int $entidadId = null, array $datos = []): void
    {
        DB::table('auditoria')->insert([
            'usuario_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos' => $datos ? json_encode($datos, JSON_UNESCAPED_UNICODE) : null,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
