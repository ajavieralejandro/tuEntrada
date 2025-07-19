<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Pago Exitoso</title></head>
<body>
<h1>Pago exitoso</h1>
<p>Gracias por tu compra.</p>

<h3>Datos de MercadoPago:</h3>
<pre>{{ print_r($data, true) }}</pre>

<a href="{{ route('entradas.comprar') }}">Comprar otra entrada</a>
</body>
</html>
