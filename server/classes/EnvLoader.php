<?php

/**
 * Class EnvLoader
 * Loads environment variables from .env file
 * @package server\classes
 * @author Yago Faria
 */

class EnvLoader {
    public static function load($path) {
        if (!file_exists($path)) {
            throw new Exception("Arquivo .env não encontrado em: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            putenv("$name=$value");
            $_ENV[$name] = trim($value);
        }
    }
}
