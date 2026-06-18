<?php
namespace App;

class Logger {
    public static function log($level, $message) {
        $logDir = BASE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    public static function info($message) {
        self::log('INFO', $message);
    }

    public static function error($message) {
        self::log('ERROR', $message);
    }
}
