<?php
declare(strict_types=1);
http_response_code(403);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <main class="container error-page">
        <h1>403</h1>
        <p>No tienes permiso para acceder a esta sección.</p>
        <p><a class="button button-primary" href="/dashboard">Volver al dashboard</a></p>
    </main>
</body>
</html>
