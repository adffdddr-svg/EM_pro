<?php
/**
 * Employee Management System
 * فحص الحقول الموجودة في جدول الموظفين
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

$db = getDB();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فحص الحقول الوظيفية</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
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
        .exists {
            color: #28a745;
            font-weight: bold;
        }
        .missing {
            color: #dc3545;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>فحص الحقول الوظيفية في جدول الموظفين</h1>
        
        <?php
        try {
            // الحصول على جميع الحقول الموجودة
            $stmt = $db->query("SHOW COLUMNS FROM employees");
            $existing_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_columns[] = $row['Field'];
            }
            
            // الحقول المطلوبة
            $required_fields = [
                'certificate' => 'الشهادة',
                'certificate_date' => 'تاريخ الحصول على الشهادة',
                'title' => 'اللقب',
                'title_date' => 'تاريخ الحصول على اللقب',
                'current_salary' => 'الراتب الحالي',
                'new_salary' => 'الراتب الجديد',
                'last_raise_date' => 'تاريخ آخر زيادة',
                'entitlement_date' => 'تاريخ الاستحقاق',
                'grade_entry_date' => 'تاريخ الدخول بدرجة',
                'last_promotion_date' => 'تاريخ آخر ترفيع',
                'last_promotion_number' => 'رقم آخر ترفيع',
                'job_notes' => 'ملاحظات وظيفية'
            ];
            
            echo "<h2>نتيجة الفحص:</h2>";
            echo "<table>";
            echo "<tr><th>اسم الحقل</th><th>الوصف</th><th>الحالة</th></tr>";
            
            $missing_count = 0;
            foreach ($required_fields as $field_name => $field_desc) {
                $exists = in_array($field_name, $existing_columns);
                if (!$exists) {
                    $missing_count++;
                }
                echo "<tr>";
                echo "<td><strong>$field_name</strong></td>";
                echo "<td>$field_desc</td>";
                if ($exists) {
                    echo "<td class='exists'>✓ موجود</td>";
                } else {
                    echo "<td class='missing'>✗ غير موجود</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            if ($missing_count > 0) {
                echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3 style='color: #856404;'>⚠ يوجد $missing_count حقول ناقصة</h3>";
                echo "<p>يرجى إضافة الحقول الناقصة.</p>";
                echo "<a href='" . SITE_URL . "/database/update_employee_job_fields.php' class='btn'>إضافة الحقول تلقائياً</a>";
                echo "</div>";
            } else {
                echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3 style='color: #155724;'>✓ جميع الحقول موجودة!</h3>";
                echo "<p>جميع الحقول الوظيفية موجودة في قاعدة البيانات.</p>";
                echo "<a href='" . SITE_URL . "/admin/employees/add.php' class='btn'>العودة إلى صفحة إضافة الموظف</a>";
                echo "</div>";
            }
            
            echo "<h2>جميع الحقول الموجودة في الجدول:</h2>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
            echo "<pre style='margin: 0;'>";
            foreach ($existing_columns as $col) {
                echo $col . "\n";
            }
            echo "</pre>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; color: #721c24;'>";
            echo "<h3>خطأ:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>

