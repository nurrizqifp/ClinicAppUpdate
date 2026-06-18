<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS apotekers (
        user_id BIGINT PRIMARY KEY,
        license_number VARCHAR(100) NOT NULL UNIQUE,
        CONSTRAINT fk_apotekers_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS apotekers;"
];
