<?php
/**
 * Employee Management System
 * إضافة الحقول الوظيفية عبر AJAX
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// التحقق من تسجيل الدخول والصلاحيات
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بهذه العملية'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => '', 'fields_added' => 0];

try {
    $db = getDB();
    
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
    
    // الحصول على قائمة الحقول الموجودة
    $stmt = $db->query("SHOW COLUMNS FROM employees");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    $added = 0;
    $errors = [];
    
    // إضافة الحقول المفقودة
    foreach ($all_fields as $field_name => $field_definition) {
        if (!in_array($field_name, $existing_columns)) {
            try {
                $db->exec("ALTER TABLE employees ADD COLUMN $field_name $field_definition");
                $added++;
                // تحديث قائمة الحقول بعد الإضافة
                $existing_columns[] = $field_name;
            } catch (PDOException $e) {
                $error_msg = $e->getMessage();
                if (stripos($error_msg, 'Duplicate column') === false && 
                    stripos($error_msg, 'already exists') === false &&
                    stripos($error_msg, 'Duplicate column name') === false) {
                    $errors[] = "خطأ في إضافة الحقل '$field_name': " . $error_msg;
                } else {
                    // الحقل موجود بالفعل
                    if (!in_array($field_name, $existing_columns)) {
                        $existing_columns[] = $field_name;
                    }
                }
            }
        }
    }
    
    // نسخ الراتب الحالي من salary إذا كان current_salary فارغ
    try {
        $stmt = $db->query("UPDATE employees SET current_salary = salary WHERE current_salary IS NULL AND salary > 0");
    } catch (PDOException $e) {
        // تجاهل الخطأ
    }
    
    // التحقق النهائي
    $stmt = $db->query("SHOW COLUMNS FROM employees");
    $final_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $final_columns[] = $row['Field'];
    }
    
    $required_fields = array_keys($all_fields);
    $missing = array_diff($required_fields, $final_columns);
    
    if (empty($missing)) {
        $response['success'] = true;
        $response['message'] = "تم إضافة جميع الحقول بنجاح!";
        $response['fields_added'] = $added;
    } else {
        $response['message'] = "تم إضافة $added حقول، لكن بعض الحقول لم تُضف: " . implode(', ', $missing);
        $response['errors'] = $errors;
    }
    
} catch (Exception $e) {
    $response['message'] = "خطأ: " . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

