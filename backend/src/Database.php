<?php

class Database
{
    /**
     * Shared PDO connection for the current PHP request.
     *
     * The controller and service layers reuse this connection instead of
     * opening a separate database connection for every operation.
     */
    private static ?PDO $connection = null;

    /**
     * Creates or returns the shared PDO connection.
     *
     * Database values come from the environment and are never sent to the
     * browser. The DSN selcets MySQL, the configured database and utf8mb4
     * support. PDO exceptions are handled by the backend entrypoint, while
     * FETCH_ASSOC returns query rows as named arrays.
     */
    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $environment = loadEnvironment(__DIR__ . '/../.env');

        $required = [
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD'
        ];

        foreach ($required as $key) {
            if (!array_key_exists($key, $environment)) {
                throw new RuntimeException("Missing database environment variable: $key");
            }
        }

        self::$connection = new PDO(
            "mysql:host={$environment['DB_HOST']};dbname={$environment['DB_NAME']};charset=utf8mb4",
            $environment['DB_USER'],
            $environment['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return self::$connection;
    }
}
