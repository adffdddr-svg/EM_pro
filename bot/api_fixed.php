<?php
/**
 * Employee Management System
 * API للبوت الذكي - نسخة محسنة
 */

define('ACCESS_ALLOWED', true);

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';
require_once __DIR__ . '/processor.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$employee_id = $user_id;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'process') {
    $message = $_POST['message'] ?? $_GET['message'] ?? '';
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'الرسالة فارغة'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($employee_id)) {
        echo json_encode(['success' => false, 'error' => 'معرف المستخدم غير موجود'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // تنظيف الرسالة
    if (function_exists('cleanInput')) {
        $message = cleanInput($message);
    } else {
        $message = trim(strip_tags($message));
    }
    
    // محاولة استخدام processMessage
    $response = '';
    try {
        if (function_exists('processMessage')) {
            $response = processMessage($message, $employee_id);
        }
    } catch (Throwable $e) {
        error_log("processMessage error: " . $e->getMessage());
    }
    
    // إذا فشل، استخدم رد افتراضي
    if (empty($response)) {
        $response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n";
        $response .= "💰 الاستفسار عن الراتب\n";
        $response .= "📅 الاستفسار عن الإجازات\n";
        $response .= "✅ معرفة حالتك الوظيفية";
    }
    
    // محاولة الحفظ (اختياري)
    try {
        if (function_exists('saveBotInteraction')) {
            saveBotInteraction($employee_id, $message, $response, 'rule_based', 1.0);
        }
    } catch (Throwable $e) {
        // تجاهل
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    echo json_encode(['success' => false, 'error' => 'إجراء غير صحيح'], JSON_UNESCAPED_UNICODE);
}

