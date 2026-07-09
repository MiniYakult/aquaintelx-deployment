-- AquaIntelX profile image column
-- Run this once on your AquaIntelX database if the profile image does not save.

SET @db_name = DATABASE();

SET @has_profile_image = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'profile_image'
);

SET @sql = IF(
    @has_profile_image = 0,
    'ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER role',
    'SELECT "profile_image already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Optional check:
-- DESCRIBE users;
