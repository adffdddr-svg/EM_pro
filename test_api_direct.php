<?php
/**
 * اختبار مباشر لـ API
 */

define('ACCESS_ALLOWED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    die('يجب تسجيل الدخول');
}

$user_id = $_SESSION['user_id'];
$test_message = $_POST['message'] ?? $_GET['message'] ?? 'مرحبا';

// محاكاة الطلب
$_POST['action'] = 'process';
$_POST['message'] = $test_message;
$_SESSION['user_id'] = $user_id;

// تضمين API
ob_start();
try {
    include __DIR__ . '/bot/api.php';
    $output = ob_get_clean();
    
    echo '<pre>';
    echo 'Response: ' . htmlspecialchars($output);
    echo '</pre>';
    
    $json = json_decode($output, true);
    if ($json) {
        echo '<pre>';
        print_r($json);
        echo '</pre>';
    }
} catch (Exception $e) {
    ob_end_clean();
    echo '<pre style="color: red;">';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo "\n\nStack trace:\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo '</pre>';
}
