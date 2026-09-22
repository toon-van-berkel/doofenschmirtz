<?php

$allowedOrigins = [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
];

/*
    Defines which frontend origins may communicate with the backend.

    Credentials are allowed because authentication uses PHP session cookies.
    Production origins should be added explicitly instead of allowing every origin.
*/
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
    Configures the session cookie used to keep users authenticated.

    These settings are intended for the current HTTPS backend and cross-origin
    frontend setup. They may need to change when the application is deployed.
*/
session_set_cookie_params([
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None',
]);

session_start();

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

$routes = require __DIR__ . '/../config/routes.php';

$router = new Router($routes);
$router->dispatch();