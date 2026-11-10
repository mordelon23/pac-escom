<?php

namespace Database\Seeders;

use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use Illuminate\Database\Seeder;

/** Crea la cuenta inicial del superadministrador a partir de variables de entorno. */
class SuperadministradorSeeder extends Seeder
{
    public function run(): void
    {
        $correo = env('PAC_SUPERADMIN_CORREO', 'superadmin@ipn.mx');
        Usuario::firstOrCreate(['correo' => $correo], [
            'rol_id' => Rol::where('clave', RolSistema::SUPERADMINISTRADOR->value)->value('id'),
            'nombre' => 'Superadministrador',
            'apellidos' => 'PAC',
            'password' => env('PAC_SUPERADMIN_PASSWORD', 'CambiaEsto2026'),
        ])->forceFill(['correo_verificado_en' => now()])->save();
    }
}
