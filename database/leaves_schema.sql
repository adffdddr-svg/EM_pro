-- Employee Management System
-- نظام الإجازات - Leaves Management System
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- جدول الإجازات
CREATE TABLE IF NOT EXISTS employee_leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('ordinary', 'time', 'medical', 'emergency', 'unpaid') NOT NULL DEFAULT 'ordinary',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NULL DEFAULT NULL, -- للإجازة الزمنية
    end_time TIME NULL DEFAULT NULL, -- للإجازة الزمنية
    days DECIMAL(5, 2) NOT NULL DEFAULT 0,
    purpose TEXT, -- الغرض من الإجازة
    substitute_employee_id INT NULL, -- الموظف البديل
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL, -- من وافق على الإجازة
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL, -- سبب الرفض
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_leave_type (leave_type),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الموافقات (للموافقات المتعددة)
CREATE TABLE IF NOT EXISTS leave_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_id INT NOT NULL,
    approver_type ENUM('leave_unit', 'direct_supervisor', 'assistant_dean') NOT NULL,
    approver_id INT NULL, -- معرف الموظف الذي وافق
    approver_name VARCHAR(100) NOT NULL, -- اسم الموافق
    approver_position VARCHAR(100) NULL, -- المسمى الوظيفي
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_id) REFERENCES employee_leaves(id) ON DELETE CASCADE,
    INDEX idx_leave_id (leave_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول رصيد الإجازات
CREATE TABLE IF NOT EXISTS leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL UNIQUE,
    total_balance INT NOT NULL DEFAULT 0, -- الرصيد الكلي
    monthly_balance INT NOT NULL DEFAULT 2, -- الرصيد الشهري (افتراضي 2 يوم)
    remaining_balance INT NOT NULL DEFAULT 0, -- الرصيد المتبقي
    used_this_year INT NOT NULL DEFAULT 0, -- المستخدم هذا العام
    last_reset_date DATE NULL, -- آخر تاريخ إعادة تعيين
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج رصيد افتراضي للموظفين الموجودين
INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance)
SELECT id, 104, 2, 104 FROM employees
WHERE id NOT IN (SELECT employee_id FROM leave_balance)
ON DUPLICATE KEY UPDATE total_balance = total_balance;

-- عرض النتائج
SELECT 'Leaves system tables created successfully!' AS message;
SELECT COUNT(*) AS leaves_table_exists FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'employee_management' AND TABLE_NAME = 'employee_leaves';
SELECT COUNT(*) AS approvals_table_exists FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'employee_management' AND TABLE_NAME = 'leave_approvals';
SELECT COUNT(*) AS balance_table_exists FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'employee_management' AND TABLE_NAME = 'leave_balance';
