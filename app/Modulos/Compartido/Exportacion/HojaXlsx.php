<?php

namespace App\Modulos\Compartido\Exportacion;

use RuntimeException;
use ZipArchive;

/**
 * Generador mínimo de hojas de cálculo .xlsx (Office Open XML, ISO/IEC 29500) sin dependencias externas.
 * Produce un libro con una hoja: título, subtítulo, encabezados en negritas con fondo guinda,
 * números como números (Excel puede sumarlos y graficarlos) y una fila de totales opcional.
 */
class HojaXlsx
{
    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, array<int, string|int|float|null>>  $filas
     * @param  array<int, string|int|float|null>|null  $totales
     * @param  array<int, int>  $anchos  ancho de cada columna en caracteres
     */
    public static function crear(string $titulo, string $subtitulo, array $encabezados, array $filas, ?array $totales = null, array $anchos = []): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Falta la extensión zip de PHP (en Debian/Ubuntu: sudo apt install php-zip).');
        }
        $ruta = tempnam(sys_get_temp_dir(), 'pac-xlsx');
        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::tipos());
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::hoja($titulo, $subtitulo, $encabezados, $filas, $totales, $anchos));
        $zip->close();
        $contenido = file_get_contents($ruta);
        @unlink($ruta);

        return $contenido;
    }

    // Estilos: 0 normal · 1 título · 2 subtítulo · 3 encabezado · 4 total
    private static function hoja(string $titulo, string $subtitulo, array $encabezados, array $filas, ?array $totales, array $anchos): string
    {
        $n = count($encabezados);
        $cols = '';
        foreach (range(1, $n) as $i) {
            $w = $anchos[$i - 1] ?? 14;
            $cols .= "<col min=\"{$i}\" max=\"{$i}\" width=\"{$w}\" customWidth=\"1\"/>";
        }
        $xml = '';
        $r = 1;
        $xml .= self::fila($r++, [$titulo], 1);
        $xml .= self::fila($r++, [$subtitulo], 2);
        $r++;
        $filaEncabezado = $r;
        $xml .= self::fila($r++, $encabezados, 3);
        foreach ($filas as $f) {
            $xml .= self::fila($r++, $f, 0);
        }
        if ($totales !== null) {
            $xml .= self::fila($r++, $totales, 4);
        }
        $ultima = self::columna($n);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$filaEncabezado.'" topLeftCell="A'.($filaEncabezado + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            ."<cols>{$cols}</cols><sheetData>{$xml}</sheetData>"
            .'<autoFilter ref="A'.$filaEncabezado.':'.$ultima.($filaEncabezado + count($filas)).'"/>'
            .'</worksheet>';
    }

    private static function fila(int $r, array $valores, int $estilo): string
    {
        $celdas = '';
        foreach (array_values($valores) as $i => $v) {
            $ref = self::columna($i + 1).$r;
            if ($v === null || $v === '') {
                $celdas .= "<c r=\"{$ref}\" s=\"{$estilo}\"/>";
            } elseif (is_int($v) || is_float($v)) {
                $celdas .= "<c r=\"{$ref}\" s=\"{$estilo}\"><v>{$v}</v></c>";
            } else {
                $texto = htmlspecialchars(self::limpiar((string) $v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $celdas .= "<c r=\"{$ref}\" s=\"{$estilo}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">{$texto}</t></is></c>";
            }
        }

        return "<row r=\"{$r}\">{$celdas}</row>";
    }

    /** Evita la inyección de fórmulas (CSV/Formula injection, OWASP) y caracteres no válidos en XML. */
    private static function limpiar(string $v): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v);

        return preg_match('/^[=+\-@]/', $v) ? "'".$v : $v;
    }

    private static function columna(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    private static function tipos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private static function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><color rgb="FF6C1D45"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF6C1D45"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top style="medium"><color rgb="FF6C1D45"/></top><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
