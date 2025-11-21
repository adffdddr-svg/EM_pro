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
echo ".success { color: green; font-weight: bold; }";
echo ".error { color: red; font-weight: bold; }";
echo ".info { background: white; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #667eea; }";
echo "pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 اختبار النظام - Debug Mode</h1>";

// اختبار 1: PHP
echo "<div class='info'>";
echo "<h2>✓ PHP يعمل</h2>";
echo "إصدار PHP: <strong>" . phpversion() . "</strong><br>";
echo "نظام التشغيل: " . PHP_OS . "<br>";
echo "</div>";

// اختبار 2: config.php
echo "<div class='info'>";
echo "<h2>اختبار config.php:</h2>";
define('ACCESS_ALLOWED', true);

try {
    require_once __DIR__ . '/config/config.php';
    echo "<span class='success'>✓ تم تحميل config.php بنجاح</span><br>";
    
    if (defined('SITE_URL')) {
        echo "SITE_URL: <strong>" . SITE_URL . "</strong><br>";
    } else {
        echo "<span class='error'>✗ SITE_URL غير معرف</span><br>";
    }
    
    if (defined('DB_HOST')) {
        echo "DB_HOST: " . DB_HOST . "<br>";
        echo "DB_NAME: " . DB_NAME . "<br>";
        echo "DB_USER: " . DB_USER . "<br>";
    }
} catch (Throwable $e) {
    echo "<span class='error'>✗ خطأ في config.php:</span><br>";
    echo "<strong>" . htmlspecialchars($e->getMessage()) . "</strong><br>";
    echo "<p>في الملف: " . htmlspecialchars($e->getFile()) . " السطر: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// اختبار 3: database.php
echo "<div class='info'>";
echo "<h2>اختبار database.php:</h2>";
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        echo "<span class='success'>✓ تم تحميل database.php</span><br>";
        
        try {
            $db = getDB();
            echo "<span class='success'>✓ الاتصال بقاعدة البيانات نجح!</span><br>";
            
            // اختبار استعلام بسيط
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<span class='success'>✓ يمكن تنفيذ الاستعلامات</span><br>";
        } catch (Exception $e) {
            echo "<span class='error'>✗ خطأ في قاعدة البيانات:</span><br>";
            echo "<strong>" . htmlspecialchars($e->getMessage()) . "</strong><br>";
            echo "<p>في الملف: " . htmlspecialchars($e->getFile()) . " السطر: " . $e->getLine() . "</p>";
        }
    } else {
        echo "<span class='error'>✗ ملف database.php غير موجود</span><br>";
    }
} catch (Throwable $e) {
    echo "<span class='error'>✗ خطأ في تحميل database.php:</span><br>";
    echo "<strong>" . htmlspecialchars($e->getMessage()) . "</strong><br>";
    echo "<p>في الملف: " . htmlspecialchars($e->getFile()) . " السطر: " . $e->getLine() . "</p>";
}
echo "</div>";

// اختبار 4: auth.php
echo "<div class='info'>";
echo "<h2>اختبار auth.php:</h2>";
try {
    if (file_exists(__DIR__ . '/includes/auth.php')) {
        require_once __DIR__ . '/includes/auth.php';
        echo "<span class='success'>✓ تم تحميل auth.php</span><br>";
        
        if (function_exists('isLoggedIn')) {
            echo "<span class='success'>✓ دالة isLoggedIn() موجودة</span><br>";
        } else {
            echo "<span class='error'>✗ دالة isLoggedIn() غير موجودة</span><br>";
        }
    } else {
        echo "<span class='error'>✗ ملف auth.php غير موجود</span><br>";
    }
} catch (Throwable $e) {
    echo "<span class='error'>✗ خطأ في auth.php:</span><br>";
    echo "<strong>" . htmlspecialchars($e->getMessage()) . "</strong><br>";
    echo "<p>في الملف: " . htmlspecialchars($e->getFile()) . " السطر: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// معلومات إضافية
echo "<div class='info'>";
echo "<h2>معلومات إضافية:</h2>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد') . "<br>";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'غير محدد') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'غير محدد') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'غير محدد') . "<br>";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد') . "<br>";
echo "</div>";

echo "<h2>✅ انتهى الاختبار</h2>";
echo "<p><strong>ملاحظة:</strong> بعد إصلاح المشكلة، احذف هذا الملف وأعد تسمية index.php الأصلي.</p>";
echo "</body>";
echo "</html>";
?>

