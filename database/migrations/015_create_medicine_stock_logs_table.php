<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS medicine_stock_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        medicine_id BIGINT NOT NULL,
        change_type ENUM('in', 'out', 'adjustment') NOT NULL,
        quantity_change INT NOT NULL,
        reference_note TEXT NULL,
        performed_by BIGINT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_stock_logs_medicines FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_stock_logs_users FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS medicine_stock_logs;"
];
