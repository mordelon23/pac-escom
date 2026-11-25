<?php

namespace App\Console\Commands;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Compartido\Notificaciones\Notificador;
use App\Modulos\Inscripciones\Enums\EstadoInscripcion;
use App\Modulos\Inscripciones\Notificaciones\RecordatorioActividad;
use Illuminate\Console\Command;

/**
 * RF8 — Envía el recordatorio a los alumnos inscritos en actividades cuya primera sesión
 * ocurre en las próximas 24 horas. Se programa cada hora; cada alumno lo recibe una sola vez.
 *   php artisan pac:recordatorios
 */
class EnviarRecordatorios extends Command
{
    protected $signature = 'pac:recordatorios';

    protected $description = 'Envía los recordatorios de las actividades que inician en las próximas 24 horas';

    public function handle(): int
    {
        $ahora = now();
        $limite = $ahora->copy()->addDay();
        $enviados = 0;

        Actividad::query()
            ->where('estado', EstadoActividad::PUBLICADA)
            ->whereBetween('fecha_inicio', [$ahora->toDateString(), $limite->toDateString()])
            ->with('horarios')
            ->get()
            ->each(function (Actividad $a) use ($ahora, $limite, &$enviados) {
                $inicio = $a->inicioPrimeraSesion();
                if (! $inicio || $inicio->lt($ahora) || $inicio->gt($limite)) {
                    return;
                }
                $a->inscripciones()
                    ->where('estado', EstadoInscripcion::ACTIVA)
                    ->whereNull('recordatorio_enviado_en')
                    ->with('alumno')
                    ->get()
                    ->each(function ($inscripcion) use ($a, $inicio, &$enviados) {
                        if (Notificador::enviar($inscripcion->alumno, new RecordatorioActividad($a, $inicio))) {
                            $inscripcion->forceFill(['recordatorio_enviado_en' => now()])->save();
                            $enviados++;
                        }
                    });
            });

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
