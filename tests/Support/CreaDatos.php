<?php

namespace Tests\Support;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use Database\Seeders\RolesPermisosSeeder;

trait CreaDatos
{
    private int $consecutivo = 0;

    protected function sembrarRoles(): void
    {
        $this->seed(RolesPermisosSeeder::class);
    }

    protected function usuario(RolSistema $rol = RolSistema::ALUMNO): Usuario
    {
        $n = ++$this->consecutivo;
        $u = Usuario::create([
            'rol_id' => Rol::where('clave', $rol->value)->value('id'),
            'nombre' => "Usuario{$n}", 'apellidos' => 'Prueba',
            'correo' => $rol === RolSistema::ALUMNO ? "u{$n}@alumno.ipn.mx" : "u{$n}@ipn.mx",
            'matricula' => $rol === RolSistema::ALUMNO ? str_pad((string) (2023630000 + $n), 10, '0') : null,
            'password' => 'Secreta123',
        ]);
        $u->forceFill(['correo_verificado_en' => now()])->save();

        return $u;
    }

    /** Actividad publicada que se imparte todos los días hábiles (para que «hoy» siempre aplique entre semana). */
    protected function actividad(array $extra = [], array $dias = [1, 2, 3, 4, 5]): Actividad
    {
        $coord = $this->usuario(RolSistema::COORDINADOR);
        $resp = $this->usuario(RolSistema::RESPONSABLE);
        $a = Actividad::create(array_merge([
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Música'])->id,
            'responsable_id' => $resp->id, 'creada_por' => $coord->id,
            'titulo' => 'Taller de prueba', 'descripcion' => 'Descripción de prueba', 'lugar' => 'Aula 1',
            'fecha_inicio' => now()->addDays(3)->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(),
            'cupo_maximo' => 10, 'imagen_ruta' => 'demo/musica.svg', 'estado' => EstadoActividad::PUBLICADA,
        ], $extra));
        foreach ($dias as $d) {
            $a->horarios()->create(['dia_semana' => $d, 'hora_inicio' => '09:00', 'hora_fin' => '11:00']);
        }

        return $a->load('horarios');
    }
}
