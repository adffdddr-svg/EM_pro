-- Employee Management System
-- جدول الإعدادات
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_group (setting_group),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة الإعدادات الافتراضية
INSERT INTO settings (setting_key, setting_value, setting_type, setting_group, description) VALUES
-- الإعدادات العامة
('site_name', 'نظام إدارة الموظفين', 'text', 'general', 'اسم الموقع'),
('site_url', 'http://localhost/EM_pro', 'text', 'general', 'رابط الموقع'),
('site_description', 'نظام إدارة موظفين متكامل', 'text', 'general', 'وصف الموقع'),
('timezone', 'Asia/Baghdad', 'text', 'general', 'المنطقة الزمنية'),
('language', 'ar', 'text', 'general', 'اللغة الافتراضية'),

-- إعدادات العرض
('dark_mode_enabled', '0', 'boolean', 'display', 'تفعيل الوضع الليلي'),
('items_per_page', '10', 'number', 'display', 'عدد العناصر في الصفحة'),
('date_format', 'Y-m-d', 'text', 'display', 'صيغة التاريخ'),
('time_format', '24', 'text', 'display', 'صيغة الوقت (12/24)'),
('font_size', '16', 'number', 'display', 'حجم الخط الافتراضي'),
('primary_color', '#2c3e50', 'color', 'display', 'اللون الأساسي'),
('show_statistics', '1', 'boolean', 'display', 'إظهار الإحصائيات'),
('show_photos', '1', 'boolean', 'display', 'إظهار الصور الشخصية'),

-- إعدادات الأمان
('password_min_length', '6', 'number', 'security', 'الحد الأدنى لطول كلمة المرور'),
('session_timeout', '3600', 'number', 'security', 'انتهاء الجلسة بالثواني'),
('two_factor_enabled', '0', 'boolean', 'security', 'تفعيل تسجيل الدخول بخطوتين'),
('max_login_attempts', '5', 'number', 'security', 'عدد محاولات تسجيل الدخول الفاشلة'),
('lockout_duration', '15', 'number', 'security', 'مدة الحظر بالدقائق'),
('https_enabled', '0', 'boolean', 'security', 'تفعيل HTTPS'),
('csrf_protection', '1', 'boolean', 'security', 'تفعيل حماية CSRF'),
('xss_protection', '1', 'boolean', 'security', 'تفعيل حماية XSS'),
('remember_me_enabled', '1', 'boolean', 'security', 'تفعيل Remember Me'),

-- إعدادات الإجازات
('default_annual_leave', '30', 'number', 'leaves', 'الرصيد الافتراضي للإجازات السنوية'),
('default_monthly_leave', '2', 'number', 'leaves', 'الرصيد الافتراضي للإجازات الشهرية'),
('max_sick_leave', '15', 'number', 'leaves', 'عدد أيام الإجازة المرضية المسموحة'),
('max_emergency_leave', '5', 'number', 'leaves', 'عدد أيام الإجازة الطارئة المسموحة'),
('unpaid_leave_enabled', '1', 'boolean', 'leaves', 'تفعيل الإجازات غير المدفوعة'),
('leave_notice_days', '3', 'number', 'leaves', 'عدد أيام الإشعار المسبق للإجازة'),
('multi_approval_enabled', '1', 'boolean', 'leaves', 'تفعيل نظام الموافقات المتعددة'),

-- إعدادات البوت الذكي
('ai_bot_enabled', '1', 'boolean', 'ai', 'تفعيل البوت الذكي'),
('openrouter_api_key', '', 'password', 'ai', 'مفتاح OpenRouter API'),
('openrouter_model', 'openai/gpt-4o-mini', 'text', 'ai', 'نموذج AI المستخدم'),
('ai_temperature', '0.7', 'number', 'ai', 'درجة الحرارة (Temperature)'),
('ai_max_tokens', '1000', 'number', 'ai', 'الحد الأقصى للرموز'),
('ai_language', 'ar', 'text', 'ai', 'لغة البوت'),
('ai_memory_enabled', '1', 'boolean', 'ai', 'تفعيل الذاكرة'),
('ai_memory_limit', '10', 'number', 'ai', 'عدد الرسائل المحفوظة في الذاكرة'),

-- إعدادات البريد الإلكتروني
('email_enabled', '0', 'boolean', 'email', 'تفعيل إرسال الإيميلات'),
('smtp_server', '', 'text', 'email', 'SMTP Server'),
('smtp_port', '587', 'number', 'email', 'SMTP Port'),
('smtp_username', '', 'text', 'email', 'SMTP Username'),
('smtp_password', '', 'password', 'email', 'SMTP Password'),
('smtp_encryption', 'tls', 'text', 'email', 'نوع التشفير (TLS/SSL)'),
('from_email', '', 'email', 'email', 'عنوان المرسل'),
('from_name', 'نظام إدارة الموظفين', 'text', 'email', 'اسم المرسل'),

-- إعدادات الإشعارات
('notifications_enabled', '1', 'boolean', 'notifications', 'تفعيل الإشعارات'),
('notify_new_employee', '1', 'boolean', 'notifications', 'إشعارات إضافة موظف جديد'),
('notify_employee_update', '1', 'boolean', 'notifications', 'إشعارات تعديل بيانات موظف'),
('notify_leave_request', '1', 'boolean', 'notifications', 'إشعارات طلبات الإجازات'),
('notify_leave_approval', '1', 'boolean', 'notifications', 'إشعارات الموافقات/الرفض'),
('notify_session_timeout', '1', 'boolean', 'notifications', 'إشعارات انتهاء الجلسة'),
('notify_errors', '1', 'boolean', 'notifications', 'إشعارات الأخطاء'),
('notification_method', 'both', 'text', 'notifications', 'طريقة الإشعار (email/in-app/both)'),

-- إعدادات الملفات
('max_file_size', '5', 'number', 'files', 'الحد الأقصى لحجم الملف (MB)'),
('allowed_file_types', 'image/jpeg,image/png,image/gif,image/webp', 'text', 'files', 'أنواع الملفات المسموحة'),
('image_quality', '85', 'number', 'files', 'جودة ضغط الصور'),
('file_storage', 'local', 'text', 'files', 'مكان حفظ الملفات (local/cloud)'),
('upload_enabled', '1', 'boolean', 'files', 'تفعيل رفع الملفات'),
('auto_cleanup', '0', 'boolean', 'files', 'مسح الملفات القديمة تلقائياً')

ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

