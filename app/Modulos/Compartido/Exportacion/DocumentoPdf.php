<?php

namespace App\Modulos\Compartido\Exportacion;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

/**
 * Genera PDF en el servidor a partir de una vista Blade (RF9), con dompdf.
 * Por seguridad no se permite cargar recursos remotos ni ejecutar PHP incrustado.
 */
class DocumentoPdf
{
    public static function desdeVista(string $vista, array $datos, bool $horizontal = false): string
    {
        $opciones = new Options;
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('isPhpEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');   // incluye acentos y ñ
        $opciones->set('chroot', resource_path());
        $opciones->set('tempDir', storage_path('framework/cache'));
        $opciones->set('fontCache', storage_path('framework/cache'));

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml(view($vista, $datos)->render(), 'UTF-8');
        $pdf->setPaper('letter', $horizontal ? 'landscape' : 'portrait');
        $pdf->render();
        $lienzo = $pdf->getCanvas();
        $lienzo->page_text($lienzo->get_width() - 100, $lienzo->get_height() - 24, 'Página {PAGE_NUM} de {PAGE_COUNT}', 'DejaVu Sans', 7, [0.4, 0.4, 0.4]);

        return $pdf->output();
    }

    public static function descarga(string $contenido, string $nombre): Response
    {
        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }
}
