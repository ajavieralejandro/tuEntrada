<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Entradas')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Mi Evento</a>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-4">
        <small>&copy; {{ date('Y') }} Mi Evento Especial</small>
    </footer>

</body>
</html>
