CREATE DATABASE IF NOT EXISTS amore_academy
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE amore_academy;

-- ── Appointments Table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    contact_number  VARCHAR(50)  NOT NULL,
    applicant_level VARCHAR(50)  NOT NULL,
    preferred_date  DATE         NOT NULL,
    preferred_time  TIME         NOT NULL,
    purpose         VARCHAR(100) NOT NULL,
    notes           TEXT,
    status          VARCHAR(50)  NOT NULL DEFAULT 'Pending',
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Inquiries Table ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inquiries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Admin Users Table ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: username=admin  password=Admin@2025
-- Hash generated with password_hash('Admin@2025', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, password)
VALUES ('admin', '$2y$10$TKh8H1.PfFmGv7rRBxeJbu2u4eqPjNrU5MqzEO4BQGC9uoq0yBXya')
ON DUPLICATE KEY UPDATE username = username;