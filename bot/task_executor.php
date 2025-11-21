<?php
/**
 * Employee Management System
 * Task Executor - تنفيذ المهام من البوت
 * 
 * ينشئ JSON منظم بدون نقص أو تضارب
 * يرسل إلى API مباشرة بشكل صحيح
 */

if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/middleware/error_handler.php';

/**
 * تنفيذ المهام من البوت
 */
class TaskExecutor {
    private $base_url;
    
    public function __construct() {
        $this->base_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/EM_pro';
    }
    
    /**
     * تنفيذ مهمة من JSON
     */
    public function executeTask($task_json) {
        // التحقق من صحة JSON
        if (empty($task_json)) {
            return ErrorHandler::handleResponse(null, 'المهمة فارغة');
        }
        
        // تحليل JSON
        $task = is_string($task_json) ? json_decode($task_json, true) : $task_json;
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ErrorHandler::logError('Invalid JSON in task', ['json_error' => json_last_error_msg(), 'json' => $task_json]);
            return ErrorHandler::handleResponse(null, 'JSON غير صحيح');
        }
        
        // التحقق من وجود action
        if (empty($task['action'])) {
            return ErrorHandler::handleResponse(null, 'المهمة لا تحتوي على action');
        }
        
        $action = $task['action'];
        $data = $task['data'] ?? [];
        $employee_id = $task['employee_id'] ?? null;
        
        // تنفيذ المهمة حسب النوع
        switch ($action) {
            case 'add_employee':
                return $this->addEmployee($data);
                
            case 'update_employee':
                return $this->updateEmployee($data, $employee_id);
                
            case 'delete_employee':
                return $this->deleteEmployee($employee_id);
                
            case 'update_salary':
                return $this->updateSalary($employee_id, $data);
                
            case 'add_leave':
                return $this->addLeave($employee_id, $data);
                
            case 'get_employee':
                return $this->getEmployee($employee_id);
                
            case 'get_salary':
                return $this->getSalary($employee_id);
                
            case 'get_leaves':
                return $this->getLeaves($employee_id);
                
            default:
                ErrorHandler::logError('Unknown task action', ['action' => $action]);
                return ErrorHandler::handleResponse(null, "المهمة غير معروفة: {$action}");
        }
    }
    
    /**
     * إضافة موظف جديد
     */
    private function addEmployee($data) {
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
        
        // إرسال إلى API
        return ErrorHandler::retryAPI(function() use ($data) {
            return $this->callAPI('/bot/api/employees.php?action=add', 'POST', $data);
        });
    }
    
    /**
     * تحديث موظف
     */
    private function updateEmployee($data, $employee_id) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        $data['id'] = $employee_id;
        
        return ErrorHandler::retryAPI(function() use ($data) {
            return $this->callAPI('/bot/api/employees.php?action=update', 'POST', $data);
        });
    }
    
    /**
     * حذف موظف
     */
    private function deleteEmployee($employee_id) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        return ErrorHandler::retryAPI(function() use ($employee_id) {
            return $this->callAPI('/bot/api/employees.php?action=delete', 'POST', ['id' => $employee_id]);
        });
    }
    
    /**
     * تحديث الراتب
     */
    private function updateSalary($employee_id, $data) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        if (empty($data['new_salary'])) {
            return ErrorHandler::handleValidationError(['new_salary']);
        }
        
        $payload = [
            'employee_id' => $employee_id,
            'new_salary' => $data['new_salary']
        ];
        
        return ErrorHandler::retryAPI(function() use ($payload) {
            return $this->callAPI('/bot/api/salary.php?action=update', 'POST', $payload);
        });
    }
    
    /**
     * إضافة إجازة
     */
    private function addLeave($employee_id, $data) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
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
        
        $payload = array_merge($data, ['employee_id' => $employee_id]);
        
        return ErrorHandler::retryAPI(function() use ($payload) {
            return $this->callAPI('/bot/api/leaves.php?action=add', 'POST', $payload);
        });
    }
    
    /**
     * الحصول على معلومات موظف
     */
    private function getEmployee($employee_id) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        return ErrorHandler::retryAPI(function() use ($employee_id) {
            return $this->callAPI("/bot/api/employees.php?action=get&id={$employee_id}", 'GET');
        });
    }
    
    /**
     * الحصول على الراتب
     */
    private function getSalary($employee_id) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        return ErrorHandler::retryAPI(function() use ($employee_id) {
            return $this->callAPI("/bot/api/salary.php?action=get&employee_id={$employee_id}", 'GET');
        });
    }
    
    /**
     * الحصول على الإجازات
     */
    private function getLeaves($employee_id) {
        if (empty($employee_id)) {
            return ErrorHandler::handleResponse(null, 'معرف الموظف مطلوب');
        }
        
        return ErrorHandler::retryAPI(function() use ($employee_id) {
            return $this->callAPI("/bot/api/leaves.php?action=get&employee_id={$employee_id}", 'GET');
        });
    }
    
    /**
     * استدعاء API
     */
    private function callAPI($endpoint, $method = 'GET', $data = []) {
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        if ($curl_error) {
            ErrorHandler::logError('CURL error', ['error' => $curl_error, 'url' => $url]);
            return [
                'success' => false,
                'error' => 'فشل الاتصال بالخادم'
            ];
        }
        
        if ($http_code !== 200) {
            ErrorHandler::logError('HTTP error', ['code' => $http_code, 'url' => $url]);
            return [
                'success' => false,
                'error' => "خطأ HTTP: {$http_code}"
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ErrorHandler::logError('Invalid JSON response', ['json_error' => json_last_error_msg(), 'response' => $response]);
            return [
                'success' => false,
                'error' => 'استجابة غير صحيحة من الخادم'
            ];
        }
        
        return ErrorHandler::handleResponse($result);
    }
    
    /**
     * بناء JSON Task من نص طبيعي (للـ AI)
     */
    public static function buildTaskFromText($text, $employee_id = null) {
        // هذا يمكن أن يكون محلل بسيط أو يمكن للـ AI أن يبني JSON مباشرة
        // هنا نعيد تنسيق بسيط يمكن للـ AI استخدامه
        
        return [
            'action' => 'parse_from_ai', // سيتم تحديده من الـ AI
            'data' => ['text' => $text],
            'employee_id' => $employee_id
        ];
    }
}

