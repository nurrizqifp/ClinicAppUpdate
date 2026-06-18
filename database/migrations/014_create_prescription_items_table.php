<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS prescription_items (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        prescription_id BIGINT NOT NULL,
        medicine_id BIGINT NOT NULL,
        dosage VARCHAR(100) NOT NULL,
        quantity INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_items_prescriptions FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_items_medicines FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS prescription_items;"
];
