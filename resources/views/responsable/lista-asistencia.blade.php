@extends('layouts.app', ['titulo' => 'Lista de asistencia'])
@section('contenido')
<a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('responsable.inicio') }}" class="text-sm text-guinda-700 hover:underline">← Volver</a>
<div class="mt-2 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">Lista de asistencia</h1>
        <p class="mt-1 text-gray-600">{{ $actividad->titulo }} · {{ $actividad->categoria?->nombre }} · Responsable: {{ $actividad->responsable?->nombreCompleto() ?? '—' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="btn-secundario" href="{{ request()->fullUrlWithQuery(['formato' => 'xlsx']) }}">Descargar Excel (.xlsx)</a>
        <a class="btn-secundario" href="{{ request()->fullUrlWithQuery(['formato' => 'pdf']) }}">Descargar PDF</a>
    </div>
</div>

<p class="mt-4 text-sm text-gray-600">
    <span class="font-semibold text-emerald-700">✓</span> asistió con QR ·
    <span class="font-semibold text-amber-700">M</span> registro manual (contingencia) ·
    <span class="text-gray-500">—</span> falta
</p>

@if ($sesiones->isEmpty())
    <div class="tarjeta mt-4 p-8 text-center text-gray-600">Aún no se han realizado sesiones de asistencia para esta actividad.</div>
@elseif ($alumnos->isEmpty())
    <div class="tarjeta mt-4 p-8 text-center text-gray-600">La actividad no tiene alumnos inscritos.</div>
@else
    <div class="tarjeta mt-4 overflow-x-auto" tabindex="0" role="region" aria-label="Lista de asistencia (desplazable)">
        <table class="min-w-full text-sm">
            <caption class="sr-only">Asistencia de {{ $alumnos->count() }} alumnos en {{ $sesiones->count() }} sesiones</caption>
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th scope="col" class="sticky left-0 bg-gray-50 px-3 py-2">Alumno</th>
                    @foreach ($sesiones as $s)
                        <th scope="col" class="px-2 py-2 text-center whitespace-nowrap">{{ $s->fecha->format('d/m') }}</th>
                    @endforeach
                    <th scope="col" class="px-3 py-2 text-right">Total</th>
                    <th scope="col" class="px-3 py-2 text-right">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($alumnos as $a)
                    <tr>
                        <th scope="row" class="sticky left-0 bg-white px-3 py-2 text-left font-normal whitespace-nowrap">
                            <span class="font-medium">{{ $a->nombre }}</span> <span class="text-gray-500">{{ $a->matricula }}</span>
                        </th>
                        @foreach ($a->celdas as $m)
                            <td class="px-2 py-2 text-center">
                                @if ($m === 'QR')<span class="font-semibold text-emerald-700" title="QR">✓</span>
                                @elseif ($m === 'MANUAL')<span class="font-semibold text-amber-700" title="Manual">M</span>
                                @else<span class="text-gray-400" title="Falta">—</span>@endif
                            </td>
                        @endforeach
                        <td class="px-3 py-2 text-right">{{ $a->asistencias }}/{{ $sesiones->count() }}</td>
                        <td class="px-3 py-2 text-right font-medium">{{ $a->porcentaje }} %</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
