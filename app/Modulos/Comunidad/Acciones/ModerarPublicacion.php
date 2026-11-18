<?php

namespace App\Modulos\Comunidad\Acciones;

use App\Modulos\Compartido\Auditoria\Auditor;
use App\Modulos\Compartido\Excepciones\ReglaNegocioException;
use App\Modulos\Compartido\Notificaciones\Notificador;
use App\Modulos\Comunidad\Enums\EstadoModeracion;
use App\Modulos\Comunidad\Modelos\Publicacion;
use App\Modulos\Comunidad\Notificaciones\PublicacionRechazada;
use App\Modulos\Identidad\Modelos\Usuario;

/** CU13 — Moderar publicaciones del muro (RB16). */
class ModerarPublicacion
{
    public function aprobar(Usuario $coordinador, Publicacion $p): void
    {
        $this->verificarPendiente($p);
        $p->update(['estado_moderacion' => EstadoModeracion::APROBADA, 'moderada_por' => $coordinador->id]);
        Auditor::registrar('publicacion.aprobar', 'publicaciones', $p->id);
    }

    public function rechazar(Usuario $coordinador, Publicacion $p, string $motivo): void
    {
        $this->verificarPendiente($p);
        $p->update(['estado_moderacion' => EstadoModeracion::RECHAZADA, 'moderada_por' => $coordinador->id, 'motivo_rechazo' => $motivo]);
        Auditor::registrar('publicacion.rechazar', 'publicaciones', $p->id, ['motivo' => $motivo]);
        Notificador::enviar($p->autor, new PublicacionRechazada($p));
    }

    private function verificarPendiente(Publicacion $p): void
    {
        if ($p->estado_moderacion !== EstadoModeracion::PENDIENTE) {
            throw new ReglaNegocioException('RB16', 'La publicación ya fue moderada.');
        }
    }
}
