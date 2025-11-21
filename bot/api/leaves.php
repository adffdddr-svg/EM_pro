<?php
/**
 * Employee Management System
 * API Endpoint لإدارة الإجازات
 * 
 * Actions: add, get
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../middleware/error_handler.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح - يرجى تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// تحديد نوع العملية
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// قراءة JSON من Body إذا كان موجود
$input = file_get_contents('php://input');
$data = [];
if (!empty($input)) {
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST; // Fallback إلى POST
    }
} else {
    $data = $_POST;
}

// معالجة الطلب
try {
    switch ($action) {
        case 'add':
            $result = handleAddLeave($data);
            break;
            
        case 'get':
            $employee_id = $_GET['employee_id'] ?? $data['employee_id'] ?? 0;
            $result = handleGetLeaves($employee_id);
            break;
            
        default:
            $result = ErrorHandler::handleResponse(null, 'عملية غير معروفة');
    }
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    ErrorHandler::logError('Leaves API Exception', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(ErrorHandler::handleDatabaseError($e), JSON_UNESCAPED_UNICODE);
}

/**
 * إضافة إجازة
 */
function handleAddLeave($data) {
    $db = getDB();
    
    $employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
    $leave_type = cleanInput($data['leave_type'] ?? '');
    $start_date = cleanInput($data['start_date'] ?? '');
    $end_date = cleanInput($data['end_date'] ?? '');
    
    if ($employee_id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    // التحقق من البيانات المطلوبة
    $required = ['leave_type', 'start_date', 'end_date'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        return ErrorHandler::handleValidationError($missing);
    }
    
    // التحقق من صحة التواريخ
    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    
    if ($start_timestamp === false || $end_timestamp === false) {
        return ErrorHandler::handleValidationError(['dates' => 'التواريخ غير صحيحة']);
    }
    
    if ($start_timestamp > $end_timestamp) {
        return ErrorHandler::handleValidationError(['dates' => 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية']);
    }
    
    // التحقق من أنواع الإجازات المسموحة
    $allowed_types = ['annual', 'sick', 'emergency', 'unpaid'];
    if (!in_array($leave_type, $allowed_types)) {
        return ErrorHandler::handleValidationError(['leave_type' => 'نوع الإجازة غير صحيح']);
    }
    
    // التحقق من وجود الموظف
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
    }
    
    // حساب عدد الأيام
    $days = (int)ceil(($end_timestamp - $start_timestamp) / 86400) + 1;
    
    // التحقق من وجود جدول vacations (إذا كان موجوداً)
    try {
        // استخدام جدول employee_leaves الجديد
        $stmt = $db->prepare("INSERT INTO employee_leaves 
                             (employee_id, leave_type, start_date, end_date, days, status) 
                             VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $days]);
        
        $leave_id = $db->lastInsertId();
        
        // إضافة الموافقات الأولية
        $approvers = [
            ['type' => 'leave_unit', 'name' => '', 'position' => 'مسؤول وحدة الإجازات'],
            ['type' => 'direct_supervisor', 'name' => '', 'position' => 'المسؤول المباشر'],
            ['type' => 'assistant_dean', 'name' => '', 'position' => 'معاون العميد الإداري']
        ];
        
        foreach ($approvers as $approver) {
            try {
                $stmt = $db->prepare("INSERT INTO leave_approvals (leave_id, approver_type, approver_name, approver_position, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$leave_id, $approver['type'], $approver['name'], $approver['position']]);
            } catch (PDOException $e) {
                // تجاهل خطأ الموافقات إذا كان الجدول غير موجود
            }
        }
        
        return [
            'success' => true,
            'message' => 'تم إضافة الإجازة بنجاح',
            'data' => [
                'leave_id' => $leave_id,
                'employee_id' => $employee_id,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'leave_type' => $leave_type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => $days,
                'status' => 'pending'
            ]
        ];
        
    } catch (PDOException $e) {
        // إذا كان الجدول غير موجود، نعيد رسالة خطأ واضحة
        if (strpos($e->getMessage(), '1146') !== false || strpos($e->getMessage(), 'Table') !== false) {
            return ErrorHandler::handleResponse(null, 'جدول الإجازات غير موجود في قاعدة البيانات. يرجى تشغيل ملف database/leaves_schema.sql أولاً.');
        }
        
        return ErrorHandler::handleDatabaseError($e);
    }
}

/**
 * الحصول على معلومات الإجازات
 */
function handleGetLeaves($employee_id) {
    $db = getDB();
    
    if ($employee_id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    // التحقق من وجود الموظف
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
    }
    
    try {
        // استخدام جدول employee_leaves الجديد
        $stmt = $db->prepare("SELECT * FROM employee_leaves WHERE employee_id = ? ORDER BY start_date DESC");
        $stmt->execute([$employee_id]);
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // حساب الإجمالي
        $total_days = 0;
        $remaining = 0;
        
        foreach ($leaves as $leave) {
            if ($leave['status'] === 'approved') {
                $total_days += (float)($leave['days'] ?? 0);
            }
        }
        
        // الحصول على الرصيد من جدول leave_balance
        $balance = getLeaveBalance($employee_id);
        if ($balance) {
            $remaining = (int)$balance['remaining_balance'];
        }
        
        return [
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'leaves' => $leaves,
                'total_days' => $total_days,
                'remaining' => $remaining,
                'balance' => $balance
            ]
        ];
        
    } catch (PDOException $e) {
        // إذا كان الجدول غير موجود
        if (strpos($e->getMessage(), '1146') !== false || strpos($e->getMessage(), 'Table') !== false) {
            return [
                'success' => true,
                'data' => [
                    'employee_id' => $employee_id,
                    'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                    'leaves' => [],
                    'total_days' => 0,
                    'remaining' => 0,
                    'note' => 'جدول الإجازات غير موجود. يرجى تشغيل ملف database/leaves_schema.sql'
                ]
            ];
        }
        
        return ErrorHandler::handleDatabaseError($e);
    }
}

