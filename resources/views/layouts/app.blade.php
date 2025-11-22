<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jimi Upload')</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background:#fff; color:#222; margin:0; }
        .nav { display:flex; gap:12px; padding:12px 16px; border-bottom:1px solid #e5e5e5; background:#f9f9f9; }
        .nav a { color:#222; text-decoration:none; padding:6px 10px; border:1px solid #e0e0e0; background:#fff; }
        .container { padding:16px; }
        .card { background:#fff; border:1px solid #e5e5e5; padding:12px; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border:1px solid #e5e5e5; padding:6px 8px; text-align:left; }
        th { background:#f7f7f7; }
        details summary { cursor:pointer; padding:6px; background:#f7f7f7; border:1px solid #e5e5e5; }
        .muted { color:#666; }
        .btn { background:#f5f5f5; border:1px solid #e0e0e0; color:#222; padding:6px 10px; cursor:pointer; text-decoration:none; display:inline-block; }
        .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
    </style>
    @yield('head')
</head>
<body>
    <div class="nav">
        <a href="{{ url('/') }}">Inicio</a>
        <a href="{{ url('/historico') }}">Histórico</a>
        <a href="{{ url('/comandos') }}">Enviar comandos</a>
    </div>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>


