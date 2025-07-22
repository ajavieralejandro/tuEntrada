<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tus Entradas para Mi Evento Especial</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2>Hola {{ $nombre }}!</h2>

    <p>Gracias por registrarte en <strong>Mi Evento Especial</strong>.</p>
    <p>Aquí tienes tus entradas:</p>

    @foreach($entradas as $entrada)
    <div style="margin-bottom: 30px; border: 1px solid #eee; padding: 15px;">
        <h3>Entrada #{{ $loop->iteration }}</h3>
        <p>Código único: <strong>{{ $entrada['codigo'] }}</strong></p>
        <p>Para: {{ $entrada['persona']['nombre'] }}</p>

        <p>Presenta este código QR en la entrada:</p>
        <img src="{{ $entrada['qr_url'] }}" alt="Código QR" style="width: 250px; height: auto; border: 1px solid #ccc;" />
    </div>
    @endforeach

    <p>Total entradas: {{ count($entradas) }}</p>
    <p>Total pagado: ${{ $total }}</p>

    <p>Fecha del evento: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
    <p>¡Esperamos verte allí!</p>

    <hr>
    <small>Este es un correo automático, por favor no respondas.</small>
</body>
</html>
