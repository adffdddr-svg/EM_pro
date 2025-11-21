<?php
/**
 * Employee Management System
 * ملف اختبار API Endpoints
 * 
 * يختبر جميع العمليات: إضافة، تعديل، حذف، جلب معلومات
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/task_executor.php';
require_once __DIR__ . '/middleware/error_handler.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// محاكاة تسجيل الدخول (للاختبار فقط)
if (!isLoggedIn()) {
    // محاولة تسجيل الدخول كـ admin
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}

echo "<!DOCTYPE html>
<html dir='rtl' lang='ar'>
<head>
    <meta charset='UTF-8'>
    <title>اختبار API Endpoints</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🧪 اختبار API Endpoints للبوت الذكي</h1>";

$executor = new TaskExecutor();
$test_results = [];

// اختبار 1: إضافة موظف
echo "<div class='test-section'>";
echo "<h2>1. اختبار إضافة موظف</h2>";

$add_task = [
    'action' => 'add_employee',
    'data' => [
        'first_name' => 'أحمد',
        'last_name' => 'الاختبار',
        'email' => 'test_' . time() . '@example.com',
        'phone' => '07701234567',
        'address' => 'البصرة - الجمعية',
        'department_id' => 1,
        'position' => 'مطور برمجيات',
        'salary' => 1500000,
        'hire_date' => date('Y-m-d')
    ]
];

$result = $executor->executeTask($add_task);
$test_results['add_employee'] = $result;

if ($result['success']) {
    $new_employee_id = $result['data']['id'] ?? null;
    echo "<div class='test-result success'>✓ نجح: " . $result['message'] . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// اختبار 2: جلب معلومات موظف
echo "<div class='test-section'>";
echo "<h2>2. اختبار جلب معلومات موظف</h2>";

$get_task = [
    'action' => 'get_employee',
    'employee_id' => $new_employee_id ?? 1 // استخدام الموظف الجديد أو الموظف الأول
];

$result = $executor->executeTask($get_task);
$test_results['get_employee'] = $result;

if ($result['success']) {
    echo "<div class='test-result success'>✓ نجح: تم جلب معلومات الموظف</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// اختبار 3: تحديث راتب
echo "<div class='test-section'>";
echo "<h2>3. اختبار تحديث راتب</h2>";

$update_salary_task = [
    'action' => 'update_salary',
    'employee_id' => $new_employee_id ?? 1,
    'data' => [
        'new_salary' => 2000000
    ]
];

$result = $executor->executeTask($update_salary_task);
$test_results['update_salary'] = $result;

if ($result['success']) {
    echo "<div class='test-result success'>✓ نجح: " . $result['message'] . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// اختبار 4: جلب معلومات الراتب
echo "<div class='test-section'>";
echo "<h2>4. اختبار جلب معلومات الراتب</h2>";

$get_salary_task = [
    'action' => 'get_salary',
    'employee_id' => $new_employee_id ?? 1
];

$result = $executor->executeTask($get_salary_task);
$test_results['get_salary'] = $result;

if ($result['success']) {
    echo "<div class='test-result success'>✓ نجح: تم جلب معلومات الراتب</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
    echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// اختبار 5: تحديث موظف
if (isset($new_employee_id)) {
    echo "<div class='test-section'>";
    echo "<h2>5. اختبار تحديث موظف</h2>";
    
    $update_task = [
        'action' => 'update_employee',
        'employee_id' => $new_employee_id,
        'data' => [
            'position' => 'مطور برمجيات أول',
            'phone' => '07709999999'
        ]
    ];
    
    $result = $executor->executeTask($update_task);
    $test_results['update_employee'] = $result;
    
    if ($result['success']) {
        echo "<div class='test-result success'>✓ نجح: " . $result['message'] . "</div>";
        echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
        echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
    }
    echo "</div>";
}

// اختبار 6: حذف موظف (أرشفة)
if (isset($new_employee_id)) {
    echo "<div class='test-section'>";
    echo "<h2>6. اختبار حذف موظف (أرشفة)</h2>";
    
    $delete_task = [
        'action' => 'delete_employee',
        'employee_id' => $new_employee_id,
        'data' => [
            'reason' => 'اختبار من البوت'
        ]
    ];
    
    $result = $executor->executeTask($delete_task);
    $test_results['delete_employee'] = $result;
    
    if ($result['success']) {
        echo "<div class='test-result success'>✓ نجح: " . $result['message'] . "</div>";
        echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='test-result error'>✗ فشل: " . ($result['error'] ?? $result['message'] ?? 'خطأ غير معروف') . "</div>";
        echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
    }
    echo "</div>";
}

// ملخص النتائج
echo "<div class='test-section'>";
echo "<h2>📊 ملخص النتائج</h2>";

$success_count = 0;
$fail_count = 0;

foreach ($test_results as $test_name => $result) {
    if ($result['success'] ?? false) {
        $success_count++;
    } else {
        $fail_count++;
    }
}

echo "<div class='test-result info'>";
echo "<strong>إجمالي الاختبارات:</strong> " . count($test_results) . "<br>";
echo "<strong>نجحت:</strong> <span style='color: green;'>$success_count</span><br>";
echo "<strong>فشلت:</strong> <span style='color: red;'>$fail_count</span><br>";
echo "</div>";

echo "</div>";

echo "</body></html>";

