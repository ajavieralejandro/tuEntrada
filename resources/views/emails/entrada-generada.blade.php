<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Entrada para Mi Evento Especial</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2>Hola {{ $entrada['persona']['nombre'] }}!</h2>

    <p>Gracias por registrarte en <strong>Mi Evento Especial</strong>.</p>

    <p>Tu código único de entrada es:</p>
    <p style="font-size: 1.2em; font-weight: bold;">{{ $entrada['codigo'] }}</p>

    <p>Presenta este código QR en la entrada del evento:</p>

    <p>
        <img src="{{ $entrada['qr_url'] }}" alt="Código QR" style="width: 250px; height: auto; border: 1px solid #ccc;" />
    </p>

    <p>Fecha del evento: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>

    <p>¡Esperamos verte allí!</p>

    <hr>
    <small>Este es un correo automático, por favor no respondas.</small>
</body>
</html>
