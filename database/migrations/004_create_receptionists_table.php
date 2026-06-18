<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS receptionists (
        user_id BIGINT PRIMARY KEY,
        employee_code VARCHAR(50) NOT NULL UNIQUE,
        CONSTRAINT fk_receptionists_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS receptionists;"
];
