<?php
/**
 * Employee Management System
 * API Endpoint لإدارة الرواتب
 * 
 * Actions: update, get
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
        case 'update':
            $result = handleUpdateSalary($data);
            break;
            
        case 'get':
            $employee_id = $_GET['employee_id'] ?? $data['employee_id'] ?? 0;
            $result = handleGetSalary($employee_id);
            break;
            
        default:
            $result = ErrorHandler::handleResponse(null, 'عملية غير معروفة');
    }
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    ErrorHandler::logError('Salary API Exception', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(ErrorHandler::handleDatabaseError($e), JSON_UNESCAPED_UNICODE);
}

/**
 * تحديث الراتب
 */
function handleUpdateSalary($data) {
    $db = getDB();
    
    $employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
    $new_salary = isset($data['new_salary']) ? (float)$data['new_salary'] : 0;
    
    if ($employee_id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    if ($new_salary <= 0) {
        return ErrorHandler::handleValidationError(['new_salary' => 'الراتب يجب أن يكون أكبر من صفر']);
    }
    
    // التحقق من وجود الموظف
    $stmt = $db->prepare("SELECT id, salary, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
    }
    
    $old_salary = $employee['salary'];
    
    // تحديث الراتب
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE employees SET salary = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_salary, $employee_id]);
        
        // تسجيل التغيير (يمكن إضافة جدول salary_history لاحقاً)
        // TODO: إضافة جدول salary_history لتسجيل تاريخ التغييرات
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'تم تحديث الراتب بنجاح',
            'data' => [
                'employee_id' => $employee_id,
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'old_salary' => $old_salary,
                'new_salary' => $new_salary
            ]
        ];
        
    } catch (PDOException $e) {
        $db->rollBack();
        return ErrorHandler::handleDatabaseError($e);
    }
}

/**
 * الحصول على معلومات الراتب
 */
function handleGetSalary($employee_id) {
    $db = getDB();
    
    if ($employee_id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    try {
        $stmt = $db->prepare("SELECT id, first_name, last_name, salary, position, department_id FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
        }
        
        // الحصول على اسم القسم
        $department_name = 'غير محدد';
        if ($employee['department_id']) {
            $dept_stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
            $dept_stmt->execute([$employee['department_id']]);
            $dept = $dept_stmt->fetch();
            if ($dept) {
                $department_name = $dept['name'];
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'position' => $employee['position'],
                'department' => $department_name,
                'salary' => (float)$employee['salary'],
                'salary_formatted' => formatCurrency($employee['salary'])
            ]
        ];
        
    } catch (PDOException $e) {
        return ErrorHandler::handleDatabaseError($e);
    }
}

