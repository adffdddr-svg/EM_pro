-- Employee Management System
-- نظام الحضور والانصراف - Attendance System
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- جدول الحضور والانصراف
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    day_type ENUM('work_day', 'holiday') DEFAULT 'work_day',
    schedule_id INT NULL, -- معرف الجدول (1, 2, 3, etc.)
    time_in TIME NULL, -- وقت الحضور
    time_out TIME NULL, -- وقت الانصراف
    overtime_hours DECIMAL(5, 2) DEFAULT 0.00, -- الوقت الإضافي بالساعات
    work_hours_difference DECIMAL(5, 2) DEFAULT 0.00, -- فارق ساعات العمل
    late_arrival_minutes INT DEFAULT 0, -- وصول متأخر بالدقائق
    early_departure_minutes INT DEFAULT 0, -- خروج مبكر بالدقائق
    leave_taken VARCHAR(50) NULL, -- نوع الإجازة المأخوذة (مثل: غائب، إجازة، إلخ)
    notes TEXT NULL, -- الملاحظات
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL, -- من سجل الحضور
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_employee_date (employee_id, attendance_date),
    INDEX idx_employee_id (employee_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_day_type (day_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الجداول الزمنية (Schedules)
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(100) NOT NULL, -- اسم الجدول (مثل: جدول 1، جدول 2)
    start_time TIME NOT NULL, -- وقت بداية الدوام
    end_time TIME NOT NULL, -- وقت نهاية الدوام
    work_hours DECIMAL(4, 2) NOT NULL, -- عدد ساعات العمل
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج جداول افتراضية
INSERT INTO schedules (id, schedule_name, start_time, end_time, work_hours) VALUES
(1, 'جدول 1', '08:00:00', '16:00:00', 8.00),
(2, 'جدول 2', '07:00:00', '15:00:00', 8.00)
ON DUPLICATE KEY UPDATE schedule_name = VALUES(schedule_name);

