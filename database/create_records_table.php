<?php
/**
 * Employee Management System
 * إنشاء جداول السجلات تلقائياً
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../config/config.php';

$db = getDB();

try {
    // قراءة ملف SQL
    $sql_file = __DIR__ . '/records_schema.sql';
    
    if (!file_exists($sql_file)) {
        die('❌ ملف SQL غير موجود: ' . $sql_file);
    }
    
    $sql = file_get_contents($sql_file);
    
    // تقسيم SQL إلى أوامر منفصلة
    $lines = explode("\n", $sql);
    $statements = [];
    $current_statement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // تجاهل التعليقات والأسطر الفارغة
        if (empty($line) || preg_match('/^\s*--/', $line) || preg_match('/^\s*USE\s+/i', $line)) {
            continue;
        }
        
        $current_statement .= $line . "\n";
        
        // إذا انتهى الأمر بفاصلة منقوطة
        if (substr(rtrim($line), -1) === ';') {
            $stmt = trim($current_statement);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current_statement = '';
        }
    }
    
    // إضافة آخر أمر إذا لم ينته بفاصلة منقوطة
    if (!empty(trim($current_statement))) {
        $statements[] = trim($current_statement);
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $success_count++;
        } catch (PDOException $e) {
            // تجاهل خطأ "الجدول موجود مسبقاً"
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                $error_count++;
                $errors[] = [
                    'statement' => substr($statement, 0, 150),
                    'error' => $e->getMessage()
                ];
            } else {
                $success_count++;
            }
        }
    }
    
    echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>إنشاء جداول السجلات</title>";
    echo "<style>body{font-family:Arial;padding:20px;direction:rtl;} .success{color:green;} .error{color:red;}</style></head><body>";
    
    echo "<h2>✅ تم إنشاء جداول السجلات!</h2>";
    echo "<p class='success'>✅ تم تنفيذ $success_count أمر بنجاح</p>";
    
    if ($error_count > 0) {
        echo "<p class='error'>⚠️ حدث $error_count خطأ:</p><ul>";
        foreach ($errors as $err) {
            echo "<li><strong>الخطأ:</strong> " . htmlspecialchars($err['error']) . "<br>";
            echo "<strong>الأمر:</strong> " . htmlspecialchars($err['statement']) . "...</li>";
        }
        echo "</ul>";
    }
    
    // التحقق من إنشاء الجداول بنجاح
    try {
        $db->query("SELECT 1 FROM employee_records LIMIT 1");
        $db->query("SELECT 1 FROM record_details LIMIT 1");
        echo "<p class='success'>✅ تم التحقق: جميع الجداول موجودة الآن!</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>⚠️ تحذير: بعض الجداول قد لا تكون موجودة. يرجى التحقق يدوياً.</p>";
    }
    
    echo "<p><a href='" . SITE_URL . "/admin/records/index.php' style='display:inline-block;padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>➡️ الانتقال إلى صفحة السجلات</a></p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    die("❌ خطأ: " . $e->getMessage());
}

