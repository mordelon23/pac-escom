<?php

namespace App\Modulos\Inscripciones\Notificaciones;

use App\Modulos\Actividades\Modelos\Actividad;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** RF8 — Recordatorio por correo 24 horas antes de que inicie la actividad. */
class RecordatorioActividad extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Actividad $actividad, public CarbonInterface $inicio) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->actividad;

        return (new MailMessage)
            ->subject('Mañana inicia: '.$a->titulo)
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Te recordamos que **'.$a->titulo.'** inicia el '.$this->inicio->translatedFormat('l j \d\e F').' a las '.$this->inicio->format('H:i').' h.')
            ->line('Lugar: '.$a->lugar)
            ->line('Para registrar tu asistencia, escanea el código QR que proyectará el responsable con tu teléfono.')
            ->action('Ver la actividad', url('/actividades/'.$a->id))
            ->line('Si ya no puedes asistir, cancela tu inscripción para liberar tu lugar.');
    }
}
