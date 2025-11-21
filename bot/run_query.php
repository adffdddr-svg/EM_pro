<?php
/**
 * Employee Management System
 * تنفيذ SQL Queries المقترحة من AI
 * هذا الملف ينفذ الـ Queries التي يقترحها AI بأمان
 */

define('ACCESS_ALLOWED', true);

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح - يرجى تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$employee_id = $user_id;

// الحصول على الـ Query من POST
$query = $_POST['query'] ?? $_GET['query'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'الـ Query فارغ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // تنظيف الـ Query من أي محاولات SQL Injection
    $query = trim($query);
    
    // التحقق من أن الـ Query آمن (فقط SELECT)
    if (!preg_match('/^\s*SELECT\s+/i', $query)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'يُسمح فقط بـ SELECT queries'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // منع الكلمات الخطيرة
    $dangerous_keywords = [
        'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 
        'TRUNCATE', 'EXEC', 'EXECUTE', '--', ';', 'UNION'
    ];
    
    $query_upper = strtoupper($query);
    foreach ($dangerous_keywords as $keyword) {
        if (strpos($query_upper, $keyword) !== false && 
            !preg_match('/\bSELECT\s+.*?\b' . preg_quote($keyword, '/') . '\b/i', $query)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'الـ Query يحتوي على أوامر غير مسموحة'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // استبدال employee_id في الـ Query
    $query = str_replace('{employee_id}', $employee_id, $query);
    $query = preg_replace('/\bemployee_id\s*=\s*\?\b/i', "employee_id = {$employee_id}", $query);
    
    // تنفيذ الـ Query
    $db = getDB();
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // الحصول على النتائج
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تنسيق النتائج
    $formatted_results = [];
    foreach ($results as $row) {
        $formatted_results[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_results,
        'count' => count($formatted_results),
        'query' => $query // للتطوير فقط
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Query Execution Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'خطأ في تنفيذ الـ Query: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

