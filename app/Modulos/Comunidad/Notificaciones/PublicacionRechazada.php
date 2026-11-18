<?php

namespace App\Modulos\Comunidad\Notificaciones;

use App\Modulos\Comunidad\Modelos\Publicacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicacionRechazada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Publicacion $publicacion) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu publicación en el muro no fue aprobada')
            ->line('Tu publicación sobre «'.$this->publicacion->actividad->titulo.'» no fue aprobada.')
            ->line('Motivo: '.$this->publicacion->motivo_rechazo)
            ->action('Ir al muro', url('/muro'));
    }
}
