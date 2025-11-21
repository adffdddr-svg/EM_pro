<?php
/**
 * Employee Management System
 * حفظ الإعدادات
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/settings.php');
    exit;
}

$db = getDB();
$group = $_POST['group'] ?? 'general';
$user_id = $_SESSION['user_id'];

try {
    // التحقق من وجود جدول settings
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // حفظ كل حقل
    foreach ($_POST as $key => $value) {
        if ($key === 'group') continue;
        
        // تنظيف القيمة
        $value = trim($value);
        
        // تحديد نوع الحقل
        $type = 'text';
        if (strpos($key, 'api_key') !== false || strpos($key, 'password') !== false) {
            $type = 'password';
        } elseif ($key === 'user_theme' && in_array($value, ['light', 'dark', 'auto', 'dark-blue', 'dark-pink', 'classic', 'blue', 'elegant', 'vibrant', 'pink'])) {
            $type = 'text'; // حفظ الثيم المختار
        } elseif (is_numeric($value) && strpos($key, '_enabled') === false && strpos($key, 'show_') === false && strpos($key, 'notify_') === false) {
            $type = 'number';
        } elseif (in_array($value, ['0', '1']) && (strpos($key, '_enabled') !== false || strpos($key, 'show_') !== false || strpos($key, 'notify_') !== false || strpos($key, 'ai_') !== false || strpos($key, 'multi_') !== false || strpos($key, 'unpaid_') !== false || strpos($key, 'auto_') !== false || strpos($key, 'upload_') !== false || strpos($key, 'two_') !== false || strpos($key, 'https_') !== false || strpos($key, 'csrf_') !== false || strpos($key, 'xss_') !== false || strpos($key, 'remember_') !== false || strpos($key, 'email_') !== false || strpos($key, 'notifications_') !== false)) {
            $type = 'boolean';
        } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
        } elseif (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $type = 'color';
        }
        
        // حفظ أو تحديث
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, setting_group, updated_by) 
                             VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE 
                             setting_value = VALUES(setting_value),
                             setting_type = VALUES(setting_type),
                             updated_by = VALUES(updated_by),
                             updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $type, $group, $user_id]);
    }
    
    // إذا كان الوضع الليلي أو الثيم، نطبق التغيير فوراً
    if ($group === 'display' && (isset($_POST['dark_mode_enabled']) || isset($_POST['user_theme']))) {
        // سيتم تطبيقه عبر JavaScript
    }
    
    header('Location: ' . SITE_URL . '/admin/settings.php?success=' . urlencode('تم حفظ الإعدادات بنجاح'));
    
} catch (PDOException $e) {
    header('Location: ' . SITE_URL . '/admin/settings.php?error=' . urlencode('حدث خطأ: ' . $e->getMessage()));
}
exit;

