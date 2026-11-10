<?php

namespace Database\Seeders;

use App\Modulos\Actividades\Modelos\Categoria;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    public const CATEGORIAS = [
        'Música' => 'Talleres, ensambles y conciertos',
        'Danza' => 'Danza folklórica, contemporánea y urbana',
        'Teatro' => 'Puesta en escena, improvisación y expresión corporal',
        'Literatura' => 'Círculos de lectura y talleres de escritura',
        'Artes visuales' => 'Dibujo, pintura, fotografía y exposiciones',
        'Deportes' => 'Equipos representativos, torneos y acondicionamiento',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $nombre => $descripcion) {
            Categoria::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
