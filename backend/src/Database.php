<?php

class Database
{
    private static ?PDO $connection = null;

    /*
        Returns the shared PDO connection used by backend controllers and services.

        The connection is created lazily from the developer-specific configuration
        file and is reused for the lifetime of the request.
    */
    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require __DIR__ . '/../config/database.php';

        self::$connection = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return self::$connection;
    }
}