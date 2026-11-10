<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesPermisosSeeder::class);
        $this->call(SuperadministradorSeeder::class);
        $this->call(CategoriasSeeder::class);
        if (app()->environment(['local', 'testing']) || config('pac.datos_demo')) {
            $this->call(DemoSeeder::class);
        }
    }
}
