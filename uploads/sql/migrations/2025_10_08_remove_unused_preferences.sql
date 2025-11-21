-- Migration: remove SMS and promotional preferences columns
-- Run this after the initial user_preferences table creation

ALTER TABLE user_preferences 
DROP COLUMN IF EXISTS sms_notifications,
DROP COLUMN IF EXISTS promotional_updates;
