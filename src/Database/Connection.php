<?php
namespace App\Database;

use App\Config\Env;
use App\Logger;
use PDO;
use PDOException;

class Connection {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Load environment configuration if not already loaded
            Env::load();

            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '3306');
            $db   = Env::get('DB_DATABASE', 'clinicdb');
            $user = Env::get('DB_USERNAME', 'root');
            $pass = Env::get('DB_PASSWORD', '');
            $charset = Env::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                self::runMigrations(self::$instance);
            } catch (PDOException $e) {
                Logger::error("Database connection failure: " . $e->getMessage());
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$instance;
    }

    private static function runMigrations(PDO $db): void {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS migrations_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $migrationDir = BASE_PATH . '/database/migrations';
            if (is_dir($migrationDir)) {
                $files = glob($migrationDir . '/*.php');
                if ($files) {
                    sort($files);
                    foreach ($files as $file) {
                        $name = basename($file);
                        $stmt = $db->prepare("SELECT id FROM migrations_history WHERE migration_name = ?");
                        $stmt->execute([$name]);
                        if (!$stmt->fetch()) {
                            $migration = require $file;
                            if (isset($migration['up'])) {
                                $db->exec($migration['up']);
                            }
                            $ins = $db->prepare("INSERT INTO migrations_history (migration_name) VALUES (?)");
                            $ins->execute([$name]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Logger::error("Auto-migration failed: " . $e->getMessage());
        }
    }
}
