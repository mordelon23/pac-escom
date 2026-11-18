<?php

namespace App\Modulos\Inscripciones\Servicios;

use App\Modulos\Identidad\Modelos\Usuario;
use Carbon\CarbonImmutable;

/**
 * Exporta las actividades inscritas del alumno en formato iCalendar (RFC 5545), que importan
 * Google Calendar, Outlook y el calendario del teléfono (interoperabilidad).
 * Cada horario semanal se convierte en un evento que se repite cada semana durante el periodo de la actividad.
 */
class CalendarioIcs
{
    private const ZONA = 'America/Mexico_City';

    public function generar(Usuario $alumno): string
    {
        $inscripciones = $alumno->inscripcionesActivas()->with('actividad.horarios')->get();
        $sello = now()->utc()->format('Ymd\THis\Z');
        $lineas = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ESCOM IPN//Portal de Actividades Culturales//ES', 'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH', 'X-WR-CALNAME:Mis actividades culturales (PAC)', 'X-WR-TIMEZONE:'.self::ZONA,
            // México no aplica horario de verano desde 2022: UTC−6 todo el año.
            'BEGIN:VTIMEZONE', 'TZID:'.self::ZONA, 'BEGIN:STANDARD', 'DTSTART:19700101T000000', 'TZOFFSETFROM:-0600',
            'TZOFFSETTO:-0600', 'TZNAME:CST', 'END:STANDARD', 'END:VTIMEZONE',
        ];

        foreach ($inscripciones as $i) {
            $a = $i->actividad;
            $fin = CarbonImmutable::parse($a->fecha_fin, self::ZONA)->setTime(23, 59, 59);
            foreach ($a->horarios as $h) {
                $dia = CarbonImmutable::parse($a->fecha_inicio, self::ZONA);
                while ($dia->dayOfWeekIso !== $h->dia_semana->value) {
                    $dia = $dia->addDay();
                }
                if ($dia->gt($fin)) {
                    continue;
                }
                $ini = $dia->setTimeFromTimeString($h->hora_inicio);
                $ter = $dia->setTimeFromTimeString($h->hora_fin);
                array_push($lineas,
                    'BEGIN:VEVENT',
                    "UID:pac-{$a->id}-{$h->id}-{$alumno->id}@escom.ipn.mx",
                    "DTSTAMP:{$sello}",
                    'DTSTART;TZID='.self::ZONA.':'.$ini->format('Ymd\THis'),
                    'DTEND;TZID='.self::ZONA.':'.$ter->format('Ymd\THis'),
                    'RRULE:FREQ=WEEKLY;UNTIL='.$fin->utc()->format('Ymd\THis\Z'),
                    'SUMMARY:'.self::texto($a->titulo),
                    'LOCATION:'.self::texto($a->lugar),
                    'DESCRIPTION:'.self::texto(mb_strimwidth($a->descripcion, 0, 300, '…')),
                    'END:VEVENT',
                );
            }
        }
        $lineas[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'plegar'], $lineas))."\r\n";
    }

    /** Escapa los caracteres especiales de iCalendar. */
    private static function texto(string $t): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $t);
    }

    /** RFC 5545: las líneas de más de 75 octetos se continúan con un espacio inicial. */
    private static function plegar(string $linea): string
    {
        $partes = [];
        while (strlen($linea) > 75) {
            $corte = mb_strcut($linea, 0, 75, 'UTF-8');
            $partes[] = $corte;
            $linea = ' '.substr($linea, strlen($corte));
        }
        $partes[] = $linea;

        return implode("\r\n", $partes);
    }
}
