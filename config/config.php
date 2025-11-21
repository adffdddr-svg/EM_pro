<?php
/**
 * Employee Management System
 * جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات
 * إعدادات عامة للنظام
 */

// منع الوصول المباشر
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

// إعدادات الوقت
date_default_timezone_set('Asia/Baghdad');

// إعدادات الجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // تغيير إلى 1 في حالة استخدام HTTPS

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من Remember Token (يجب أن يكون بعد session_start وقبل تضمين auth.php)
// لكن يجب أن نتحقق من أن auth.php لم يتم تضمينه بعد
if (!function_exists('checkRememberToken')) {
    // سنستدعي checkRememberToken بعد تضمين auth.php
}

// إعدادات النظام
define('SITE_NAME', 'نظام إدارة الموظفين');

// تعريف SITE_URL بشكل بسيط
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// كشف Cloudflare Tunnel domain أو empro.local
$is_cloudflare = (strpos($host, 'trycloudflare.com') !== false || 
                  strpos($host, 'cloudflare') !== false);
$is_empro_local = ($host === 'empro.local');

// إذا كان Cloudflare Tunnel أو empro.local، لا نضيف /EM_pro
if ($is_cloudflare || $is_empro_local) {
    define('SITE_URL', $protocol . '://' . $host);
} elseif ($host === 'localhost' || $host === '127.0.0.1') {
    // إذا كان localhost، أضف /EM_pro
    define('SITE_URL', $protocol . '://' . $host . '/EM_pro');
} else {
    // على الاستضافة، لا نضيف /EM_pro
    define('SITE_URL', $protocol . '://' . $host);
}
define('SITE_URL_LOCAL', 'http://localhost/EM_pro'); // للوصول المحلي فقط
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
// UPLOAD_URL سيتم تعريفه بعد التأكد من SITE_URL
if (defined('SITE_URL')) {
    define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    define('UPLOAD_URL', $protocol . '://' . $host . '/assets/images/uploads/');
}
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// إعدادات قاعدة البيانات
// كشف تلقائي: localhost أو الاستضافة
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$is_localhost = ($host === 'localhost' || $host === '127.0.0.1');

if ($is_localhost) {
    // إعدادات قاعدة البيانات المحلية (WAMP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'employee_management');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // إعدادات قاعدة البيانات على الاستضافة (InfinityFree)
    define('DB_HOST', 'sql203.infinityfree.com');
    define('DB_NAME', 'if0_40432205_employee_management');
    define('DB_USER', 'if0_40432205');
    define('DB_PASS', 'Zxcvbnmfatmah1');
    define('DB_CHARSET', 'utf8mb4');
}

// إعدادات العرض
define('ITEMS_PER_PAGE', 10);
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// إعدادات الأمان
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات OpenAI API (للذكاء الاصطناعي)
// يمكنك إضافة API Key هنا أو في ملف منفصل: config/openai_key.txt
// define('OPENAI_API_KEY', 'sk-your-api-key-here');

// إعدادات OpenRouter.ai API (بديل عن OpenAI - يدعم نماذج متعددة)
// يمكنك إضافة API Key هنا أو في ملف منفصل: config/openrouter_key.txt
// للحصول على API Key: https://openrouter.ai/keys
 define('OPENROUTER_API_KEY', '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');
 define('OPENROUTER_MODEL', 'openai/gpt-4o-mini'); // أو أي نموذج آخر من OpenRouter
 define('AI_PROVIDER', 'openrouter'); // 'openai' أو 'openrouter'

// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/database.php';

// التحقق من Remember Token بعد تضمين database.php
// (لأن checkRememberToken يحتاج getDB())
if (!function_exists('checkRememberToken')) {
    // سنستدعي checkRememberToken بعد تضمين auth.php في الصفحات
}

