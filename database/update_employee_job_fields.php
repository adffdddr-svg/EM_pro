<?php
/**
 * Employee Management System
 * تحديث الحقول الوظيفية للموظفين تلقائياً
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
    <title>تحديث الحقول الوظيفية للموظفين</title>
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
        <h1>تحديث الحقول الوظيفية للموظفين</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'>جاري تحديث الحقول الوظيفية...</div>";
            
            // الحقول الجديدة المطلوبة (جميع الحقول)
            $new_fields = [
                'certificate' => "VARCHAR(200) NULL COMMENT 'الشهادة'",
                'certificate_date' => "DATE NULL COMMENT 'تاريخ الحصول على الشهادة'",
                'title' => "VARCHAR(200) NULL COMMENT 'اللقب'",
                'title_date' => "DATE NULL COMMENT 'تاريخ الحصول على اللقب'",
                'current_salary' => "DECIMAL(10, 2) NULL COMMENT 'الراتب الحالي'",
                'new_salary' => "DECIMAL(10, 2) NULL COMMENT 'الراتب الجديد'",
                'last_raise_date' => "DATE NULL COMMENT 'تاريخ آخر زيادة'",
                'entitlement_date' => "DATE NULL COMMENT 'تاريخ الاستحقاق'",
                'grade_entry_date' => "DATE NULL COMMENT 'تاريخ الدخول بدرجة'",
                'last_promotion_date' => "DATE NULL COMMENT 'تاريخ آخر ترفيع'",
                'last_promotion_number' => "VARCHAR(50) NULL COMMENT 'رقم آخر ترفيع'",
                'job_notes' => "TEXT NULL COMMENT 'ملاحظات وظيفية'"
            ];
            
            // إضافة حقول إضافية قد تكون مفقودة
            $additional_fields = [
                'full_name' => "VARCHAR(200) NULL COMMENT 'الاسم الكامل'",
                'specialization' => "VARCHAR(200) NULL COMMENT 'التخصص'"
            ];
            
            // دمج الحقول
            $all_fields = array_merge($new_fields, $additional_fields);
            
            $added = 0;
            $skipped = 0;
            $errors = [];
            
            // دالة للحصول على قائمة الحقول
            $getExistingColumns = function($db) {
                $columns = [];
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM employees");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = $row['Field'];
                    }
                } catch (PDOException $e) {
                    // تجاهل الخطأ
                }
                return $columns;
            };
            
            // الحصول على قائمة الحقول الموجودة
            $existing_columns = $getExistingColumns($db);
            
            // التحقق من وجود الحقول وإضافتها
            foreach ($all_fields as $field_name => $field_definition) {
                try {
                    // التحقق من وجود الحقل
                    if (in_array($field_name, $existing_columns)) {
                        echo "<div class='info'>ℹ الحقل '$field_name' موجود مسبقاً</div>";
                        $skipped++;
                    } else {
                        // إضافة الحقل
                        $db->exec("ALTER TABLE employees ADD COLUMN $field_name $field_definition");
                        echo "<div class='success'>✓ تم إضافة الحقل '$field_name'</div>";
                        $added++;
                        // تحديث قائمة الحقول بعد الإضافة
                        $existing_columns = $getExistingColumns($db);
                    }
                } catch (PDOException $e) {
                    $error_msg = $e->getMessage();
                    if (stripos($error_msg, 'Duplicate column') !== false || 
                        stripos($error_msg, 'already exists') !== false ||
                        stripos($error_msg, 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ الحقل '$field_name' موجود مسبقاً</div>";
                        $skipped++;
                    } else {
                        $errors[] = "خطأ في إضافة الحقل '$field_name': " . $error_msg;
                        echo "<div class='error'>✗ خطأ في إضافة الحقل '$field_name': " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
                    }
                }
            }
            
            // نسخ الراتب الحالي من salary إذا كان current_salary فارغ
            try {
                $stmt = $db->query("UPDATE employees SET current_salary = salary WHERE current_salary IS NULL AND salary > 0");
                $updated = $stmt->rowCount();
                if ($updated > 0) {
                    echo "<div class='success'>✓ تم نسخ الراتب الحالي لـ $updated موظف</div>";
                }
            } catch (PDOException $e) {
                // تجاهل الخطأ
            }
            
            // التحقق النهائي من وجود جميع الحقول (إعادة قراءة القائمة)
            $final_columns = $getExistingColumns($db);
            $all_fields_exist = true;
            $missing_fields = [];
            foreach (array_keys($all_fields) as $field_name) {
                if (!in_array($field_name, $final_columns)) {
                    $all_fields_exist = false;
                    $missing_fields[] = $field_name;
                }
            }
            
            if ($all_fields_exist) {
                echo "<div class='success'><strong>✓ تمت العملية بنجاح!</strong></div>";
                echo "<p>تم إضافة $added حقول جديد، وتم تخطي $skipped حقول موجودة مسبقاً.</p>";
                echo "<p><strong>جميع الحقول الوظيفية موجودة الآن في قاعدة البيانات.</strong></p>";
                echo "<p style='margin-top: 20px;'><strong>سيتم إعادة توجيهك تلقائياً إلى صفحة إضافة الموظف خلال 3 ثوان...</strong></p>";
                echo "<a href='" . SITE_URL . "/admin/employees/add.php' class='btn' style='background: #28a745; margin-left: 10px;'>إضافة موظف جديد الآن</a>";
                echo "<a href='" . SITE_URL . "/admin/employees/index.php' class='btn'>الذهاب إلى صفحة الموظفين</a>";
                
                // إعادة التوجيه التلقائي بعد 3 ثوان
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '" . SITE_URL . "/admin/employees/add.php';
                    }, 3000);
                </script>";
            } else {
                echo "<div class='error'><strong>⚠ تحذير:</strong> بعض الحقول لم يتم إضافتها: " . implode(', ', $missing_fields) . "</div>";
                if ($added > 0) {
                    echo "<p>تم إضافة $added حقول جديد، وتم تخطي $skipped حقول موجودة مسبقاً.</p>";
                }
            }
            
            if (!empty($errors)) {
                echo "<div class='error'><strong>الأخطاء:</strong><pre>" . implode("\n", $errors) . "</pre></div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'><strong>خطأ:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>

