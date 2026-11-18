<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Mantenibilidad y eficiencia: fuera de producción, un acceso a una relación no cargada
        // (consulta N+1) o a un atributo inexistente lanza una excepción en lugar de pasar desapercibido.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Recuperabilidad: /up responde 500 si la base de datos no está disponible,
        // de modo que el hosting o un monitor externo detecte la falla.
        Event::listen(DiagnosingHealth::class, fn () => DB::select('select 1'));

        // En producción todas las URL (incluida la del código QR) se generan con HTTPS.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
