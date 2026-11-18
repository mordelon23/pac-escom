<?php

namespace App\Modulos\Inscripciones\Notificaciones;

use App\Modulos\Actividades\Modelos\Actividad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** RB19: aviso al coordinador cuando la ocupación alcanza el 90 %. */
class CupoPorAgotarse extends Notification implements ShouldQueue
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
            ->subject('Cupo al '.$this->actividad->porcentajeOcupacion().' %: '.$this->actividad->titulo)
            ->line("La actividad «{$this->actividad->titulo}» tiene {$this->actividad->cupo_ocupado} de {$this->actividad->cupo_maximo} lugares ocupados.")
            ->action('Ver actividad', url('/coordinacion/actividades/'.$this->actividad->id));
    }
}
