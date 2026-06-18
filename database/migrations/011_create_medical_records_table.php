<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS medical_records (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        appointment_id BIGINT NOT NULL UNIQUE,
        doctor_id BIGINT NOT NULL,
        diagnosis TEXT NOT NULL,
        notes TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_medical_records_appointments FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_medical_records_doctors FOREIGN KEY (doctor_id) REFERENCES doctors(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS medical_records;"
];
