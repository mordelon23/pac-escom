<?php

namespace App\Modulos\Actividades\Notificaciones;

use App\Modulos\Actividades\Modelos\Actividad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** RB8: aviso a los inscritos cuando se cancela una actividad. */
class ActividadCancelada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Actividad $actividad) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Actividad cancelada: '.$this->actividad->titulo)
            ->greeting('Hola, '.$notifiable->nombre)
            ->line("Lamentamos informarte que la actividad «{$this->actividad->titulo}» fue cancelada.")
            ->line('Tu inscripción se canceló automáticamente y ya no cuenta para tu límite de '.config('pac.reglas.max_inscripciones_activas').' actividades.')
            ->action('Explorar otras actividades', url('/actividades'));
    }
}
