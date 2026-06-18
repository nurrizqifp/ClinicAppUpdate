<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS appointments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        public_id VARCHAR(36) NOT NULL UNIQUE,
        patient_id BIGINT NOT NULL,
        doctor_id BIGINT NOT NULL,
        schedule_id BIGINT NULL,
        queue_number INT NOT NULL,
        complaint TEXT NOT NULL,
        priority ENUM('normal', 'emergency') NOT NULL DEFAULT 'normal',
        status ENUM('waiting', 'called', 'in_consultation', 'done', 'cancelled') NOT NULL DEFAULT 'waiting',
        called_at DATETIME NULL,
        started_at DATETIME NULL,
        completed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_appointments_patients FOREIGN KEY (patient_id) REFERENCES patients(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_appointments_doctors FOREIGN KEY (doctor_id) REFERENCES doctors(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_appointments_schedules FOREIGN KEY (schedule_id) REFERENCES doctor_schedules(id) ON DELETE SET NULL ON UPDATE CASCADE,
        INDEX idx_appointments_status (status),
        INDEX idx_appointments_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS appointments;"
];
