<?php
namespace App\Config;

class Env {
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(?string $path = null): void {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Skip empty lines and comments
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }
                
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match("/^'([^']*)'$/", $value, $matches)) {
                        $value = $matches[1];
                    }
                    
                    self::$vars[$name] = $value;
                    
                    if (!array_key_exists($name, $_SERVER)) {
                        $_SERVER[$name] = $value;
                    }
                    if (!array_key_exists($name, $_ENV)) {
                        $_ENV[$name] = $value;
                    }
                    putenv("$name=$value");
                }
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null) {
        self::load();
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
