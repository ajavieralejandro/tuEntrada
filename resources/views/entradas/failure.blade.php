@extends('layouts.app')

@section('content')
<div class="container text-center">
    <h1 class="text-danger">¡Error en el pago!</h1>
    <p>No se pudo procesar tu pago. Puedes intentar nuevamente.</p>
    <a href="{{ route('entradas.comprar') }}" class="btn btn-primary">Volver a comprar</a>
</div>
@endsection
