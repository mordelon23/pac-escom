<?php

namespace App\Modulos\Asistencia\Servicios;

use App\Modulos\Asistencia\Modelos\SesionAsistencia;

/**
 * Genera y valida los tokens del código QR dinámico (sección 22.6 del plan).
 *
 *   ventana = floor(tiempo_unix / segundos_ventana)   (30 s por omisión, config pac.reglas)
 *   token   = HMAC-SHA256(secreto_de_la_sesion, id_sesion | ventana)
 *
 * Se acepta la ventana actual y la anterior para tolerar retrasos de red (RB20).
 */
class ServicioTokenQR
{
    public const VENTANA_PREDETERMINADA = 30;

    private readonly int $ventana;

    /** La duración se toma de config/pac.php; las pruebas unitarias pueden fijarla sin cargar Laravel. */
    public function __construct(?int $segundosVentana = null)
    {
        $this->ventana = max(10, $segundosVentana ?? (int) config('pac.reglas.segundos_ventana_qr', self::VENTANA_PREDETERMINADA));
    }

    public function segundosVentana(): int
    {
        return $this->ventana;
    }

    public function ventanaActual(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? now()->getTimestamp(), $this->ventana);
    }

    public function generar(SesionAsistencia $sesion, ?int $timestamp = null): string
    {
        return $this->firmar($sesion, $this->ventanaActual($timestamp));
    }

    public function validar(SesionAsistencia $sesion, string $token, ?int $timestamp = null): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        $ventana = $this->ventanaActual($timestamp);
        foreach ([$ventana, $ventana - 1] as $v) {
            if (hash_equals($this->firmar($sesion, $v), $token)) {   // comparación en tiempo constante
                return true;
            }
        }

        return false;
    }

    /** Segundos que le quedan al token vigente (para la cuenta regresiva en pantalla). */
    public function segundosRestantes(?int $timestamp = null): int
    {
        $t = $timestamp ?? now()->getTimestamp();

        return $this->ventana - ($t % $this->ventana);
    }

    public static function nuevoSecreto(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function firmar(SesionAsistencia $sesion, int $ventana): string
    {
        return hash_hmac('sha256', $sesion->id.'|'.$ventana, $sesion->secreto_hmac);
    }
}
