<?php
/**
 * فحص قاعدة البيانات
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

echo "<h2>فحص قاعدة البيانات</h2>";
echo "<pre>";

try {
    // الاتصال بـ MySQL
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ الاتصال بـ MySQL نجح\n\n";
    
    // التحقق من وجود قاعدة البيانات
    $stmt = $pdo->query("SHOW DATABASES LIKE 'employee_management'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "✓ قاعدة البيانات 'employee_management' موجودة\n\n";
        
        // الاتصال بقاعدة البيانات
        $pdo->exec("USE employee_management");
        
        // التحقق من الجداول
        $tables = ['users', 'departments', 'employees', 'employees_archive'];
        
        echo "التحقق من الجداول:\n";
        echo "-------------------\n";
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $table_exists = $stmt->fetch();
            
            if ($table_exists) {
                // التحقق من عدد السجلات
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "✓ جدول '$table' موجود - عدد السجلات: $count\n";
            } else {
                echo "✗ جدول '$table' غير موجود\n";
            }
        }
        
    } else {
        echo "✗ قاعدة البيانات 'employee_management' غير موجودة\n";
        echo "\nالحل: قم بتشغيل install.php لإنشاء قاعدة البيانات\n";
    }
    
} catch (PDOException $e) {
    echo "✗ خطأ: " . $e->getMessage() . "\n";
    echo "\nتأكد من:\n";
    echo "1. تشغيل WAMP Server\n";
    echo "2. تشغيل MySQL\n";
    echo "3. إعدادات الاتصال صحيحة\n";
}

echo "</pre>";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فحص قاعدة البيانات</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #2c3e50;
        }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <a href="install.php" class="btn">إنشاء قاعدة البيانات الآن</a>
</body>
</html>

