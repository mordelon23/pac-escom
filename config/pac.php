<?php

return [
    // Si es false (maqueta o hosting sin correo), las cuentas nuevas se marcan como verificadas.
    'verificar_correo' => env('PAC_VERIFICAR_CORREO', false),

    // Siembra datos de demostración aunque el entorno no sea local (útil en el hosting de la maqueta).
    'datos_demo' => env('PAC_DATOS_DEMO', false),
];
