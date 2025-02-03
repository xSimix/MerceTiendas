<?php
$routes = [
    '/' => 'vistas/inicio_sesion.php',
    '/panel' => 'vistas/panel_control.php',
    '/flujo_caja' => 'vistas/flujo_caja.php',
    '/reportes' => 'vistas/reportes.php',
    '/registro' => 'vistas/registro.php'
];

$request = $_SERVER['REQUEST_URI'];
$basePath = '/flujo_caja';

if (array_key_exists(str_replace($basePath, '', $request), $routes)) {
    require __DIR__ . '/../' . $routes[str_replace($basePath, '', $request)];
} else {
    http_response_code(404);
    echo "Página no encontrada.";
}
?>
