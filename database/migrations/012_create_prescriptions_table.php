<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS prescriptions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        medical_record_id BIGINT NOT NULL,
        status ENUM('pending', 'dispensed') NOT NULL DEFAULT 'pending',
        dispensed_by BIGINT NULL,
        dispensed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_prescriptions_records FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_prescriptions_apotekers FOREIGN KEY (dispensed_by) REFERENCES apotekers(user_id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS prescriptions;"
];
