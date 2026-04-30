/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842
*/


/* 
# Database Schema Notes

- users: stores alumni account credentials and verification state
- email_verification_tokens: stores single-use email verification tokens
- password_reset_tokens: stores single-use password reset tokens
- profiles: stores the main alumni profile
- degrees / certifications / licenses / short_courses / employment_history: stores multiple related profile entries
- bids: stores alumni bid submissions for a given day
- featured_alumni: stores the winning alumnus for each featured date
- alumni_events / event_participation: supports the extra monthly eligibility rule
- api_keys: stores hashed bearer tokens for client access
- api_key_usage_logs: stores API key usage statistics
- login_logs: stores login history for security monitoring 
*/


-- Alumni database schema used by the backend API.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS api_key_usage_logs;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS event_participation;
DROP TABLE IF EXISTS alumni_events;
DROP TABLE IF EXISTS featured_alumni;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS employment_history;
DROP TABLE IF EXISTS short_courses;
DROP TABLE IF EXISTS licenses;
DROP TABLE IF EXISTS certifications;
DROP TABLE IF EXISTS degrees;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS email_verification_tokens;
DROP TABLE IF EXISTS login_logs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_verification_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_verification_token_hash (token_hash),
    KEY idx_email_verification_user_id (user_id),
    KEY idx_email_verification_expires_at (expires_at),
    CONSTRAINT fk_email_verification_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    KEY idx_password_reset_user_id (user_id),
    KEY idx_password_reset_expires_at (expires_at),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    logged_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logged_out_at DATETIME NULL,
    KEY idx_login_logs_user_id (user_id),
    KEY idx_login_logs_logged_in_at (logged_in_at),
    CONSTRAINT fk_login_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    headline VARCHAR(200) NULL,
    biography TEXT NULL,
    linkedin_url VARCHAR(255) NULL,
    profile_image VARCHAR(255) NULL,
    current_job_title VARCHAR(150) NULL,
    current_company VARCHAR(150) NULL,
    is_profile_complete TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_profiles_user_id (user_id),
    KEY idx_profiles_user_id (user_id),
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE degrees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    degree_name VARCHAR(150) NOT NULL,
    institution_name VARCHAR(150) NOT NULL,
    degree_url VARCHAR(255) NULL,
    completion_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_degrees_profile_id (profile_id),
    CONSTRAINT fk_degrees_profile
        FOREIGN KEY (profile_id) REFERENCES profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    certification_name VARCHAR(150) NOT NULL,
    provider_name VARCHAR(150) NOT NULL,
    certificate_url VARCHAR(255) NULL,
    completion_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_certifications_profile_id (profile_id),
    CONSTRAINT fk_certifications_profile
        FOREIGN KEY (profile_id) REFERENCES profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    license_name VARCHAR(150) NOT NULL,
    awarding_body VARCHAR(150) NOT NULL,
    license_url VARCHAR(255) NULL,
    completion_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_licenses_profile_id (profile_id),
    CONSTRAINT fk_licenses_profile
        FOREIGN KEY (profile_id) REFERENCES profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE short_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    provider_name VARCHAR(150) NOT NULL,
    course_url VARCHAR(255) NULL,
    completion_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_short_courses_profile_id (profile_id),
    CONSTRAINT fk_short_courses_profile
        FOREIGN KEY (profile_id) REFERENCES profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employment_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_employment_profile_id (profile_id),
    CONSTRAINT fk_employment_profile
        FOREIGN KEY (profile_id) REFERENCES profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bid_date DATE NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'outbid', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'active',
    is_winner TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bids_user_date (user_id, bid_date),
    KEY idx_bids_user_id (user_id),
    KEY idx_bids_bid_date (bid_date),
    KEY idx_bids_bid_date_amount (bid_date, bid_amount),
    CONSTRAINT fk_bids_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE featured_alumni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bid_id INT UNSIGNED NOT NULL,
    feature_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_featured_alumni_feature_date (feature_date),
    KEY idx_featured_alumni_user_id (user_id),
    KEY idx_featured_alumni_bid_id (bid_id),
    CONSTRAINT fk_featured_alumni_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_featured_alumni_bid
        FOREIGN KEY (bid_id) REFERENCES bids(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alumni_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_alumni_events_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_participation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_participation_user_event (user_id, event_id),
    KEY idx_event_participation_event_id (event_id),
    CONSTRAINT fk_event_participation_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_event_participation_event
        FOREIGN KEY (event_id) REFERENCES alumni_events(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by INT UNSIGNED NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    key_preview VARCHAR(20) NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    scope VARCHAR(100) NOT NULL DEFAULT 'public',
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_api_keys_hash (api_key_hash),
    KEY idx_api_keys_created_by (created_by),
    KEY idx_api_keys_expires_at (expires_at),
    CONSTRAINT fk_api_keys_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_key_usage_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT UNSIGNED NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_api_key_usage_api_key_id (api_key_id),
    KEY idx_api_key_usage_used_at (used_at),
    CONSTRAINT fk_api_key_usage_api_key
        FOREIGN KEY (api_key_id) REFERENCES api_keys(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;