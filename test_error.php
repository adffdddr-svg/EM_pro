<?php
// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='ar' dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>اختبار النظام</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }";
echo "h1 { color: #333; }";
echo "h2 { color: #667eea; margin-top: 30px; }";
echo ".success { color: green; }";
echo ".error { color: red; }";
echo ".info { background: white; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 اختبار النظام</h1>";

// اختبار 1: PHP
echo "<div class='info'>";
echo "<h2>✓ PHP يعمل</h2>";
echo "إصدار PHP: <strong>" . phpversion() . "</strong><br>";
echo "نظام التشغيل: " . PHP_OS . "<br>";
echo "</div>";

// اختبار 2: قاعدة البيانات
echo "<div class='info'>";
echo "<h2>اختبار قاعدة البيانات:</h2>";
define('ACCESS_ALLOWED', true);

try {
    require_once __DIR__ . '/config/config.php';
    echo "<span class='success'>✓ تم تحميل config.php بنجاح</span><br>";
    
    try {
        $db = getDB();
        echo "<span class='success'>✓ الاتصال بقاعدة البيانات نجح!</span><br>";
        
        // اختبار استعلام بسيط
        $stmt = $db->query("SELECT 1");
        echo "<span class='success'>✓ يمكن تنفيذ الاستعلامات</span><br>";
    } catch (Exception $e) {
        echo "<span class='error'>✗ خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        echo "<p>تفاصيل الخطأ:</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ خطأ في تحميل config.php: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<p>تفاصيل الخطأ:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// اختبار 3: SITE_URL
echo "<div class='info'>";
echo "<h2>اختبار SITE_URL:</h2>";
if (defined('SITE_URL')) {
    echo "SITE_URL: <strong>" . SITE_URL . "</strong><br>";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'غير محدد') . "<br>";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'غير محدد') . "<br>";
} else {
    echo "<span class='error'>✗ SITE_URL غير معرف</span><br>";
}
echo "</div>";

// اختبار 4: الملفات
echo "<div class='info'>";
echo "<h2>اختبار الملفات:</h2>";
$files = [
    'config/config.php',
    'config/database.php',
    'includes/auth.php',
    'index.php',
    '.htaccess'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<span class='success'>✓</span> $file موجود<br>";
    } else {
        echo "<span class='error'>✗</span> $file غير موجود<br>";
    }
}
echo "</div>";

// اختبار 5: المجلدات
echo "<div class='info'>";
echo "<h2>اختبار المجلدات:</h2>";
$dirs = [
    'config',
    'includes',
    'assets',
    'admin',
    'auth'
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "<span class='success'>✓</span> مجلد $dir موجود<br>";
    } else {
        echo "<span class='error'>✗</span> مجلد $dir غير موجود<br>";
    }
}
echo "</div>";

// معلومات إضافية
echo "<div class='info'>";
echo "<h2>معلومات إضافية:</h2>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد') . "<br>";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'غير محدد') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'غير محدد') . "<br>";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد') . "<br>";
echo "</div>";

echo "<h2>✅ انتهى الاختبار</h2>";
echo "<p><strong>ملاحظة:</strong> احذف هذا الملف بعد الانتهاء من الاختبار لأسباب أمنية.</p>";
echo "</body>";
echo "</html>";
?>

