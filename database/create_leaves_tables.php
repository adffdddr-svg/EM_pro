<?php
/**
 * Employee Management System
 * إنشاء جداول نظام الإجازات تلقائياً
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء جداول نظام الإجازات</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إنشاء جداول نظام الإجازات</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'>جاري إنشاء الجداول...</div>";
            
            // قراءة ملف SQL
            $sql_file = __DIR__ . '/leaves_schema.sql';
            
            if (!file_exists($sql_file)) {
                throw new Exception("ملف SQL غير موجود: $sql_file");
            }
            
            $sql_content = file_get_contents($sql_file);
            
            // تقسيم SQL إلى statements بشكل أفضل
            // إزالة التعليقات أولاً
            $sql_content = preg_replace('/--.*$/m', '', $sql_content);
            $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
            
            // تقسيم حسب الفاصلة المنقوطة
            $statements = [];
            $parts = explode(';', $sql_content);
            
            foreach ($parts as $part) {
                $part = trim($part);
                
                // تجاهل الأسطر الفارغة والتعليقات
                if (empty($part) || 
                    strlen($part) < 10 ||
                    stripos($part, 'USE ') === 0 ||
                    (stripos($part, 'SELECT ') === 0 && stripos($part, 'SELECT COUNT') === false)) {
                    continue;
                }
                
                // إضافة فقط CREATE TABLE و INSERT INTO
                if (stripos($part, 'CREATE TABLE') !== false || 
                    stripos($part, 'INSERT INTO') !== false) {
                    $statements[] = $part;
                }
            }
            
            $created = 0;
            $errors = [];
            
            foreach ($statements as $statement) {
                try {
                    // إزالة أي مسافات زائدة
                    $statement = trim($statement);
                    if (empty($statement)) continue;
                    
                    $db->exec($statement);
                    $created++;
                    $stmt_type = stripos($statement, 'CREATE TABLE') !== false ? 'CREATE TABLE' : 'INSERT';
                    $table_name = '';
                    if (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                        $table_name = $matches[1];
                    } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                        $table_name = $matches[1];
                    }
                    echo "<div class='success'>✓ تم تنفيذ: $stmt_type " . ($table_name ? "($table_name)" : "") . "</div>";
                } catch (PDOException $e) {
                    $error_msg = $e->getMessage();
                    // تجاهل الأخطاء المتعلقة بالموجود مسبقاً
                    if (stripos($error_msg, 'already exists') === false && 
                        stripos($error_msg, 'Duplicate') === false &&
                        stripos($error_msg, 'Duplicate entry') === false &&
                        stripos($error_msg, 'Duplicate key') === false) {
                        $errors[] = $error_msg;
                        echo "<div class='error'>⚠ تحذير: " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
                    } else {
                        echo "<div class='info'>ℹ تم تخطي (موجود مسبقاً)</div>";
                    }
                }
            }
            
            // التحقق من إنشاء الجداول
            $tables_to_check = ['employee_leaves', 'leave_approvals', 'leave_balance'];
            $all_exist = true;
            
            foreach ($tables_to_check as $table) {
                try {
                    $stmt = $db->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->fetch()) {
                        echo "<div class='success'>✓ جدول '$table' موجود</div>";
                    } else {
                        echo "<div class='error'>✗ جدول '$table' غير موجود</div>";
                        $all_exist = false;
                    }
                } catch (PDOException $e) {
                    echo "<div class='error'>✗ خطأ في التحقق من جدول '$table': " . $e->getMessage() . "</div>";
                    $all_exist = false;
                }
            }
            
            // إدراج رصيد افتراضي للموظفين الموجودين
            try {
                $stmt = $db->query("SELECT id FROM employees WHERE id NOT IN (SELECT employee_id FROM leave_balance WHERE employee_id IS NOT NULL)");
                $employees = $stmt->fetchAll();
                
                if (count($employees) > 0) {
                    $inserted = 0;
                    foreach ($employees as $emp) {
                        try {
                            $stmt = $db->prepare("INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance) VALUES (?, 104, 2, 104)");
                            $stmt->execute([$emp['id']]);
                            $inserted++;
                        } catch (PDOException $e) {
                            // تجاهل الأخطاء
                        }
                    }
                    if ($inserted > 0) {
                        echo "<div class='success'>✓ تم إدراج رصيد افتراضي لـ $inserted موظف</div>";
                    }
                }
            } catch (PDOException $e) {
                // تجاهل الخطأ
            }
            
            if ($all_exist) {
                echo "<div class='success'><strong>✓ تم إنشاء جميع الجداول بنجاح!</strong></div>";
                echo "<a href='" . SITE_URL . "/admin/leaves/index.php' class='btn'>الذهاب إلى صفحة الإجازات</a>";
            } else {
                echo "<div class='error'><strong>⚠ بعض الجداول لم يتم إنشاؤها. يرجى التحقق من الأخطاء أعلاه.</strong></div>";
            }
            
            if (!empty($errors)) {
                echo "<div class='error'><strong>الأخطاء:</strong><pre>" . implode("\n", $errors) . "</pre></div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>خطأ:</strong> " . $e->getMessage() . "</div>";
            echo "<div class='info'>يرجى تشغيل ملف <code>database/leaves_schema.sql</code> يدوياً في phpMyAdmin</div>";
        }
        ?>
    </div>
</body>
</html>

