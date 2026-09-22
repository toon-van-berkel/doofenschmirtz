<?php

class Router
{
    public function __construct(
        private array $routes
    ) {}

    public function dispatch(): void
    {
        /*
            Routes are matched by HTTP method and normalized path, then delegated
            to the controller action declared in config/routes.php.
        */
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $route = "$method $path";

        if (!isset($this->routes[$route])) {
            http_response_code(404);

            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'message' => 'Route not found'
            ]);

            return;
        }

        [$controller, $action] = $this->routes[$route];

        $instance = new $controller();

        $instance->$action();
    }
}
