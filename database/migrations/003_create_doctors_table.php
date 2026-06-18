<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS doctors (
        user_id BIGINT PRIMARY KEY,
        specialization VARCHAR(100) NOT NULL,
        license_number VARCHAR(100) NOT NULL UNIQUE,
        bio TEXT NULL,
        CONSTRAINT fk_doctors_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS doctors;"
];
