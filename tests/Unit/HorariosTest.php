<?php

namespace Tests\Unit;

use App\Modulos\Actividades\Acciones\GuardarActividad;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use PHPUnit\Framework\TestCase;

/** RB26: los horarios de una actividad no pueden traslaparse el mismo día. */
class HorariosTest extends TestCase
{
    public function test_horarios_en_dias_distintos_son_validos(): void
    {
        GuardarActividad::verificarTraslapes([
            ['dia_semana' => 1, 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
            ['dia_semana' => 3, 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
        ]);
        $this->addToAssertionCount(1);
    }

    public function test_horarios_contiguos_no_se_traslapan(): void
    {
        GuardarActividad::verificarTraslapes([
            ['dia_semana' => 2, 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
            ['dia_semana' => 2, 'hora_inicio' => '12:00', 'hora_fin' => '13:00'],
        ]);
        $this->addToAssertionCount(1);
    }

    public function test_horarios_traslapados_se_rechazan(): void
    {
        $this->expectException(ReglaNegocioException::class);
        GuardarActividad::verificarTraslapes([
            ['dia_semana' => 2, 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
            ['dia_semana' => 2, 'hora_inicio' => '11:30', 'hora_fin' => '13:00'],
        ]);
    }
}
