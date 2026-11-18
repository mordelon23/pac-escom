<?php

namespace Tests\Feature\Calidad;

use App\Modulos\Identidad\Enums\RolSistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaDatos;
use Tests\TestCase;

/** ISO 9126 · Funcionalidad › Seguridad (ISO/IEC 27002: 8.15, 8.26, 8.28). */
class SeguridadTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_cabeceras_de_seguridad_en_todas_las_respuestas(): void
    {
        $r = $this->get('/login')->assertOk();
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('X-Frame-Options', 'DENY');
        $r->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', $r->headers->get('Permissions-Policy'));
        $csp = $r->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("'unsafe-inline' ", explode('script-src', $csp)[1] ?? '');
    }

    public function test_las_pantallas_no_usan_javascript_en_linea(): void
    {
        // Requisito para que la CSP sin 'unsafe-inline' no rompa la interfaz.
        $alumno = $this->usuario();
        $a = $this->actividad(['fecha_inicio' => now()->subDay()->toDateString()]);
        $coord = $this->usuario(RolSistema::SUPERADMINISTRADOR);
        $paginas = [[$alumno, '/inicio'], [$alumno, "/actividades/{$a->id}"], [$alumno, '/mis-actividades'], [$alumno, '/ayuda'],
            [$coord, "/coordinacion/actividades/{$a->id}"], [$coord, '/coordinacion/actividades/create'], [$coord, '/coordinacion/usuarios'],
            [$coord, '/coordinacion/reportes?formato=imprimir']];
        foreach ($paginas as [$u, $url]) {
            $html = $this->actingAs($u)->get($url)->assertOk()->getContent();
            $this->assertDoesNotMatchRegularExpression('/\son(click|submit|change|input|load)=/i', $html, "JavaScript en línea en {$url}");
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $html, "<script> en línea en {$url}");
        }
    }

    public function test_inicio_de_sesion_fallido_y_exitoso_quedan_en_auditoria(): void
    {
        $u = $this->usuario();
        $this->post('/login', ['correo' => $u->correo, 'password' => 'Incorrecta1']);
        $this->post('/login', ['correo' => $u->correo, 'password' => 'Secreta123']);
        $this->assertDatabaseHas('auditoria', ['accion' => 'sesion.fallida', 'entidad_id' => $u->id]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'sesion.inicio', 'entidad_id' => $u->id]);
    }

    public function test_csv_y_excel_neutralizan_formulas(): void
    {
        $this->actividad(['titulo' => '=HYPERLINK("http://x")', 'fecha_inicio' => now()->subDay()->toDateString()]);
        $this->actingAs($this->usuario(RolSistema::COORDINADOR));
        $desde = now()->subMonth()->toDateString();
        $csv = $this->get("/coordinacion/reportes?desde={$desde}&formato=csv")->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $xlsx = $this->get("/coordinacion/reportes?desde={$desde}&formato=xlsx")->getContent();
        $this->assertStringContainsString("'=HYPERLINK", self::hojaXlsx($xlsx));
    }

    public static function hojaXlsx(string $binario): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($ruta, $binario);
        $zip = new \ZipArchive;
        $zip->open($ruta);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($ruta);

        return html_entity_decode((string) $xml, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
