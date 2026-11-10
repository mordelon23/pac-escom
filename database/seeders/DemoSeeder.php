<?php

namespace Database\Seeders;

use App\Modulos\Actividades\Enums\EstadoActividad;
use App\Modulos\Actividades\Modelos\Actividad;
use App\Modulos\Actividades\Modelos\Categoria;
use App\Modulos\Identidad\Enums\RolSistema;
use App\Modulos\Identidad\Modelos\Rol;
use App\Modulos\Identidad\Modelos\Usuario;
use App\Modulos\Inscripciones\Acciones\InscribirAlumno;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración para la maqueta. Todas las contraseñas de prueba son «Pac2026demo».
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Pac2026demo';

    public function run(): void
    {
        Notification::fake();   // no enviar correos al sembrar
        $rol = fn (RolSistema $r) => Rol::where('clave', $r->value)->value('id');
        $usuario = function (RolSistema $r, string $nombre, string $apellidos, string $correo, ?string $boleta = null) use ($rol) {
            $u = Usuario::firstOrCreate(['correo' => $correo], [
                'rol_id' => $rol($r), 'nombre' => $nombre, 'apellidos' => $apellidos,
                'matricula' => $boleta, 'password' => self::PASSWORD,
            ]);
            $u->forceFill(['correo_verificado_en' => now()])->save();

            return $u;
        };

        $coord = $usuario(RolSistema::COORDINADOR, 'Laura', 'Méndez Ruiz', 'coordinacion@ipn.mx');
        $resp1 = $usuario(RolSistema::RESPONSABLE, 'Carlos', 'Hernández Vega', 'chernandez@ipn.mx');
        $resp2 = $usuario(RolSistema::RESPONSABLE, 'Ana', 'Torres Salinas', 'atorres@ipn.mx');
        $alumnos = collect([
            ['Juan', 'Pérez López', '2023630101'], ['María', 'García Soto', '2023630102'],
            ['Diego', 'Ramírez Cruz', '2022630203'], ['Sofía', 'Martínez Ortega', '2024630304'],
            ['Luis', 'Flores Medina', '2022630405'], ['Valeria', 'Castillo Romero', '2023630506'],
        ])->map(fn ($a, $i) => $usuario(RolSistema::ALUMNO, $a[0], $a[1], "alumno{$i}@alumno.ipn.mx", $a[2]));

        $cat = Categoria::pluck('id', 'nombre');
        $fin = now()->addMonths(3);
        $actividades = [
            ['Música', 'Taller de guitarra', 'Aprende acordes, rasgueos y canciones populares desde cero.', 'Sala de música, edificio de gobierno', 25, $resp1, [[1, '14:00', '16:00'], [3, '14:00', '16:00']]],
            ['Danza', 'Danza folklórica', 'Grupo representativo de danza folklórica mexicana.', 'Explanada principal', 30, $resp2, [[2, '13:00', '15:00'], [4, '13:00', '15:00']]],
            ['Teatro', 'Improvisación teatral', 'Juegos de improvisación para desarrollar expresión y confianza.', 'Auditorio A', 20, $resp1, [[5, '12:00', '14:00']]],
            ['Literatura', 'Círculo de lectura', 'Lectura y discusión de ciencia ficción latinoamericana.', 'Biblioteca, sala 2', 15, $resp2, [[3, '17:00', '18:30']]],
            ['Deportes', 'Ajedrez competitivo', 'Preparación para el torneo interpolitécnico.', 'Sala de usos múltiples', 16, $resp1, [[1, '10:00', '12:00'], [2, '10:00', '12:00'], [3, '10:00', '12:00'], [4, '10:00', '12:00'], [5, '10:00', '12:00']]],
            ['Artes visuales', 'Fotografía con celular', 'Composición, luz y edición básica para redes.', 'Laboratorio 3', 20, $resp2, [[4, '16:00', '18:00']]],
        ];

        foreach ($actividades as $k => [$categoria, $titulo, $desc, $lugar, $cupo, $resp, $horarios]) {
            // La mitad ya está en curso; la otra mitad inicia la próxima semana (permite probar la cancelación, RB9)
            $inicio = $k % 2 === 0 ? now()->startOfWeek() : now()->next('Monday');
            $a = Actividad::firstOrCreate(['titulo' => $titulo], [
                'categoria_id' => $cat[$categoria], 'responsable_id' => $resp->id, 'creada_por' => $coord->id,
                'descripcion' => $desc, 'lugar' => $lugar, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'cupo_maximo' => $cupo, 'imagen_ruta' => $this->imagenDemo($categoria), 'estado' => EstadoActividad::PUBLICADA,
            ]);
            if ($a->horarios()->doesntExist()) {
                foreach ($horarios as [$dia, $hi, $hf]) {
                    $a->horarios()->create(['dia_semana' => $dia, 'hora_inicio' => $hi, 'hora_fin' => $hf]);
                }
            }
        }

        // Algunas inscripciones de ejemplo
        $inscribir = app(InscribirAlumno::class);
        $ids = Actividad::orderBy('id')->pluck('id');
        foreach ($alumnos as $i => $alumno) {
            foreach ($ids->slice($i % 3, 2) as $id) {
                try { $inscribir->ejecutar($alumno, $id); } catch (\Throwable) { /* ya inscrito */ }
            }
        }
    }

    /** Genera una imagen SVG sencilla por categoría para no depender de archivos externos. */
    private function imagenDemo(string $categoria): string
    {
        $ruta = 'demo/' . \Illuminate\Support\Str::slug($categoria) . '.svg';
        $colores = ['Música' => '#6C1D45', 'Danza' => '#8E2A5C', 'Teatro' => '#4A1430', 'Literatura' => '#7A4A2E', 'Artes visuales' => '#2E5E6C', 'Deportes' => '#2E6C45'];
        $c = $colores[$categoria] ?? '#6C1D45';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 450"><rect width="800" height="450" fill="$c"/>
<circle cx="640" cy="110" r="160" fill="#ffffff" opacity=".08"/><circle cx="120" cy="400" r="220" fill="#ffffff" opacity=".06"/>
<text x="60" y="250" font-family="Arial, sans-serif" font-size="64" font-weight="700" fill="#ffffff">$categoria</text></svg>
SVG;
        Storage::disk('public')->put($ruta, $svg);

        return $ruta;
    }
}
