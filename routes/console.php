<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo diario de la base de datos a las 02:30 (ver app/Console/Commands/RespaldarBaseDatos.php).
Schedule::command('pac:respaldo')->dailyAt('02:30')->withoutOverlapping();
