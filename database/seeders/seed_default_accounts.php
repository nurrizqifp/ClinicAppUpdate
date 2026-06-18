<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Config/Env.php';
require_once __DIR__ . '/../../src/Database/Connection.php';

use App\Config\Env;
use App\Database\Connection;

function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateSecurePassword(): string {
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';
    
    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];
    
    $all = $upper . $lower . $numbers . $symbols;
    for ($i = 0; $i < 12; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($password);
}

function encryptNik(string $nik, string $keyHex): string {
    $key = hex2bin($keyHex);
    $ivLength = openssl_cipher_iv_length('aes-256-gcm');
    $iv = random_bytes($ivLength);
    $encrypted = openssl_encrypt($nik, 'aes-256-gcm', $key, 0, $iv, $tag);
    return base64_encode($iv . $tag . $encrypted);
}

function hashNik(string $nik, string $hmacKeyHex): string {
    $key = hex2bin($hmacKeyHex);
    return hash_hmac('sha256', $nik, $key);
}

try {
    Env::load();
    
    $appEnv = Env::get('APP_ENV', 'development');
    $opts = getopt("", ["force"]);
    $forceMode = isset($opts['force']);

    if ($appEnv === 'production' && !$forceMode) {
        echo "Error: Seeder is blocked in production environment. Use --force flag to override.\n";
        exit(1);
    }

    $db = Connection::getConnection();

    $roles = [
        'patient' => [
            'email_var' => 'DEFAULT_PATIENT_EMAIL',
            'pass_var' => 'DEFAULT_PATIENT_PASSWORD',
            'name' => 'Default Patient',
            'table' => 'patients',
            'extra' => function($userId, $encryptionKey, $hmacKey) {
                $nik = '3171012345670001';
                return [
                     'user_id' => $userId,
                     'nik' => $nik,
                     'nik_encrypted' => encryptNik($nik, $encryptionKey),
                     'nik_hash' => hashNik($nik, $hmacKey),
                     'date_of_birth' => '1995-05-15',
                     'gender' => 'M',
                     'phone' => '081234567890',
                     'address' => 'Jl. Merdeka No. 123, Jakarta'
                 ];
             }
         ],
        'doctor' => [
            'email_var' => 'DEFAULT_DOCTOR_EMAIL',
            'pass_var' => 'DEFAULT_DOCTOR_PASSWORD',
            'name' => 'Default Doctor',
            'table' => 'doctors',
            'extra' => function($userId) {
                return [
                    'user_id' => $userId,
                    'specialization' => 'Umum',
                    'license_number' => 'IDI/2026/DOC-001',
                    'bio' => 'Dokter umum berpengalaman dengan komitmen pelayanan prima.'
                ];
            }
        ],
        'receptionist' => [
            'email_var' => 'DEFAULT_RECEPTIONIST_EMAIL',
            'pass_var' => 'DEFAULT_RECEPTIONIST_PASSWORD',
            'name' => 'Default Receptionist',
            'table' => 'receptionists',
            'extra' => function($userId) {
                return [
                    'user_id' => $userId,
                    'employee_code' => 'EMP-REC-001'
                ];
            }
        ],
        'admin' => [
            'email_var' => 'DEFAULT_ADMIN_EMAIL',
            'pass_var' => 'DEFAULT_ADMIN_PASSWORD',
            'name' => 'Default Admin',
            'table' => 'admins',
            'extra' => function($userId) {
                return [
                    'user_id' => $userId,
                    'employee_code' => 'EMP-ADM-001'
                ];
            }
        ],
        'apoteker' => [
            'email_var' => 'DEFAULT_APOTEKER_EMAIL',
            'pass_var' => 'DEFAULT_APOTEKER_PASSWORD',
            'name' => 'Default Apoteker',
            'table' => 'apotekers',
            'extra' => function($userId) {
                return [
                    'user_id' => $userId,
                    'license_number' => 'SIPA/2026/APO-001'
                ];
            }
        ]
    ];

    $encryptionKey = Env::get('APP_ENCRYPTION_KEY');
    $hmacKey = Env::get('APP_HMAC_KEY');

    if (empty($encryptionKey) || empty($hmacKey)) {
        throw new Exception("Encryption keys (APP_ENCRYPTION_KEY, APP_HMAC_KEY) must be set in .env before seeding.");
    }

    $seededInfo = "--- Seeded Accounts on " . date('Y-m-d H:i:s') . " ---\n";

    foreach ($roles as $roleName => $config) {
        $email = Env::get($config['email_var']);
        if (empty($email)) {
            echo "Skipping seeding for role $roleName: Email environment variable {$config['email_var']} is empty.\n";
            continue;
        }

        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "Account for $roleName ($email) already exists. Skipping.\n";
            continue;
        }

        $password = Env::get($config['pass_var']);
        $isGenerated = false;
        if (empty($password)) {
            $password = generateSecurePassword();
            $isGenerated = true;
        }

        // Hash using Argon2id or fallback to BCRYPT
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        if ($passwordHash === false || $passwordHash === null) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        $publicId = uuidv4();
        $name = $config['name'];

        $db->beginTransaction();
        try {
            // Insert into users
            $userQuery = "INSERT INTO users (public_id, name, email, password_hash, role, is_active, force_password_change) 
                          VALUES (?, ?, ?, ?, ?, 1, 1)";
            $stmtUser = $db->prepare($userQuery);
            $stmtUser->execute([$publicId, $name, $email, $passwordHash, $roleName]);
            $userId = $db->lastInsertId();

            // Insert into specific role table
            $extraData = $config['extra']($userId, $encryptionKey, $hmacKey);
            $cols = implode(', ', array_keys($extraData));
            $placeholders = ':' . implode(', :', array_keys($extraData));
            
            $stmtExtra = $db->prepare("INSERT INTO {$config['table']} ($cols) VALUES ($placeholders)");
            $stmtExtra->execute($extraData);

            $db->commit();
            echo "Successfully seeded $roleName account: $email\n";
            
            $seededInfo .= "Role: $roleName\nEmail: $email\nPassword: $password\n" . ($isGenerated ? "(Auto-Generated)\n" : "") . "-----------------------------------\n";
        } catch (Exception $ex) {
            $db->rollBack();
            echo "Failed seeding role $roleName: " . $ex->getMessage() . "\n";
        }
    }

    // Write seeded info to storage/seeded_credentials.txt
    $storageDir = __DIR__ . '/../../storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    file_put_contents($storageDir . '/seeded_credentials.txt', $seededInfo, FILE_APPEND);
    echo "Plaintext credentials logged to storage/seeded_credentials.txt\n";

} catch (Exception $e) {
    echo "Seeder failed: " . $e->getMessage() . "\n";
    exit(1);
}
