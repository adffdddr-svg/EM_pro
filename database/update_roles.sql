-- Update roles from 'hr' to 'employee'
-- Employee Management System
-- جامعة البصرة

USE employee_management;

-- Update the ENUM type to use 'employee' instead of 'hr'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee') DEFAULT 'admin';

-- Update any existing 'hr' roles to 'employee'
UPDATE users SET role = 'employee' WHERE role = 'hr';

-- Verify the update
SELECT id, username, email, role FROM users;

