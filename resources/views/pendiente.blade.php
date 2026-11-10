@extends('layouts.app', ['titulo' => $titulo])
@section('contenido')
<div class="tarjeta p-8 text-center">
    <h1 class="text-2xl font-bold text-guinda-700">{{ $titulo }}</h1>
    <p class="mx-auto mt-3 max-w-lg text-gray-600">Esta sección se construye en el <strong>{{ $sprint }}</strong>. El acceso por rol ya está protegido.</p>
</div>
@endsection
