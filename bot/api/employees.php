<?php
/**
 * Employee Management System
 * API Endpoint لإدارة الموظفين
 * 
 * Actions: add, update, delete, get
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
            $result = handleAddEmployee($data);
            break;
            
        case 'update':
            $result = handleUpdateEmployee($data);
            break;
            
        case 'delete':
            $result = handleDeleteEmployee($data);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? $data['id'] ?? 0;
            $result = handleGetEmployee($id);
            break;
            
        default:
            $result = ErrorHandler::handleResponse(null, 'عملية غير معروفة');
    }
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    ErrorHandler::logError('API Exception', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(ErrorHandler::handleDatabaseError($e), JSON_UNESCAPED_UNICODE);
}

/**
 * إضافة موظف جديد
 */
function handleAddEmployee($data) {
    $db = getDB();
    
    // التحقق من البيانات المطلوبة
    $required = ['first_name', 'last_name', 'email', 'position', 'salary', 'hire_date'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        return ErrorHandler::handleValidationError($missing);
    }
    
    // تنظيف البيانات
    $first_name = cleanInput($data['first_name']);
    $last_name = cleanInput($data['last_name']);
    $email = cleanInput($data['email']);
    $phone = cleanInput($data['phone'] ?? '');
    $address = cleanInput($data['address'] ?? '');
    $department_id = isset($data['department_id']) ? (int)$data['department_id'] : 0;
    $position = cleanInput($data['position']);
    $salary = isset($data['salary']) ? (float)$data['salary'] : 0;
    $hire_date = cleanInput($data['hire_date']);
    
    // التحقق من صحة البيانات
    if (!validateEmail($email)) {
        return ErrorHandler::handleValidationError(['email' => 'البريد الإلكتروني غير صحيح']);
    }
    
    if (emailExists($email)) {
        return ErrorHandler::handleResponse(null, 'البريد الإلكتروني مستخدم بالفعل');
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        return ErrorHandler::handleValidationError(['phone' => 'رقم الهاتف غير صحيح']);
    }
    
    if ($salary <= 0) {
        return ErrorHandler::handleValidationError(['salary' => 'الراتب يجب أن يكون أكبر من صفر']);
    }
    
    // توليد رمز موظف
    $employee_code = generateEmployeeCode();
    
    // إدراج الموظف
    try {
        $stmt = $db->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $employee_code,
            $first_name,
            $last_name,
            $email,
            $phone ?: null,
            $address ?: null,
            $department_id > 0 ? $department_id : null,
            $position,
            $salary,
            $hire_date
        ]);
        
        $employee_id = $db->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'تم إضافة الموظف بنجاح',
            'data' => [
                'id' => $employee_id,
                'employee_code' => $employee_code
            ]
        ];
        
    } catch (PDOException $e) {
        return ErrorHandler::handleDatabaseError($e);
    }
}

/**
 * تحديث موظف
 */
function handleUpdateEmployee($data) {
    $db = getDB();
    
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    
    if ($id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    // التحقق من وجود الموظف
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
    }
    
    // تنظيف البيانات
    $first_name = cleanInput($data['first_name'] ?? $employee['first_name']);
    $last_name = cleanInput($data['last_name'] ?? $employee['last_name']);
    $email = cleanInput($data['email'] ?? $employee['email']);
    $phone = cleanInput($data['phone'] ?? $employee['phone'] ?? '');
    $address = cleanInput($data['address'] ?? $employee['address'] ?? '');
    $department_id = isset($data['department_id']) ? (int)$data['department_id'] : ($employee['department_id'] ?? 0);
    $position = cleanInput($data['position'] ?? $employee['position']);
    $salary = isset($data['salary']) ? (float)$data['salary'] : $employee['salary'];
    $hire_date = cleanInput($data['hire_date'] ?? $employee['hire_date']);
    $status = cleanInput($data['status'] ?? $employee['status'] ?? 'active');
    
    // التحقق من صحة البيانات
    if (empty($first_name) || empty($last_name)) {
        return ErrorHandler::handleValidationError(['first_name', 'last_name']);
    }
    
    if (!validateEmail($email)) {
        return ErrorHandler::handleValidationError(['email' => 'البريد الإلكتروني غير صحيح']);
    }
    
    if (emailExists($email, $id)) {
        return ErrorHandler::handleResponse(null, 'البريد الإلكتروني مستخدم بالفعل');
    }
    
    if ($salary <= 0) {
        return ErrorHandler::handleValidationError(['salary' => 'الراتب يجب أن يكون أكبر من صفر']);
    }
    
    // تحديث الموظف
    try {
        $stmt = $db->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, department_id = ?, position = ?, salary = ?, hire_date = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone ?: null,
            $address ?: null,
            $department_id > 0 ? $department_id : null,
            $position,
            $salary,
            $hire_date,
            $status,
            $id
        ]);
        
        return [
            'success' => true,
            'message' => 'تم تحديث الموظف بنجاح',
            'data' => ['id' => $id]
        ];
        
    } catch (PDOException $e) {
        return ErrorHandler::handleDatabaseError($e);
    }
}

/**
 * حذف موظف (أرشفة)
 */
function handleDeleteEmployee($data) {
    $db = getDB();
    
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    
    if ($id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    // الحصول على بيانات الموظف
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
    }
    
    // أرشفة الموظف
    try {
        $db->beginTransaction();
        
        // نسخ إلى الأرشيف
        $stmt = $db->prepare("INSERT INTO employees_archive (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, leave_date, photo, archived_by, reason) 
                              SELECT employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, CURDATE(), photo, ?, ? 
                              FROM employees WHERE id = ?");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $data['reason'] ?? 'حذف من البوت',
            $id
        ]);
        
        // حذف من الجدول الرئيسي
        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'تم حذف الموظف بنجاح',
            'data' => ['id' => $id]
        ];
        
    } catch (PDOException $e) {
        $db->rollBack();
        return ErrorHandler::handleDatabaseError($e);
    }
}

/**
 * الحصول على معلومات موظف
 */
function handleGetEmployee($id) {
    $db = getDB();
    
    if ($id <= 0) {
        return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
    }
    
    try {
        $stmt = $db->prepare("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
        $stmt->execute([$id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            return ErrorHandler::handleResponse(null, 'الموظف غير موجود');
        }
        
        return [
            'success' => true,
            'data' => $employee
        ];
        
    } catch (PDOException $e) {
        return ErrorHandler::handleDatabaseError($e);
    }
}

