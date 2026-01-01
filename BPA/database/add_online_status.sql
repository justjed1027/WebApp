-- Add online status tracking to user table
-- Run this SQL in phpMyAdmin or MySQL CLI

ALTER TABLE user ADD COLUMN is_online BOOLEAN DEFAULT FALSE AFTER user_is_admin;
ALTER TABLE user ADD COLUMN last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_online;
CREATE INDEX idx_is_online ON user(is_online);
