<?php
// Basic front controller wiring your routes.php.
// Assumes config.php and routes.php live at plugin root beside this www/ directory.
$root = realpath(__DIR__ . '/..');
require_once $root . '/config.php';
require_once $root . '/routes.php';

// A minimalist router shim: expect $router to be defined by routes.php
if (!isset($router)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error'=>'router not initialized']);
    exit;
}

// Dispatch current request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

try {
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error'=>'unhandled exception','detail'=>$e->getMessage()]);
}
