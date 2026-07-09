-- AquaIntelX verified password reset support
-- Run this once in your Railway MySQL database and local Laragon database if needed.

CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    email VARCHAR(100) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    request_ip VARCHAR(64) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email_created (email, created_at),
    INDEX idx_email_expires (email, expires_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
