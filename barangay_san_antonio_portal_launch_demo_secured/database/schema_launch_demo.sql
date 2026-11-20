-- Barangay San Antonio Portal - Consolidated LOCAL DEMONSTRATION schema
-- MariaDB 10.4+ / MySQL 8.0+. Import into a NEW database only.
-- Safety: no DROP, TRUNCATE, or ALTER statements. Existing databases are not modified.
CREATE DATABASE IF NOT EXISTS barangay_san_antonio_portal_launch_demo
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_san_antonio_portal_launch_demo;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  role_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role_code VARCHAR(60) NOT NULL UNIQUE,
  role_name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  permission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  permission_code VARCHAR(100) NOT NULL UNIQUE,
  permission_name VARCHAR(150) NOT NULL,
  module_name VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  account_type ENUM('RESIDENT','VISITOR','OFFICIAL','ADMINISTRATOR') NOT NULL,
  account_status ENUM('PENDING_EMAIL','ACTIVE','SUSPENDED','DISABLED') NOT NULL DEFAULT 'PENDING_EMAIL',
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  password_changed_at DATETIME NULL,
  failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_profiles (
  user_profile_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NOT NULL,
  suffix VARCHAR(30) NULL,
  date_of_birth DATE NULL,
  mobile_number VARCHAR(20) NULL,
  address_line VARCHAR(255) NULL,
  barangay VARCHAR(120) NULL,
  municipality VARCHAR(120) NULL,
  province VARCHAR(120) NULL,
  profile_photo_file_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  assigned_by_user_id BIGINT UNSIGNED NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_user_roles_assigner FOREIGN KEY (assigned_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS official_positions (
  position_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  position_code VARCHAR(60) NOT NULL UNIQUE,
  position_name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS official_assignments (
  official_assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  position_id BIGINT UNSIGNED NOT NULL,
  term_start DATE NOT NULL,
  term_end DATE NULL,
  verification_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  assignment_status ENUM('SCHEDULED','ACTIVE','ENDED','REVOKED') NOT NULL DEFAULT 'SCHEDULED',
  verified_by_user_id BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  notes VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_official_assignment_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_official_assignment_position FOREIGN KEY (position_id) REFERENCES official_positions(position_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_official_assignment_verifier FOREIGN KEY (verified_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_official_assignment_user (user_id, assignment_status, verification_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS position_permissions (
  position_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  granted_by_user_id BIGINT UNSIGNED NULL,
  granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (position_id, permission_id),
  CONSTRAINT fk_position_permissions_position FOREIGN KEY (position_id) REFERENCES official_positions(position_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_position_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_position_permissions_granter FOREIGN KEY (granted_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resident_verification_requests (
  resident_verification_request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  verification_status ENUM('PENDING','UNDER_REVIEW','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  decision_reason VARCHAR(1000) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_resident_verification_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_resident_verification_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_transactions (
  transaction_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference_code VARCHAR(32) NOT NULL UNIQUE,
  created_by BIGINT UNSIGNED NOT NULL,
  module_type ENUM('DOCUMENT_REQUEST','INCIDENT_REPORT','MEDICINE_REQUEST') NOT NULL,
  current_status ENUM('SUBMITTED','UNDER_REVIEW','APPROVED','REJECTED','PROCESSING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SUBMITTED',
  title VARCHAR(180) NOT NULL,
  details TEXT NOT NULL,
  metadata_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  review_notes TEXT NULL,
  CONSTRAINT fk_service_owner FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_service_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_service_owner (created_by, created_at),
  INDEX idx_service_status (module_type, current_status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_status_history (
  service_status_history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  notes VARCHAR(1000) NULL,
  changed_by_user_id BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_service_history_transaction FOREIGN KEY (transaction_id) REFERENCES service_transactions(transaction_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_service_history_user FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_service_history_transaction (transaction_id, changed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
  email_verification_token_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  sent_at DATETIME NULL,
  used_at DATETIME NULL,
  invalidated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_token_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  INDEX idx_email_token_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_outbox (
  email_outbox_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  recipient_email VARCHAR(254) NOT NULL,
  message_type VARCHAR(60) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  payload_json LONGTEXT NOT NULL,
  delivery_status ENUM('PENDING','PROCESSING','RETRY_SCHEDULED','SENT','FAILED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NULL,
  accepted_by_smtp_at DATETIME NULL,
  sent_at DATETIME NULL,
  last_error VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_outbox_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  INDEX idx_email_outbox_due (delivery_status, next_attempt_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_requests (
  appointment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference_code VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL,
  office_unit VARCHAR(100) NOT NULL,
  appointment_purpose VARCHAR(180) NOT NULL,
  details TEXT NULL,
  requested_date DATE NOT NULL,
  requested_time TIME NOT NULL,
  status ENUM('SUBMITTED','UNDER_REVIEW','CONFIRMED','DECLINED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SUBMITTED',
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  review_notes VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointment_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_appointment_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_appointment_queue (status, requested_date, requested_time)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_status_history (
  appointment_status_history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  appointment_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  notes VARCHAR(1000) NULL,
  changed_by_user_id BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointment_history_request FOREIGN KEY (appointment_id) REFERENCES appointment_requests(appointment_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_appointment_history_user FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS foi_requests (
  foi_request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference_code VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL,
  requester_name VARCHAR(200) NOT NULL,
  requester_email VARCHAR(254) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  requested_records TEXT NOT NULL,
  purpose VARCHAR(1000) NULL,
  status ENUM('SUBMITTED','UNDER_REVIEW','RELEASED','PARTIALLY_RELEASED','DENIED','CLOSED') NOT NULL DEFAULT 'SUBMITTED',
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  response_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_foi_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_foi_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_foi_queue (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS foi_status_history (
  foi_status_history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  foi_request_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  notes VARCHAR(1000) NULL,
  changed_by_user_id BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_foi_history_request FOREIGN KEY (foi_request_id) REFERENCES foi_requests(foi_request_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_foi_history_user FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feedback_submissions (
  feedback_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  category ENUM('SERVICE','WEBSITE','COMPLAINT','SUGGESTION','COMPLIMENT','OTHER') NOT NULL DEFAULT 'OTHER',
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  contact_email VARCHAR(254) NULL,
  status ENUM('OPEN','IN_REVIEW','RESOLVED','CLOSED') NOT NULL DEFAULT 'OPEN',
  assigned_to_user_id BIGINT UNSIGNED NULL,
  staff_response TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT fk_feedback_assignee FOREIGN KEY (assigned_to_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_feedback_queue (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_items (
  content_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  content_type ENUM('ARTICLE','ANNOUNCEMENT','NOTICE','ISSUANCE') NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  title VARCHAR(220) NOT NULL,
  summary VARCHAR(500) NULL,
  body MEDIUMTEXT NOT NULL,
  status ENUM('DRAFT','PENDING_REVIEW','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  created_by_user_id BIGINT UNSIGNED NULL,
  approved_by_user_id BIGINT UNSIGNED NULL,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_content_creator FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT fk_content_approver FOREIGN KEY (approved_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_content_public (content_type, status, published_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_revisions (
  content_revision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(220) NOT NULL,
  summary VARCHAR(500) NULL,
  body MEDIUMTEXT NOT NULL,
  changed_by_user_id BIGINT UNSIGNED NULL,
  change_note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_content_revision_item FOREIGN KEY (content_id) REFERENCES content_items(content_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_content_revision_user FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS public_documents (
  public_document_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category ENUM('TRANSPARENCY_SEAL','BUDGET','PROCUREMENT','ANNUAL_REPORT','OPEN_DATA','LAW_OR_ISSUANCE','OTHER') NOT NULL,
  title VARCHAR(220) NOT NULL,
  description VARCHAR(1000) NULL,
  document_url VARCHAR(1000) NULL,
  published_date DATE NULL,
  status ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  uploaded_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_public_document_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_public_documents (category, status, published_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS citizen_charter_services (
  charter_service_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  service_code VARCHAR(80) NOT NULL UNIQUE,
  service_name VARCHAR(180) NOT NULL,
  description VARCHAR(1000) NULL,
  requirements_text TEXT NULL,
  fee_text VARCHAR(255) NOT NULL DEFAULT 'To be confirmed by the Barangay Hall',
  processing_time_text VARCHAR(255) NOT NULL DEFAULT 'To be confirmed by the Barangay Hall',
  service_channel VARCHAR(255) NULL,
  status ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  audit_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  event_code VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  details_json LONGTEXT NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT,
  INDEX idx_audit_event (event_code, created_at),
  INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_records (
  consent_record_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  consent_type VARCHAR(80) NOT NULL,
  notice_version VARCHAR(40) NOT NULL,
  accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_hash CHAR(64) NULL,
  CONSTRAINT fk_consent_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
  INDEX idx_consent_user (user_id, consent_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS data_subject_requests (
  data_subject_request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  request_type ENUM('ACCESS','CORRECTION','ERASURE','OBJECTION','PORTABILITY','OTHER') NOT NULL,
  details TEXT NOT NULL,
  status ENUM('SUBMITTED','UNDER_REVIEW','APPROVED','DENIED','COMPLETED') NOT NULL DEFAULT 'SUBMITTED',
  handled_by_user_id BIGINT UNSIGNED NULL,
  response_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dsr_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_dsr_handler FOREIGN KEY (handled_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_by_user_id BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_setting_user FOREIGN KEY (updated_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Roles. Public registration can create Resident and Visitor only.
INSERT IGNORE INTO roles (role_id, role_code, role_name, is_active) VALUES
(1,'RESIDENT','Resident',1),(2,'VISITOR','Visitor',1),(3,'BARANGAY_OFFICIAL','Barangay Official',1),(4,'BARANGAY_ADMINISTRATOR','Barangay Administrator',1);

-- Preserve the approved 1-19 permission catalogue order; additional demo permissions follow.
INSERT IGNORE INTO permissions (permission_id,permission_code,permission_name,module_name) VALUES
(1,'dashboard.resident.view','View resident dashboard','Dashboard'),
(2,'documents.view_own','View own document requests','Documents'),
(3,'documents.create','Create document requests','Documents'),
(4,'incidents.view_own','View own incident reports','Incidents'),
(5,'incidents.create','Create incident reports','Incidents'),
(6,'medicine.view_own','View own medicine requests','Medicine'),
(7,'medicine.create','Create medicine requests','Medicine'),
(8,'appointments.view_own','View own appointments','Appointments'),
(9,'appointments.create','Create appointments','Appointments'),
(10,'officials.resident_verification.review','Review resident verification','Residents'),
(11,'documents.review','Review document requests','Documents'),
(12,'incidents.manage','Manage incident reports','Incidents'),
(13,'medicine.manage','Manage medicine requests','Medicine'),
(14,'appointments.manage','Manage appointments','Appointments'),
(15,'users.manage','Manage user accounts','Users'),
(16,'roles.manage','Manage roles and permissions','RBAC'),
(17,'officials.manage','Manage official assignments','Officials'),
(18,'audit.view','View audit logs','Audit'),
(19,'settings.manage','Manage portal settings','Settings'),
(20,'foi.create','Submit FOI requests','FOI'),
(21,'foi.review','Review FOI requests','FOI'),
(22,'feedback.create','Submit feedback','Feedback'),
(23,'feedback.manage','Manage feedback','Feedback'),
(24,'content.manage','Manage articles and announcements','Content'),
(25,'transparency.manage','Manage transparency documents','Transparency'),
(26,'data.subject.manage','Manage data subject requests','Privacy'),
(27,'dashboard.admin.view','View administrator dashboard','Dashboard'),
(28,'dashboard.official.view','View official dashboard','Dashboard');

-- Resident and Visitor role permissions; Official has NO generic role permissions.
INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT 1,permission_id FROM permissions WHERE permission_id IN (1,2,3,4,5,6,7,8,9,20,22);
INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT 2,permission_id FROM permissions WHERE permission_id IN (8,9,20,22);
-- Administrator receives the previously approved administrative permissions 10-19 plus demo administration modules.
INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT 4,permission_id FROM permissions WHERE permission_id BETWEEN 10 AND 19 OR permission_id IN (20,21,23,24,25,26,27,28);

INSERT IGNORE INTO official_positions (position_id,position_code,position_name,description,is_active) VALUES
(1,'BARANGAY_CAPTAIN','Barangay Captain','Demonstration position; replace with verified official appointment.',1),
(2,'BARANGAY_SECRETARY','Barangay Secretary','Demonstration position; replace with verified official appointment.',1),
(3,'BARANGAY_KAGAWAD','Barangay Kagawad','Demonstration position; replace with verified official appointment.',1),
(4,'SK_CHAIRPERSON','SK Chairperson','Demonstration position; replace with verified official appointment.',1);

-- Demonstration position-scoped permissions. No Official permissions are inherited from role_permissions.
INSERT IGNORE INTO position_permissions (position_id,permission_id) VALUES
(1,10),(1,11),(1,12),(1,13),(1,14),(1,21),(1,23),(1,24),(1,25),
(2,10),(2,11),(2,14),(2,21),(2,23),(2,24),(2,25),
(3,12),(3,13),(3,23),
(4,12),(4,23);

-- Test accounts are pre-verified and are for local demonstration only. Change passwords before any public deployment.
INSERT IGNORE INTO users (user_id,username,email,password_hash,account_type,account_status,email_verified_at,password_changed_at) VALUES
(1,'portal_admin_test','portal-admin-test@example.invalid','$2y$12$3qYfSvk3MguwqpRUZtZO3esqPMe79ubj/iRvhC3XX.HR50YmC4X9u','ADMINISTRATOR','ACTIVE',NOW(),NOW()),
(2,'portal_resident_test','portal-resident-test@example.invalid','$2y$12$5ITSaNuoQ8Liml4OcqFt3.WSToNlujOK6fszYdw8ghi2aHpCh7OIa','RESIDENT','ACTIVE',NOW(),NOW()),
(3,'portal_official_test','portal-official-test@example.invalid','$2y$12$rtRrPwnGTvgrfz2TWcRR7eEDiskdy2eatOZg/5PCB.jHLBI0e1IaW','OFFICIAL','ACTIVE',NOW(),NOW()),
(4,'portal_visitor_test','portal-visitor-test@example.invalid','$2y$12$6qXPIMkVgWijtnycnPsore9ssN2OzsZVnBgOeaak6xz5bPfiCiyia','VISITOR','ACTIVE',NOW(),NOW());
INSERT IGNORE INTO user_profiles (user_id,first_name,last_name,mobile_number,address_line,barangay,municipality,province) VALUES
(1,'Portal','Administrator',NULL,NULL,NULL,'Candon City','Ilocos Sur'),
(2,'Demo','Resident','+639171234567','Demo Street','San Antonio','Candon City','Ilocos Sur'),
(3,'Demo','Official',NULL,NULL,'San Antonio','Candon City','Ilocos Sur'),
(4,'Demo','Visitor',NULL,NULL,NULL,'Candon City','Ilocos Sur');
INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (1,4),(2,1),(3,3),(4,2);
INSERT IGNORE INTO resident_verification_requests (user_id,verification_status,reviewed_by_user_id,reviewed_at,decision_reason) VALUES
(2,'APPROVED',1,NOW(),'Demonstration account only; replace with actual verification procedure.');
INSERT IGNORE INTO official_assignments (official_assignment_id,user_id,position_id,term_start,term_end,verification_status,assignment_status,verified_by_user_id,verified_at,notes) VALUES
(1,3,1,'2026-01-01','2028-12-31','VERIFIED','ACTIVE',1,NOW(),'Demonstration assignment only; replace with verified official records.');

-- Public-facing catalogue records remain DRAFT until an authorized barangay reviewer confirms them.
INSERT IGNORE INTO citizen_charter_services (service_code,service_name,description,requirements_text,fee_text,processing_time_text,status) VALUES
('BARANGAY_CLEARANCE','Barangay Clearance','Barangay-issued clearance for eligible residents.','Valid identification and any supporting documents required by the Barangay Hall.','To be confirmed by the Barangay Hall','To be confirmed by the Barangay Hall','DRAFT'),
('CERTIFICATE_OF_INDIGENCY','Certificate of Indigency','Certification for eligible residents seeking assistance.','Identity, residency, and eligibility documents as required by the Barangay Hall.','To be confirmed by the Barangay Hall','To be confirmed by the Barangay Hall','DRAFT'),
('COMMUNITY_TAX_CERTIFICATE','Community Tax Certificate','Community tax certificate information.','Requirements and issuing arrangements to be confirmed by the Barangay Hall.','To be confirmed by the Barangay Hall','To be confirmed by the Barangay Hall','DRAFT');

INSERT IGNORE INTO system_settings (setting_key,setting_value,is_public) VALUES
('portal_environment','LOCAL DEMONSTRATION - NOT AN OFFICIAL GOVERNMENT DOMAIN',1),
('barangay_name','Barangay San Antonio',1),
('municipality','Candon City',1),
('province','Ilocos Sur',1),
('official_domain_status','A verified .gov.ph domain has not been configured.',1),
('https_status','Configure a valid TLS certificate before public deployment.',1),
('dpo_contact','Replace with the officially designated Data Protection Officer contact.',1),
('office_address','Replace with the verified Barangay Hall address.',1),
('office_hotline','Replace with the official barangay hotline.',1),
('office_email','Replace with the official barangay email address.',1),
('office_hours','Replace with verified public office hours.',1),
('mandate','Replace with the verified barangay mandate and legal basis.',1),
('leadership','Replace with the verified current leadership and official directory reference.',1);
