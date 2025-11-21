-- Employee Management System
-- تحديث قاعدة البيانات لدعم نظام الموظفين والمديرين
-- جامعة البصرة

USE employee_management;

-- 1. إنشاء جدول Remember Tokens
CREATE TABLE IF NOT EXISTS user_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. إضافة user_id إلى جدول employees (إذا لم يكن موجوداً)
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER id;

-- 3. إضافة Foreign Key (إذا لم يكن موجوداً)
-- ملاحظة: قد تحتاج إلى حذف Foreign Key القديم أولاً إذا كان موجوداً
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = 'employee_management' 
    AND TABLE_NAME = 'employees' 
    AND CONSTRAINT_NAME = 'fk_employees_user_id'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE employees ADD CONSTRAINT fk_employees_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. إضافة Index على user_id (إذا لم يكن موجوداً)
ALTER TABLE employees 
ADD INDEX IF NOT EXISTS idx_user_id (user_id);

-- 5. تحديث role في جدول users (إذا لم يكن محدثاً)
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee') DEFAULT 'admin';
UPDATE users SET role = 'employee' WHERE role = 'hr';

-- 6. عرض النتائج
SELECT 'Database updated successfully!' AS message;
SELECT COUNT(*) AS remember_tokens_table_exists FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'employee_management' AND TABLE_NAME = 'user_remember_tokens';
SELECT COUNT(*) AS employees_with_user_id FROM employees WHERE user_id IS NOT NULL;

