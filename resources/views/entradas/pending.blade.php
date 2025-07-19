@extends('layouts.app')

@section('content')
<div class="container text-center">
    <h1 class="text-warning">Pago pendiente</h1>
    <p>Tu pago está en proceso. Te notificaremos cuando se acredite.</p>
    <a href="{{ route('entradas.comprar') }}" class="btn btn-primary">Volver al inicio</a>
</div>
@endsection
