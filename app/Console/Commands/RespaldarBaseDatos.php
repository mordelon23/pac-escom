<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Capacidad de recuperación: genera un respaldo de PostgreSQL con pg_dump y conserva los N más recientes.
 *   php artisan pac:respaldo
 * Restauración:  psql -h HOST -U USUARIO NUEVA_BASE < archivo.sql
 * Se programa a diario en routes/console.php (requiere «php artisan schedule:work» o un cron).
 */
class RespaldarBaseDatos extends Command
{
    protected $signature = 'pac:respaldo';

    protected $description = 'Crea un respaldo de la base de datos del PAC y elimina los más antiguos';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        if (($db['driver'] ?? null) !== 'pgsql') {
            $this->error('El respaldo automático solo está disponible para PostgreSQL.');

            return self::FAILURE;
        }
        $cfg = config('pac.respaldos');
        File::ensureDirectoryExists($cfg['ruta']);
        $archivo = $cfg['ruta'].'/pac-'.now()->format('Ymd-His').'.sql';

        $proceso = Process::env(['PGPASSWORD' => (string) $db['password']])->timeout(300)->run([
            $cfg['pg_dump'], '--host='.$db['host'], '--port='.$db['port'], '--username='.$db['username'],
            '--no-owner', '--no-privileges', '--file='.$archivo, $db['database'],
        ]);
        if ($proceso->failed()) {
            @unlink($archivo);
            $this->error('No se pudo crear el respaldo: '.trim($proceso->errorOutput() ?: 'pg_dump no está instalado o no se encontró.'));
            $this->line('Sugerencia: instala el cliente de PostgreSQL o define PAC_PG_DUMP con la ruta de pg_dump.');

            return self::FAILURE;
        }

        $respaldos = collect(File::glob($cfg['ruta'].'/pac-*.sql'))->sortDesc()->values();
        $respaldos->slice($cfg['conservar'])->each(fn ($viejo) => File::delete($viejo));

        $this->info('Respaldo creado: '.$archivo.' ('.round(filesize($archivo) / 1024, 1).' KB)');
        $this->line('Respaldos conservados: '.min($respaldos->count(), $cfg['conservar']));

        return self::SUCCESS;
    }
}
