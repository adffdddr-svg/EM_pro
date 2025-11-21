<?php
/**
 * Employee Management System
 * إضافة الحقول الجديدة لجدول الموظفين تلقائياً
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
    <title>إضافة الحقول الجديدة للموظفين</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 10px;
            text-align: right;
            border: 1px solid #ddd;
        }
        table th {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إضافة الحقول الجديدة للموظفين</h1>
        
        <?php
        try {
            $db = getDB();
            
            echo "<div class='info'>جاري إضافة الحقول الجديدة...</div>";
            
            // الحقول المطلوبة
            $new_fields = [
                'specialization' => "VARCHAR(200) NULL COMMENT 'التخصص/النظام'",
                'degree' => "VARCHAR(100) NULL COMMENT 'الدرجة العلمية'",
                'role_type' => "ENUM('مدرس', 'مدرس مساعد', 'مساعد', 'أستاذ', 'أستاذ مساعد', 'محاضر', 'أخرى') NULL COMMENT 'نوع الدور/المنصب'",
                'field_of_study' => "VARCHAR(200) NULL COMMENT 'مجال الدراسة'",
                'score_1' => "INT NULL COMMENT 'الدرجة/النقاط الأولى'",
                'score_2' => "INT NULL COMMENT 'الدرجة/النقاط الثانية'",
                'appointment_date' => "DATE NULL COMMENT 'تاريخ التعيين'",
                'appointment_status' => "VARCHAR(100) NULL COMMENT 'حالة التعيين'",
                'seniority_grant_months' => "INT NULL DEFAULT 0 COMMENT 'منح القدم بالأشهر'",
                'seniority_grant_date' => "DATE NULL COMMENT 'تاريخ منح القدم'",
                'full_name' => "VARCHAR(200) NULL COMMENT 'الاسم الكامل'"
            ];
            
            $added = 0;
            $skipped = 0;
            $errors = [];
            
            // التحقق من وجود الحقول وإضافتها
            foreach ($new_fields as $field_name => $field_definition) {
                try {
                    // التحقق من وجود الحقل
                    $stmt = $db->query("SHOW COLUMNS FROM employees LIKE '$field_name'");
                    if ($stmt->fetch()) {
                        echo "<div class='info'>ℹ الحقل '$field_name' موجود مسبقاً</div>";
                        $skipped++;
                    } else {
                        // إضافة الحقل
                        $db->exec("ALTER TABLE employees ADD COLUMN $field_name $field_definition");
                        echo "<div class='success'>✓ تم إضافة الحقل '$field_name'</div>";
                        $added++;
                    }
                } catch (PDOException $e) {
                    $error_msg = $e->getMessage();
                    if (stripos($error_msg, 'Duplicate column') === false) {
                        $errors[] = "خطأ في إضافة الحقل '$field_name': " . $error_msg;
                        echo "<div class='error'>✗ خطأ في إضافة الحقل '$field_name': " . htmlspecialchars(substr($error_msg, 0, 100)) . "</div>";
                    } else {
                        echo "<div class='info'>ℹ الحقل '$field_name' موجود مسبقاً</div>";
                        $skipped++;
                    }
                }
            }
            
            // تحديث full_name للموظفين الموجودين
            try {
                $stmt = $db->query("UPDATE employees SET full_name = CONCAT(first_name, ' ', last_name) WHERE full_name IS NULL OR full_name = ''");
                $updated = $stmt->rowCount();
                if ($updated > 0) {
                    echo "<div class='success'>✓ تم تحديث الاسم الكامل لـ $updated موظف</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>⚠ تحذير: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
            }
            
            // عرض الحقول الحالية
            echo "<h2 style='margin-top: 30px;'>الحقول الحالية في جدول الموظفين:</h2>";
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $columns = $stmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>اسم الحقل</th><th>النوع</th><th>السماح بالفراغ</th><th>القيمة الافتراضية</th></tr>";
            foreach ($columns as $col) {
                $null = $col['Null'] == 'YES' ? 'نعم' : 'لا';
                $default = $col['Default'] !== null ? $col['Default'] : '-';
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>$null</td>";
                echo "<td>$default</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($added > 0 || $skipped > 0) {
                echo "<div class='success'><strong>✓ تمت العملية بنجاح!</strong></div>";
                echo "<p>تم إضافة $added حقول جديد، وتم تخطي $skipped حقول موجودة مسبقاً.</p>";
                echo "<a href='" . SITE_URL . "/admin/employees/index.php' class='btn'>الذهاب إلى صفحة الموظفين</a>";
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

