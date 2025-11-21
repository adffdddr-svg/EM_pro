-- Employee Management System
-- جدول سجل الرواتب
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

CREATE TABLE IF NOT EXISTS salary_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    old_salary DECIMAL(10, 2) NULL,
    new_salary DECIMAL(10, 2) NOT NULL,
    change_type ENUM('increase', 'decrease', 'initial', 'adjustment') DEFAULT 'adjustment',
    change_amount DECIMAL(10, 2) NULL,
    change_percentage DECIMAL(5, 2) NULL,
    effective_date DATE NOT NULL,
    reason TEXT,
    notes TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_effective_date (effective_date),
    INDEX idx_change_type (change_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

