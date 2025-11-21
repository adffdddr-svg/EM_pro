<?php
/**
 * Employee Management System
 * API للحصول على رصيد الإجازات
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if ($employee_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'معرف الموظف مطلوب'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$balance = getLeaveBalance($employee_id);

if ($balance) {
    echo json_encode([
        'success' => true,
        'balance' => $balance
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'لم يتم العثور على رصيد الإجازات'
    ], JSON_UNESCAPED_UNICODE);
}

