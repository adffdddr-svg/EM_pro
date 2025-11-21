-- Employee Management System
-- نظام السجلات - Records System
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- جدول السجلات
CREATE TABLE IF NOT EXISTS employee_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    record_type ENUM('personal', 'employment', 'attendance', 'leave', 'salary', 'evaluation', 'promotion', 'disciplinary', 'training', 'certificate', 'other') NOT NULL DEFAULT 'other',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    record_date DATE NOT NULL,
    document_file VARCHAR(255) NULL, -- اسم الملف المرفق
    document_path VARCHAR(500) NULL, -- مسار الملف
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    created_by INT NULL, -- من أنشأ السجل
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_record_type (record_type),
    INDEX idx_record_date (record_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تفاصيل السجلات (للمعلومات الإضافية)
CREATE TABLE IF NOT EXISTS record_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT NULL,
    field_type VARCHAR(50) DEFAULT 'text', -- text, number, date, file, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES employee_records(id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_field_name (field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

