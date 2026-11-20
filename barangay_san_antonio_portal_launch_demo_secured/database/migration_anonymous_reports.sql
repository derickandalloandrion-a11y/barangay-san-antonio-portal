-- Non-destructive addition for anonymous incident reports.
-- Select the dedicated launch-demo database before running this migration.
CREATE TABLE IF NOT EXISTS anonymous_reports (
  anonymous_report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference_code VARCHAR(32) NOT NULL UNIQUE,
  category VARCHAR(100) NOT NULL,
  incident_date DATE NULL,
  incident_location VARCHAR(255) NOT NULL,
  details TEXT NOT NULL,
  contact_info VARCHAR(180) NULL,
  status ENUM('SUBMITTED','UNDER_REVIEW','REFERRED','CLOSED') NOT NULL DEFAULT 'SUBMITTED',
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_anonymous_reports_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
