<?php

class HealthController
{
    /**
     * Lightweight deployment health check.
     *
     * This intentionally does not query MySQL. A 200 response proves that
     * Apache rewriting, the public API adapter, PHP and the router work.
     */
    public function show(): void
    {
        http_response_code(200);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'status' => 'ok'
        ]);
    }
}
