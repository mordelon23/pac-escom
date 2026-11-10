<?php

namespace App\Modulos\Inscripciones\Notificaciones;

use App\Modulos\Inscripciones\Modelos\Inscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo de confirmación de inscripción (RF8). Se envía en segundo plano. */
class InscripcionConfirmada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Inscripcion $inscripcion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->inscripcion->actividad;
        $horarios = $a->horarios->map(fn ($h) => $h->dia_semana->etiqueta() . ' ' . $h->rango())->implode(', ');

        return (new MailMessage)
            ->subject('Inscripción confirmada: ' . $a->titulo)
            ->greeting('Hola, ' . $notifiable->nombre)
            ->line('Tu inscripción a **' . $a->titulo . '** quedó registrada.')
            ->line('Lugar: ' . $a->lugar)
            ->line('Periodo: ' . $a->fecha_inicio->format('d/m/Y') . ' al ' . $a->fecha_fin->format('d/m/Y'))
            ->line('Horario: ' . $horarios)
            ->line('Folio: ' . str_pad((string) $this->inscripcion->id, 6, '0', STR_PAD_LEFT))
            ->line('Puedes cancelar tu inscripción hasta 24 horas antes del inicio.')
            ->action('Ver mis actividades', url('/mis-actividades'));
    }
}
