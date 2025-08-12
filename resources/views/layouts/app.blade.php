@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Listado de Entradas</h1>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Evento</th>
                <th>Código QR</th>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Fecha</th>
                <th>Válido</th>
                <th>Usada</th>
                <th>QR</th>
                <th>Fecha creación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entradas as $entrada)
            <tr>
                <td>{{ $entrada->id }}</td>
                <td>{{ $entrada->evento }}</td>
                <td>{{ $entrada->codigo_qr }}</td>
                <td>{{ $entrada->nombre }}</td>
                <td>{{ $entrada->dni }}</td>
                <td>{{ $entrada->fecha }}</td>
                <td>{{ $entrada->valido ? 'Sí' : 'No' }}</td>
                <td>{{ $entrada->usada ? 'Sí' : 'No' }}</td>
                <td>
                    @if($entrada->qr_path)
                        <img src="{{ asset($entrada->qr_path) }}" alt="QR" width="80" height="80">
                    @else
                        No disponible
                    @endif
                </td>
                <td>{{ $entrada->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Paginación -->
    <div>
        {{ $entradas->links() }}
    </div>
</div>
@endsection
