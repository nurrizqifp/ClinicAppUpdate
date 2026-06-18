<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS insurance_claims (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        payment_id BIGINT NOT NULL,
        provider_name VARCHAR(100) NOT NULL,
        policy_number VARCHAR(100) NOT NULL,
        claim_status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_claims_payments FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS insurance_claims;"
];
