-- Employee Management System Database Schema
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

CREATE DATABASE IF NOT EXISTS employee_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE employee_management;

-- جدول المستخدمين (المديرون)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'hr') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الأقسام
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الموظفين النشطين
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    department_id INT,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    hire_date DATE NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_employee_code (employee_code),
    INDEX idx_department (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول أرشيف الموظفين
CREATE TABLE IF NOT EXISTS employees_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    department_id INT,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    hire_date DATE NOT NULL,
    leave_date DATE NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    reason TEXT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_code (employee_code),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية
-- إدراج مستخدم افتراضي (كلمة المرور: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$bU715mWIsppVNnk1pTEyJeqwkOu1Zg4EOwV2hLsyFz18oOfsnhkd.', 'admin@example.com', 'admin')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- إدراج أقسام تجريبية
INSERT INTO departments (name, description) VALUES 
('قسم تقنية المعلومات', 'إدارة أنظمة المعلومات والشبكات'),
('قسم الموارد البشرية', 'إدارة شؤون الموظفين والتوظيف'),
('قسم المالية', 'إدارة الشؤون المالية والمحاسبة'),
('قسم المبيعات', 'إدارة المبيعات والتسويق'),
('قسم الإنتاج', 'إدارة عمليات الإنتاج والتصنيع');

-- إدراج موظفين تجريبيين
INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date) VALUES 
('EMP001', 'أحمد', 'محمد', 'ahmed@example.com', '07701234567', 'البصرة - الجمعية', 1, 'مطور برمجيات', 1500000.00, '2024-01-15'),
('EMP002', 'فاطمة', 'علي', 'fatima@example.com', '07701234568', 'البصرة - العشار', 2, 'أخصائي موارد بشرية', 1200000.00, '2024-02-20'),
('EMP003', 'محمد', 'حسن', 'mohammed@example.com', '07701234569', 'البصرة - الكورنيش', 3, 'محاسب', 1300000.00, '2024-03-10');

