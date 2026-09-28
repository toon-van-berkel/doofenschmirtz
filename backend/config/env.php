<?php

/**
 * Loads simple KEY=VALUE configuration from a local environment file.
 *
 * Database credentials stay outside PHP source so they are not committed to
 * Git or copied into the public frontend. Production stores this file at
 * htdocs/_backend/.env. This smal loader is intentionally dependency-free;
 * it does not support multiline values, interpolation or advanced dotenv
 * quoting rules.
 *
 * Empty lines, comments and lines without '=' are ignored. Other lines are
 * split once into a variable name and value. Process environment variables
 * override values read from the file.
 *
 * @return array<string, string>
 */

function loadEnvironment(string $file): array
{
    $values = [];

    if (is_readable($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || substr($line, 0, 1) === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name !== '') {
                $values[$name] = trim($value, " \t\r\n\"");
            }
        }
    }

    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'APP_ENV'] as $name) {
        $environmentValue = getenv($name);

        if ($environmentValue !== false) {
            $values[$name] = $environmentValue;
        }
    }

    return $values;
}
