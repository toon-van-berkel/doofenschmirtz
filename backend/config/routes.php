<?php

/*
    Maps HTTP method and path combinations to controller actions.

    Add new routes here only when the matching controller action already exists.
    Route definitions should stay here instead of being added directly to
    public/index.php.
*/
return [
    'POST /auth/register' => [AuthController::class, 'register'],
    'POST /auth/login' => [AuthController::class, 'login'],
    'GET /auth/me' => [AuthController::class, 'me'],
    'POST /auth/logout' => [AuthController::class, 'logout'],
];
