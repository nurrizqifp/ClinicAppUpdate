<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Config/Env.php';
require_once __DIR__ . '/../src/Database/Connection.php';

use App\Config\Env;
use App\Database\Connection;

try {
    Env::load();
    
    $host = Env::get('DB_HOST', '127.0.0.1');
    $port = Env::get('DB_PORT', '3306');
    $user = Env::get('DB_USERNAME', 'root');
    $pass = Env::get('DB_PASSWORD', '');
    $dbName = Env::get('DB_DATABASE', 'clinicdb');
    $charset = Env::get('DB_CHARSET', 'utf8mb4');

    // Establish connection to server to verify/create database
    echo "Connecting to MySQL server at $host:$port...\n";
    $dsnServer = "mysql:host=$host;port=$port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdoServer = new PDO($dsnServer, $user, $pass, $options);
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci;");
    echo "Database `$dbName` verified or created.\n";

    // Now connect via the Connection wrapper
    $db = Connection::getConnection();
    
    // Create migrations history tracking table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS migrations_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Scan migration files
    $migrationDir = __DIR__ . '/migrations';
    $files = glob($migrationDir . '/*.php');
    sort($files);

    $opts = getopt("", ["rollback"]);
    $rollbackMode = isset($opts['rollback']);

    if ($rollbackMode) {
        echo "Starting rollback...\n";
        // Sort in reverse order for rollback
        rsort($files);
        foreach ($files as $file) {
            $name = basename($file);
            // Check if run
            $stmt = $db->prepare("SELECT id FROM migrations_history WHERE migration_name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                echo "Rolling back: $name\n";
                $migration = require $file;
                if (isset($migration['down'])) {
                    $db->exec($migration['down']);
                }
                $del = $db->prepare("DELETE FROM migrations_history WHERE migration_name = ?");
                $del->execute([$name]);
                echo "Rolled back successfully: $name\n";
            }
        }
        echo "Rollback completed.\n";
    } else {
        echo "Starting migrations...\n";
        foreach ($files as $file) {
            $name = basename($file);
            // Check if already run
            $stmt = $db->prepare("SELECT id FROM migrations_history WHERE migration_name = ?");
            $stmt->execute([$name]);
            if (!$stmt->fetch()) {
                echo "Migrating: $name\n";
                $migration = require $file;
                if (isset($migration['up'])) {
                    $db->exec($migration['up']);
                }
                $ins = $db->prepare("INSERT INTO migrations_history (migration_name) VALUES (?)");
                $ins->execute([$name]);
                echo "Migrated successfully: $name\n";
            } else {
                echo "Already migrated: $name (skipped)\n";
            }
        }
        echo "Migrations completed.\n";
    }
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
