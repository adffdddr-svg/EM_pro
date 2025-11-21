<?php
/**
 * Employee Management System
 * إصلاح مشكلة الحقول الوظيفية - حل مباشر
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إصلاح الحقول الوظيفية</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 16px;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 16px;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .exists { color: #28a745; font-weight: bold; }
        .missing { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 إصلاح الحقول الوظيفية</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'><strong>جاري فحص وإصلاح الحقول...</strong></div>";
            
            // الحصول على الحقول الموجودة
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $existing_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_columns[] = $row['Field'];
            }
            
            // جميع الحقول المطلوبة
            $required_fields = [
                'certificate' => "VARCHAR(200) NULL",
                'certificate_date' => "DATE NULL",
                'title' => "VARCHAR(200) NULL",
                'title_date' => "DATE NULL",
                'current_salary' => "DECIMAL(10, 2) NULL",
                'new_salary' => "DECIMAL(10, 2) NULL",
                'last_raise_date' => "DATE NULL",
                'entitlement_date' => "DATE NULL",
                'grade_entry_date' => "DATE NULL",
                'last_promotion_date' => "DATE NULL",
                'last_promotion_number' => "VARCHAR(50) NULL",
                'job_notes' => "TEXT NULL"
            ];
            
            $added = 0;
            $skipped = 0;
            $errors = [];
            
            echo "<table>";
            echo "<tr><th>اسم الحقل</th><th>الحالة</th></tr>";
            
            foreach ($required_fields as $field_name => $field_definition) {
                if (in_array($field_name, $existing_columns)) {
                    echo "<tr><td><strong>$field_name</strong></td><td class='exists'>✓ موجود</td></tr>";
                    $skipped++;
                } else {
                    try {
                        $db->exec("ALTER TABLE employees ADD COLUMN $field_name $field_definition");
                        echo "<tr><td><strong>$field_name</strong></td><td class='missing'>✓ تم إضافته</td></tr>";
                        $added++;
                        $existing_columns[] = $field_name;
                    } catch (PDOException $e) {
                        $error_msg = $e->getMessage();
                        if (stripos($error_msg, 'Duplicate') === false && stripos($error_msg, 'already exists') === false) {
                            $errors[] = "$field_name: " . $error_msg;
                            echo "<tr><td><strong>$field_name</strong></td><td class='missing'>✗ خطأ: " . htmlspecialchars(substr($error_msg, 0, 50)) . "</td></tr>";
                        } else {
                            echo "<tr><td><strong>$field_name</strong></td><td class='exists'>✓ موجود</td></tr>";
                            $skipped++;
                        }
                    }
                }
            }
            echo "</table>";
            
            // التحقق النهائي
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $final_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $final_columns[] = $row['Field'];
            }
            
            $missing = array_diff(array_keys($required_fields), $final_columns);
            
            if (empty($missing)) {
                echo "<div class='success'>";
                echo "<h2>✅ تم إصلاح المشكلة بنجاح!</h2>";
                echo "<p><strong>تم إضافة $added حقول جديد</strong></p>";
                echo "<p><strong>تم تخطي $skipped حقول موجودة مسبقاً</strong></p>";
                echo "<p style='margin-top: 20px;'><strong>جميع الحقول الوظيفية موجودة الآن في قاعدة البيانات.</strong></p>";
                echo "<p style='margin-top: 20px; color: #155724;'><strong>✅ يمكنك الآن إضافة موظف جديد بدون مشاكل!</strong></p>";
                echo "</div>";
                
                echo "<div style='text-align: center; margin-top: 30px;'>";
                echo "<a href='" . SITE_URL . "/admin/employees/add.php' class='btn btn-success'>";
                echo "✅ الذهاب إلى صفحة إضافة الموظف";
                echo "</a>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<h2>⚠ تحذير: بعض الحقول لم تُضف</h2>";
                echo "<p>الحقول الناقصة: " . implode(', ', $missing) . "</p>";
                if ($added > 0) {
                    echo "<p>تم إضافة $added حقول جديد</p>";
                }
                echo "</div>";
            }
            
            if (!empty($errors)) {
                echo "<div class='error'>";
                echo "<h3>الأخطاء:</h3>";
                echo "<pre>" . implode("\n", $errors) . "</pre>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>❌ خطأ:</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>

