<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', __DIR__);

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
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
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(BASE_PATH . '/.env');

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = BASE_PATH . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Setup custom error and exception handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $message = "[$errno] $errstr in $errfile on line $errline";
    \App\Logger::error($message);
    if (getenv('APP_ENV') === 'development') {
        echo "<div style='border:1px solid #ff0000; padding:10px; margin:10px;'><strong>Error:</strong> $message</div>";
    }
    return true;
});

set_exception_handler(function($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    \App\Logger::error($message . "\n" . $exception->getTraceAsString());
    if (getenv('APP_ENV') === 'development') {
        echo "<div style='border:1px solid #ff0000; padding:10px; margin:10px;'><strong>Exception:</strong> $message</div>";
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo "An unexpected error occurred. Please contact the administrator.";
    }
});
