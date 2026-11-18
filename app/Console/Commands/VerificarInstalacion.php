<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Facilidad de instalación: revisa en un solo paso los requisitos del PAC y dice cómo corregir cada falla.
 *   php artisan pac:verificar
 */
class VerificarInstalacion extends Command
{
    protected $signature = 'pac:verificar';

    protected $description = 'Verifica que el entorno cumple los requisitos del Portal de Actividades Culturales';

    private int $fallas = 0;

    public function handle(): int
    {
        $this->info('Verificación de la instalación del PAC');
        $this->newLine();

        $this->revisar('PHP 8.3 o superior (tienes '.PHP_VERSION.')', version_compare(PHP_VERSION, '8.3.0', '>='),
            'Instala PHP 8.3+. Debian/Ubuntu: sudo apt install php-cli. Si tienes XAMPP, revisa con «which php» cuál se usa.');
        foreach (['pdo_pgsql' => 'php-pgsql', 'mbstring' => 'php-mbstring', 'intl' => 'php-intl', 'gd' => 'php-gd', 'zip' => 'php-zip', 'xml' => 'php-xml', 'curl' => 'php-curl'] as $ext => $paquete) {
            $this->revisar("Extensión {$ext}", extension_loaded($ext), "Debian/Ubuntu: sudo apt install {$paquete}. Windows: quita el ; de extension={$ext} en php.ini.");
        }
        $this->revisar('Archivo .env', file_exists(base_path('.env')), 'Ejecuta: cp .env.example .env');
        $this->revisar('Llave de la aplicación (APP_KEY)', filled(config('app.key')), 'Ejecuta: php artisan key:generate');

        $conexion = false;
        try {
            DB::select('select 1');
            $conexion = true;
        } catch (Throwable $e) {
            $detalle = str($e->getMessage())->before('(Connection')->limit(140);
        }
        $this->revisar('Conexión a PostgreSQL ('.config('database.connections.pgsql.database').')', $conexion,
            'Revisa DB_HOST, DB_DATABASE, DB_USERNAME y DB_PASSWORD en .env y que PostgreSQL esté encendido. '.($detalle ?? ''));
        if ($conexion) {
            $this->revisar('Tablas creadas (migraciones)', Schema::hasTable('actividades') && Schema::hasTable('asistencias'),
                'Ejecuta: php artisan migrate --seed');
            $this->revisar('Roles y permisos sembrados', Schema::hasTable('roles') && DB::table('roles')->count() >= 4,
                'Ejecuta: php artisan db:seed');
        }
        $this->revisar('Enlace de imágenes (public/storage)', is_link(public_path('storage')) || is_dir(public_path('storage')),
            'Ejecuta: php artisan storage:link');
        $this->revisar('Estilos y JavaScript compilados', file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')),
            'Ejecuta: npm install && npm run build');
        foreach (['storage/logs', 'storage/framework/sessions', 'bootstrap/cache'] as $dir) {
            $this->revisar("Permiso de escritura en {$dir}", is_writable(base_path($dir)), "Ejecuta: chmod -R u+w {$dir}");
        }

        if (app()->isProduction()) {
            $this->newLine();
            $this->line('<comment>Producción</comment>');
            $this->revisar('APP_DEBUG desactivado', ! config('app.debug'), 'Pon APP_DEBUG=false: con true se muestran detalles internos al público.');
            $this->revisar('URL con HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Usa APP_URL=https://… (el escáner de la cámara requiere HTTPS).');
            $this->revisar('Cookie de sesión segura', (bool) config('session.secure'), 'Pon SESSION_SECURE_COOKIE=true.');
            $this->revisar('Contraseña del superadministrador cambiada', env('PAC_SUPERADMIN_PASSWORD') !== 'CambiaEsto2026', 'Cambia PAC_SUPERADMIN_PASSWORD.');
        }

        $this->newLine();
        if ($this->fallas === 0) {
            $this->info('Todo listo. Inicia el sistema con: php artisan serve');

            return self::SUCCESS;
        }
        $this->error("Hay {$this->fallas} punto(s) por corregir.");

        return self::FAILURE;
    }

    private function revisar(string $que, bool $ok, string $solucion): void
    {
        $this->components->twoColumnDetail($que, $ok ? '<fg=green;options=bold>OK</>' : '<fg=red;options=bold>FALTA</>');
        if (! $ok) {
            $this->fallas++;
            $this->output->writeln('    <fg=yellow>→ '.$solucion.'</>');
        }
    }
}
