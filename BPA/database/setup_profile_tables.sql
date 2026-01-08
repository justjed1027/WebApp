-- Setup Profile Tables for SkillSwap
-- Run this script to create the necessary tables for the user profile setup flow
-- Usage: mysql -u [username] -p [database_name] < setup_profile_tables.sql

-- ============================================================================
-- 1. PROFILE TABLE - Stores extended user profile information (Page 1)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `profile` (
    `profile_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `user_firstname` VARCHAR(100) DEFAULT NULL,
    `user_lastname` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `profile_summary` TEXT DEFAULT NULL,
    `profile_filepath` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. USER_PREFERENCES TABLE - Stores user UI preferences (Page 4)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `pref_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `accent_color` VARCHAR(7) DEFAULT '#0ea5e9',
    `theme_mode` ENUM('light', 'dark', 'mixed') DEFAULT 'mixed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VERIFICATION QUERIES (Optional - uncomment to test after creation)
-- ============================================================================
-- SHOW TABLES LIKE '%profile%';
-- SHOW TABLES LIKE '%user_%';
-- DESCRIBE profile;
-- DESCRIBE user_preferences;
