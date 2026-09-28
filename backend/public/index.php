<?php

require_once __DIR__ . '/../config/env.php';

// --------------------------------------------------
// 1. Load application configuration
// --------------------------------------------------

/**
 * Production configuration is stored in `_backend/.env`.
 *
 * Keeping database credentials in this file instead of PHP source code
 * prevents passwords and other secrets from being committed to Git.
 */
$environment = loadEnvironment(__DIR__ . '/../.env');

$host = $_SERVER['HTTP_HOST'] ?? '';

/**
 * Local development uses a different configuration from production.
 *
 * `doofenschmirtz.test`, localhost and 127.0.0.1 are treated as
 * development environments automatically. APP_ENV can override this
 * when it is explicitly configured in the .env file.
 */
$isLocalHost =
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($host, 'doofenschmirtz.test') !== false;

$appEnvironment =
    $environment['APP_ENV']
    ?? ($isLocalHost ? 'development' : 'production');


// --------------------------------------------------
// 2. Configure error handling
// --------------------------------------------------

/**
 * PHP error details should never be printed on a production website.
 *
 * Exceptions can contain filesystem paths, SQL information or other
 * implementation details. The client therefore receives only a generic
 * JSON response when an unexpected error occurs.
 */
if ($appEnvironment === 'production') {
    ini_set('display_errors', '0');
}

set_exception_handler(
    function (Throwable $exception) use ($appEnvironment): void {
        // Development errors are written to the PHP error log so they
        // can still be inspected without exposing them in the API response.
        if ($appEnvironment !== 'production') {
            error_log($exception->getMessage());
        }

        http_response_code(500);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
    }
);


// --------------------------------------------------
// 3. Configure development CORS
// --------------------------------------------------

/**
 * During development the frontend and backend run on different origins:
 *
 * Frontend: http://localhost:5500
 * Backend:  https://doofenschmirtz.test
 *
 * Browsers block these requests unless the backend explicitly allows the
 * frontend origin. Production does not require this because the frontend
 * and API both run on https://doofenschmirtz.b0i.eu.
 */
$allowedOrigins = [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");

    // Required because authentication is based on a PHP session cookie.
    header('Access-Control-Allow-Credentials: true');

    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}

/**
 * Browsers may send an OPTIONS request before the real cross-origin
 * request. This is called a CORS preflight. No controller needs to run
 * for this request, so it can end here with HTTP 204.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


// --------------------------------------------------
// 4. Configure authentication sessions
// --------------------------------------------------

/**
 * Authentication uses normal PHP sessions.
 *
 * After login the backend stores the user's ID in $_SESSION. The browser
 * only receives a session cookie containing the session identifier.
 *
 * Secure:
 *   Only sends the cookie over HTTPS in production.
 *
 * HttpOnly:
 *   Prevents frontend JavaScript from reading the session cookie.
 *
 * SameSite=Lax:
 *   Appropriate for the same-origin production setup and helps reduce
 *   unwanted cross-site cookie requests.
 */
session_set_cookie_params([
    'path' => '/',
    'secure' =>
        $appEnvironment === 'production'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => $appEnvironment === 'production' ? 'Lax' : 'None',
]);

session_start();


// --------------------------------------------------
// 5. Load backend components
// --------------------------------------------------

/**
 * This project intentionally uses a small manual PHP architecture rather
 * than a framework or Composer autoloader.
 *
 * Request flow:
 *
 * Router
 *   → Controller
 *   → Service
 *   → Database
 */
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/HealthController.php';


// --------------------------------------------------
// 6. Dispatch the request
// --------------------------------------------------

/**
 * routes.php maps an HTTP method and path to a controller action.
 *
 * The public production URL may be:
 *
 *   POST /api/auth/login
 *
 * but the deployment adapter removes `/api` before this file runs.
 * The backend router therefore receives:
 *
 *   POST /auth/login
 */
$routes = require __DIR__ . '/../config/routes.php';

$router = new Router($routes);
$router->dispatch();