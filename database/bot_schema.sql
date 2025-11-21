-- Smart HR Bot Database Schema
-- جداول البوت الذكي لنظام إدارة الموظفين

USE employee_management;

-- جدول رسائل البوت
CREATE TABLE IF NOT EXISTS bot_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    message_type ENUM('motivational', 'greeting', 'question', 'notification', 'joke', 'birthday', 'anniversary', 'reminder', 'info') DEFAULT 'info',
    message_text TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_read (is_read),
    INDEX idx_type (message_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تفاعلات البوت
CREATE TABLE IF NOT EXISTS bot_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    intent VARCHAR(50), -- salary, leave, status, greeting, etc.
    confidence DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_intent (intent),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إشعارات البوت
CREATE TABLE IF NOT EXISTS bot_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    notification_type VARCHAR(50) NOT NULL, -- meeting, task, deadline, etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    scheduled_at DATETIME,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_read (is_read),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول إعدادات البوت
CREATE TABLE IF NOT EXISTS bot_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج إعدادات افتراضية
INSERT INTO bot_settings (setting_key, setting_value, description) VALUES
('bot_enabled', '1', 'تفعيل/تعطيل البوت'),
('motivational_messages_enabled', '1', 'تفعيل الرسائل التحفيزية'),
('birthday_notifications_enabled', '1', 'تفعيل إشعارات أعياد الميلاد'),
('anniversary_notifications_enabled', '1', 'تفعيل إشعارات الذكرى السنوية'),
('daily_greetings_enabled', '1', 'تفعيل التحيات اليومية'),
('jokes_enabled', '1', 'تفعيل النكات والرسائل الإيجابية'),
('auto_response_enabled', '1', 'تفعيل الرد التلقائي على الأسئلة'),
('response_time', '2', 'وقت الاستجابة بالثواني (للمحاكاة)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- إدراج رسائل تحفيزية تجريبية
INSERT INTO bot_messages (employee_id, message_type, message_text) VALUES
(NULL, 'motivational', 'صباح الخير! يوم جديد يعني فرص جديدة للنجاح 🌟'),
(NULL, 'motivational', 'أنت تقوم بعمل رائع! استمر في التقدم 💪'),
(NULL, 'motivational', 'تذكر: كل خطوة صغيرة تقربك من هدفك الكبير 🎯'),
(NULL, 'motivational', 'الإبداع يبدأ من حيث ينتهي الآخرون. أنت مبدع! ✨'),
(NULL, 'motivational', 'النجاح ليس نهاية، والفشل ليس قاتلاً. المهم هو الشجاعة للاستمرار 🚀'),
(NULL, 'joke', 'لماذا الكمبيوتر بارد؟ لأنه Windows مفتوح! 😄'),
(NULL, 'joke', 'ما هو البرنامج المفضل للطبيب؟ الدواء! 💊'),
(NULL, 'joke', 'لماذا لا ينام المبرمج؟ لأنه يبحث عن البق! 🐛');

