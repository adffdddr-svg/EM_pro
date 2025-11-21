<?php
/**
 * Employee Management System
 * قاعدة المعرفة للنظام
 * 
 * يحتوي على: Policies, Rules, Employee Data Schema, Error Fix Guidelines
 */

if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

/**
 * الحصول على قاعدة المعرفة الكاملة
 */
function getKnowledgeBase() {
    return [
        'policies' => getPolicies(),
        'rules' => getRules(),
        'employee_schema' => getEmployeeSchema(),
        'error_guidelines' => getErrorGuidelines(),
        'api_endpoints' => getAPIEndpoints(),
    ];
}

/**
 * السياسات (Policies)
 */
function getPolicies() {
    return [
        'employee_management' => [
            'add_employee' => [
                'required_fields' => ['first_name', 'last_name', 'email', 'position', 'salary', 'hire_date'],
                'optional_fields' => ['phone', 'address', 'department_id', 'photo'],
                'validation' => [
                    'email' => 'يجب أن يكون بريد إلكتروني صحيح وفريد',
                    'salary' => 'يجب أن يكون رقماً موجباً',
                    'hire_date' => 'يجب أن يكون تاريخ صحيح',
                ],
            ],
            'update_employee' => [
                'allowed_fields' => ['first_name', 'last_name', 'email', 'phone', 'address', 'department_id', 'position', 'salary', 'status'],
                'restrictions' => [
                    'email' => 'يجب أن يكون فريداً إذا تم تغييره',
                    'employee_code' => 'لا يمكن تغييره',
                ],
            ],
            'delete_employee' => [
                'method' => 'archive', // أرشفة بدلاً من الحذف المباشر
                'required_confirmation' => true,
            ],
        ],
        'salary_management' => [
            'update_salary' => [
                'required_fields' => ['employee_id', 'new_salary'],
                'validation' => [
                    'new_salary' => 'يجب أن يكون رقماً موجباً',
                ],
                'audit' => true, // تسجيل التغييرات
            ],
        ],
        'leave_management' => [
            'add_leave' => [
                'required_fields' => ['employee_id', 'leave_type', 'start_date', 'end_date'],
                'leave_types' => ['annual', 'sick', 'emergency', 'unpaid'],
                'validation' => [
                    'dates' => 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية',
                    'balance' => 'يجب التحقق من رصيد الإجازات المتاح',
                ],
            ],
        ],
    ];
}

/**
 * القواعد (Rules)
 */
function getRules() {
    return [
        'security' => [
            'authentication' => 'يجب تسجيل الدخول لجميع العمليات',
            'authorization' => 'التحقق من صلاحيات المستخدم قبل تنفيذ المهام',
            'csrf_protection' => 'استخدام CSRF tokens لجميع العمليات',
            'input_validation' => 'تنظيف وتحقق من جميع المدخلات',
        ],
        'data_integrity' => [
            'unique_constraints' => [
                'employee_code' => 'يجب أن يكون فريداً',
                'email' => 'يجب أن يكون فريداً',
            ],
            'foreign_keys' => [
                'department_id' => 'يجب أن يكون موجوداً في جدول departments',
            ],
        ],
        'business_logic' => [
            'employee_code' => 'يتم توليده تلقائياً بصيغة EMP###',
            'salary' => 'يجب أن يكون رقماً موجباً',
            'hire_date' => 'لا يمكن أن يكون في المستقبل',
            'status' => 'القيم المسموحة: active, inactive',
        ],
    ];
}

/**
 * Employee Data Schema
 */
function getEmployeeSchema() {
    return [
        'employees_table' => [
            'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
            'employee_code' => 'VARCHAR(20) UNIQUE NOT NULL',
            'first_name' => 'VARCHAR(50) NOT NULL',
            'last_name' => 'VARCHAR(50) NOT NULL',
            'email' => 'VARCHAR(100) UNIQUE NOT NULL',
            'phone' => 'VARCHAR(20)',
            'address' => 'TEXT',
            'department_id' => 'INT FOREIGN KEY (departments.id)',
            'position' => 'VARCHAR(100) NOT NULL',
            'salary' => 'DECIMAL(10,2) NOT NULL',
            'hire_date' => 'DATE NOT NULL',
            'photo' => 'VARCHAR(255)',
            'status' => "ENUM('active','inactive') DEFAULT 'active'",
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'departments_table' => [
            'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
            'name' => 'VARCHAR(100) UNIQUE NOT NULL',
            'description' => 'TEXT',
        ],
        'vacations_table' => [
            'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
            'emp_id' => 'INT FOREIGN KEY (employees.id)',
            'leave_type' => "ENUM('annual','sick','emergency','unpaid')",
            'start_date' => 'DATE NOT NULL',
            'end_date' => 'DATE NOT NULL',
            'days' => 'INT',
            'remaining' => 'INT',
            'status' => "ENUM('pending','approved','rejected')",
        ],
    ];
}

/**
 * Error Fix Guidelines
 */
function getErrorGuidelines() {
    return [
        'sql_errors' => [
            '1062' => 'Duplicate entry - القيمة موجودة بالفعل (مثل: email, employee_code)',
            '1452' => 'Foreign key constraint - القيمة المرجعية غير موجودة (مثل: department_id)',
            '1146' => 'Table not found - الجدول غير موجود',
            '1054' => 'Unknown column - العمود غير موجود',
        ],
        'php_errors' => [
            'PDOException' => 'خطأ في قاعدة البيانات - تحقق من الاتصال والاستعلام',
            'TypeError' => 'خطأ في نوع البيانات - تحقق من القيم المرسلة',
            'ValidationError' => 'خطأ في التحقق - تحقق من صحة المدخلات',
        ],
        'fix_steps' => [
            '1' => 'تحقق من صحة المدخلات',
            '2' => 'تحقق من وجود الجداول والأعمدة',
            '3' => 'تحقق من القيود (unique, foreign key)',
            '4' => 'تحقق من الصلاحيات',
            '5' => 'راجع سجلات الأخطاء (error_log)',
        ],
    ];
}

/**
 * API Endpoints
 */
function getAPIEndpoints() {
    return [
        'employees' => [
            'base_url' => '/bot/api/employees.php',
            'actions' => [
                'add' => ['method' => 'POST', 'endpoint' => '/bot/api/employees.php?action=add'],
                'update' => ['method' => 'POST', 'endpoint' => '/bot/api/employees.php?action=update'],
                'delete' => ['method' => 'POST', 'endpoint' => '/bot/api/employees.php?action=delete'],
                'get' => ['method' => 'GET', 'endpoint' => '/bot/api/employees.php?action=get&id={id}'],
            ],
        ],
        'salary' => [
            'base_url' => '/bot/api/salary.php',
            'actions' => [
                'update' => ['method' => 'POST', 'endpoint' => '/bot/api/salary.php?action=update'],
                'get' => ['method' => 'GET', 'endpoint' => '/bot/api/salary.php?action=get&employee_id={id}'],
            ],
        ],
        'leaves' => [
            'base_url' => '/bot/api/leaves.php',
            'actions' => [
                'add' => ['method' => 'POST', 'endpoint' => '/bot/api/leaves.php?action=add'],
                'get' => ['method' => 'GET', 'endpoint' => '/bot/api/leaves.php?action=get&employee_id={id}'],
            ],
        ],
    ];
}

