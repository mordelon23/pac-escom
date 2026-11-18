<?php

namespace App\Modulos\Compartido\Notificaciones;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notificaciones;
use Throwable;

/**
 * Envía notificaciones sin poner en riesgo la operación principal (tolerancia a fallos).
 * Si el servidor de correo no responde, la inscripción, cancelación o moderación ya quedó guardada:
 * el error se registra en la bitácora para reenviarlo después, en lugar de mostrar un error al usuario.
 */
class Notificador
{
    public static function enviar(mixed $destinatarios, Notification $notificacion): bool
    {
        return self::intentar(fn () => Notificaciones::send($destinatarios, $notificacion), class_basename($notificacion));
    }

    /** Ejecuta un envío (correo de restablecimiento, verificación…) y devuelve false si falló. */
    public static function intentar(callable $envio, string $descripcion): bool
    {
        try {
            $envio();

            return true;
        } catch (Throwable $e) {
            Log::error("No se pudo enviar: {$descripcion}", ['error' => $e->getMessage()]);
            report($e);

            return false;
        }
    }
}
