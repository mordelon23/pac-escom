<?php

/*
| Configuración propia del Portal de Actividades Culturales.
| Los valores de las reglas de negocio se concentran aquí para que un cambio de política
| (por ejemplo, permitir 6 inscripciones) se haga en el .env sin modificar el código (mantenibilidad).
*/

return [
    // Si es false (maqueta o hosting sin correo), las cuentas nuevas se marcan como verificadas.
    'verificar_correo' => env('PAC_VERIFICAR_CORREO', false),

    // Siembra datos de demostración aunque el entorno no sea local (útil en el hosting de la maqueta).
    'datos_demo' => env('PAC_DATOS_DEMO', false),

    'reglas' => [
        'max_inscripciones_activas' => (int) env('PAC_MAX_INSCRIPCIONES', 5),     // RB6
        'porcentaje_aviso_cupo' => (int) env('PAC_AVISO_CUPO', 90),               // RB19
        'horas_minimas_cancelacion' => (int) env('PAC_HORAS_CANCELACION', 24),    // RB9
        'segundos_ventana_qr' => (int) env('PAC_SEGUNDOS_QR', 30),                // RB20
        'intentos_por_minuto' => (int) env('PAC_INTENTOS_LOGIN', 5),              // RB24
        'minutos_bloqueo' => (int) env('PAC_MINUTOS_BLOQUEO', 5),                 // RB24
        'intentos_para_sugerir_restablecer' => 3,                                 // RB12
    ],

    // Respaldos de la base de datos (comando pac:respaldo).
    'respaldos' => [
        'ruta' => env('PAC_RESPALDOS_RUTA', storage_path('app/respaldos')),
        'conservar' => (int) env('PAC_RESPALDOS_CONSERVAR', 7),
        'pg_dump' => env('PAC_PG_DUMP', 'pg_dump'),
    ],

    // Cabeceras de seguridad (Content-Security-Policy). Se puede desactivar solo para depurar.
    'csp' => env('PAC_CSP', true),
];
