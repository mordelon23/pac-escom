@php($e = $estado instanceof \App\Modulos\Actividades\Enums\EstadoActividad ? $estado : \App\Modulos\Actividades\Enums\EstadoActividad::from($estado))
<span @class(['chip', 'bg-gray-100 text-gray-700' => $e->value === 'BORRADOR', 'bg-green-100 text-green-800' => $e->value === 'PUBLICADA',
    'bg-red-100 text-red-800' => $e->value === 'CANCELADA', 'bg-slate-200 text-slate-700' => $e->value === 'FINALIZADA'])>{{ $e->etiqueta() }}</span>
