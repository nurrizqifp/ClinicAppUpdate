<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS patients (
        user_id BIGINT PRIMARY KEY,
        nik_encrypted TEXT NOT NULL,
        nik_hash VARCHAR(64) NOT NULL UNIQUE,
        date_of_birth DATE NOT NULL,
        gender ENUM('M', 'F') NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        CONSTRAINT fk_patients_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS patients;"
];
