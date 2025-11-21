<?php
/**
 * Employee Management System
 * حل مباشر لمشكلة الحقول - إضافة جميع الحقول مباشرة
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
    <title>إصلاح الحقول - حل مباشر</title>
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
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 16px;
        }
        th, td {
            padding: 15px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-size: 18px;
        }
        .exists { color: #28a745; font-weight: bold; font-size: 18px; }
        .missing { color: #dc3545; font-weight: bold; font-size: 18px; }
        .added { color: #17a2b8; font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 إصلاح الحقول الوظيفية - حل مباشر</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'><strong>⚙️ جاري فحص وإصلاح الحقول...</strong></div>";
            
            // الحصول على الحقول الموجودة
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $existing_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_columns[] = $row['Field'];
            }
            
            // جميع الحقول المطلوبة - بدون تعليقات لتجنب المشاكل
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
                        // إضافة الحقل مباشرة
                        $sql = "ALTER TABLE employees ADD COLUMN `$field_name` $field_definition";
                        $db->exec($sql);
                        echo "<tr><td><strong>$field_name</strong></td><td class='added'>✓ تم إضافته بنجاح</td></tr>";
                        $added++;
                        $existing_columns[] = $field_name;
                    } catch (PDOException $e) {
                        $error_msg = $e->getMessage();
                        // تجاهل أخطاء "موجود مسبقاً"
                        if (stripos($error_msg, 'Duplicate') !== false || 
                            stripos($error_msg, 'already exists') !== false ||
                            stripos($error_msg, 'Duplicate column') !== false) {
                            echo "<tr><td><strong>$field_name</strong></td><td class='exists'>✓ موجود (تم تجاهل الخطأ)</td></tr>";
                            $skipped++;
                            if (!in_array($field_name, $existing_columns)) {
                                $existing_columns[] = $field_name;
                            }
                        } else {
                            $errors[] = "$field_name: " . $error_msg;
                            echo "<tr><td><strong>$field_name</strong></td><td class='missing'>✗ خطأ: " . htmlspecialchars(substr($error_msg, 0, 80)) . "</td></tr>";
                        }
                    }
                }
            }
            echo "</table>";
            
            // التحقق النهائي - إعادة قراءة الحقول
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $final_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $final_columns[] = $row['Field'];
            }
            
            $missing = array_diff(array_keys($required_fields), $final_columns);
            
            if (empty($missing)) {
                echo "<div class='success'>";
                echo "<h2 style='font-size: 24px; margin-bottom: 15px;'>✅ تم إصلاح المشكلة بنجاح!</h2>";
                echo "<p style='font-size: 18px; margin: 10px 0;'><strong>✓ تم إضافة $added حقول جديد</strong></p>";
                echo "<p style='font-size: 18px; margin: 10px 0;'><strong>✓ تم تخطي $skipped حقول موجودة مسبقاً</strong></p>";
                echo "<p style='font-size: 20px; margin-top: 20px; color: #155724; font-weight: bold;'>✅ جميع الحقول الوظيفية موجودة الآن في قاعدة البيانات!</p>";
                echo "<p style='font-size: 18px; margin-top: 15px;'><strong>يمكنك الآن إضافة موظف جديد بدون أي مشاكل.</strong></p>";
                echo "</div>";
                
                echo "<div style='text-align: center; margin-top: 40px;'>";
                echo "<a href='" . SITE_URL . "/admin/employees/add.php' class='btn' onclick='setTimeout(function(){location.reload();}, 100);'>";
                echo "✅ الذهاب إلى صفحة إضافة الموظف الآن";
                echo "</a>";
                echo "</div>";
                
                // إضافة JavaScript لإعادة التوجيه التلقائي
                echo "<script>";
                echo "setTimeout(function() {";
                echo "  window.location.href = '" . SITE_URL . "/admin/employees/add.php';";
                echo "}, 3000);";
                echo "</script>";
                
            } else {
                echo "<div class='error'>";
                echo "<h2>⚠ تحذير: بعض الحقول لم تُضف</h2>";
                echo "<p style='font-size: 18px;'><strong>الحقول الناقصة:</strong> " . implode(', ', $missing) . "</p>";
                if ($added > 0) {
                    echo "<p style='font-size: 16px;'>تم إضافة $added حقول جديد</p>";
                }
                echo "</div>";
            }
            
            if (!empty($errors)) {
                echo "<div class='error'>";
                echo "<h3>الأخطاء:</h3>";
                echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>" . implode("\n", $errors) . "</pre>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>❌ خطأ:</h2>";
            echo "<p style='font-size: 18px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='margin-top: 15px;'>يرجى التحقق من:</p>";
            echo "<ul style='margin-top: 10px; padding-right: 30px;'>";
            echo "<li>WAMP Server يعمل</li>";
            echo "<li>MySQL يعمل</li>";
            echo "<li>قاعدة البيانات employee_management موجودة</li>";
            echo "<li>جدول employees موجود</li>";
            echo "</ul>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>

