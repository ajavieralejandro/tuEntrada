@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-center mb-8">Entradas Generadas</h1>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Resumen de Compra</h2>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                    {{ count($entradas) }} entradas
                </span>
            </div>
            <p class="text-gray-700"><span class="font-medium">Precio unitario:</span> ${{ number_format($precio_unitario, 2) }}</p>
            <p class="text-gray-700 mb-2"><span class="font-medium">Total:</span> ${{ number_format($total, 2) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($entradas as $entrada)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="font-bold text-lg">{{ $entrada['persona']['nombre'] }}</h3>
                    <p class="text-gray-600">{{ $entrada['persona']['email'] }}</p>
                    <p class="text-gray-600">DNI: {{ $entrada['persona']['dni'] }}</p>
                </div>
                <div class="p-4 flex flex-col items-center">
                    <!-- Mostrar el QR -->
<img src="{{ $entrada['qr_url'] }}" alt="Código QR" />
                    <p class="text-sm text-gray-500 text-center">
                        Escanea este código QR para verificar tu entrada
                    </p>
                </div>
                <!-- Opcional: mostrar datos del QR -->
                <div class="p-4 bg-gray-50">
                    <details class="text-sm">
                        <summary class="cursor-pointer text-blue-600">Ver datos técnicos</summary>
                        <pre class="mt-2 p-2 bg-gray-100 rounded overflow-x-auto">{{ json_encode(json_decode($entrada['qr_data']), JSON_PRETTY_PRINT) }}</pre>
                    </details>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ url('/') }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                Volver al inicio
            </a>
        </div>
    </div>
</div>
@endsection
