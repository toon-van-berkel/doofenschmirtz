<?php

/**
 * Maps HTTP method + backend path to a controller method.
 *
 * Production requests use /api/auth/login, but htdocs/api/index.php removes
 * /api before the backend starts. The router therefore matches
 * POST /auth/login here, not POST /api/auth/login.
 */
return [
    'GET /health' => [HealthController::class, 'show'],
    'POST /auth/register' => [AuthController::class, 'register'],
    'POST /auth/login' => [AuthController::class, 'login'],
    'GET /auth/me' => [AuthController::class, 'me'],
    'POST /auth/logout' => [AuthController::class, 'logout'],
];
