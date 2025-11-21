<?php
/**
 * Employee Management System
 * إنشاء جدول سجل الرواتب تلقائياً
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء جدول سجل الرواتب</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .success {
            background: #d4edda;
            border: 3px solid #28a745;
            color: #155724;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
            text-align: center;
        }
        .error {
            background: #f8d7da;
            border: 3px solid #dc3545;
            color: #721c24;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            padding: 18px 35px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin: 15px 10px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
        }
        .btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(40, 167, 69, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 إنشاء جدول سجل الرواتب</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'><strong>⚙️ جاري إنشاء جدول سجل الرواتب...</strong></div>";
            
            // قراءة ملف SQL
            $sql_file = __DIR__ . '/salary_schema.sql';
            if (!file_exists($sql_file)) {
                throw new Exception('ملف SQL غير موجود: ' . $sql_file);
            }
            
            $sql = file_get_contents($sql_file);
            
            // تقسيم SQL إلى أوامر منفصلة
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^\s*--/', $stmt) && 
                           !preg_match('/^\s*\/\*/', $stmt);
                }
            );
            
            $executed = 0;
            $skipped = 0;
            
            foreach ($statements as $statement) {
                if (empty(trim($statement))) continue;
                
                try {
                    $db->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    // تجاهل أخطاء "موجود مسبقاً"
                    if (strpos($e->getMessage(), 'already exists') !== false || 
                        strpos($e->getMessage(), 'Duplicate') !== false) {
                        $skipped++;
                    } else {
                        throw $e;
                    }
                }
            }
            
            echo "<div class='success'>";
            echo "<h2 style='font-size: 24px; margin-bottom: 15px;'>✅ تم إنشاء جدول سجل الرواتب بنجاح!</h2>";
            echo "<p style='font-size: 18px; margin: 10px 0;'><strong>✓ تم تنفيذ $executed أمر SQL</strong></p>";
            if ($skipped > 0) {
                echo "<p style='font-size: 18px; margin: 10px 0;'><strong>✓ تم تخطي $skipped أوامر موجودة مسبقاً</strong></p>";
            }
            echo "<p style='font-size: 20px; margin-top: 20px; color: #155724; font-weight: bold;'>✅ جدول سجل الرواتب جاهز الآن!</p>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin-top: 40px;'>";
            echo "<a href='" . SITE_URL . "/admin/salaries/index.php' class='btn'>";
            echo "✅ الذهاب إلى صفحة الرواتب الآن";
            echo "</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>❌ خطأ:</h2>";
            echo "<p style='font-size: 18px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='margin-top: 15px;'>يرجى التحقق من:</p>";
            echo "<ul style='margin-top: 10px; padding-right: 30px;'>";
            echo "<li>WAMP Server يعمل</li>";
            echo "<li>MySQL يعمل</li>";
            echo "<li>قاعدة البيانات employee_management موجودة</li>";
            echo "</ul>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>

