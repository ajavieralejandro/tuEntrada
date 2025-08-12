@extends('layouts.app') {{-- O el layout que uses --}}

@section('content')
<div class="container">
    <h1 class="mb-4">Entradas Vendidas</h1>

    @if($entradas->isEmpty())
        <div class="alert alert-info">No hay entradas vendidas todavía.</div>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Evento</th>
                    <th>Código QR</th>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Fecha</th>
                    <th>Válida</th>
                    <th>Usada</th>
                    <th>QR</th>
                    <th>Vendida el</th>
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
                        <td>{{ \Carbon\Carbon::parse($entrada->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $entrada->valido ? 'Sí' : 'No' }}</td>
                        <td>{{ $entrada->usada ? 'Sí' : 'No' }}</td>
                        <td>
                            @if($entrada->qr_path && file_exists(public_path('storage/' . $entrada->qr_path)))
                                <img src="{{ asset('storage/' . $entrada->qr_path) }}" alt="QR" width="80">
                            @else
                                Sin QR
                            @endif
                        </td>
                        <td>{{ $entrada->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Paginación --}}
        <div class="d-flex justify-content-center">
            {{ $entradas->links() }}
        </div>
    @endif
</div>
@endsection
