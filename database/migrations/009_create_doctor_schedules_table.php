<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS doctor_schedules (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        doctor_id BIGINT NOT NULL,
        schedule_date DATE NULL,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        quota INT NOT NULL DEFAULT 20,
        room VARCHAR(50) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        CONSTRAINT fk_schedules_doctors FOREIGN KEY (doctor_id) REFERENCES doctors(user_id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS doctor_schedules;"
];
