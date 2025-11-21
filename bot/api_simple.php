<?php
/**
 * Employee Management System
 * API للبوت - نسخة مبسطة وموثوقة
 */

define('ACCESS_ALLOWED', true);

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers
header('Content-Type: application/json; charset=utf-8');

// تضمين الملفات الأساسية
try {
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';
require_once __DIR__ . '/processor.php';
require_once __DIR__ . '/conversation_manager.php';
require_once __DIR__ . '/natural_responses.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'خطأ في تحميل الملفات: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح - يرجى تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// الحصول على البيانات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

// تحديد employee_id حسب الدور
$employee_id = null;
if (isEmployee()) {
    // للموظف: الحصول على employee_id من user_id
    $employee = getEmployeeByUserId($user_id);
    $employee_id = $employee ? $employee['id'] : null;
} else if (isAdmin()) {
    // للمدير: employee_id = null (ليس موظفاً)
    $employee_id = null;
} else {
    // حالة افتراضية
    $employee_id = $user_id;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = $_POST['message'] ?? $_GET['message'] ?? '';

// معالجة الطلب
if ($action === 'process') {
    // التحقق من البيانات
    if (empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'الرسالة فارغة'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // للمدير: employee_id يمكن أن يكون null
    if (isAdmin()) {
        // لا حاجة للتحقق من employee_id للمدير
    } else if (empty($employee_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'معرف المستخدم غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // تنظيف الرسالة
    if (function_exists('cleanInput')) {
        $message = cleanInput($message);
    } else {
        $message = trim(strip_tags($message));
    }
    
    // معالجة الرسالة
    $response = '';
    
    // أولاً: محاولة استخدام Small Talk للأسئلة العامة
    try {
        if (function_exists('addSmallTalk')) {
            $small_talk = addSmallTalk($message, $employee_id);
            if ($small_talk !== null) {
                $response = $small_talk;
            }
        }
    } catch (Throwable $e) {
        error_log("addSmallTalk error: " . $e->getMessage());
    }
    
    // ثانياً: محاولة استخدام النظام الطبيعي
    if (empty($response)) {
        try {
            if (function_exists('getNaturalResponse')) {
                $response = getNaturalResponse($message, $employee_id);
            }
        } catch (Throwable $e) {
            error_log("getNaturalResponse error: " . $e->getMessage());
        }
    }
    
    // ثالثاً: إذا فشل، محاولة استخدام processMessage
    if (empty($response)) {
        try {
            if (function_exists('processMessage')) {
                $response = processMessage($message, $employee_id);
            }
        } catch (Throwable $e) {
            error_log("processMessage error: " . $e->getMessage());
        }
    }
    
    // للمدير: معالجة خاصة للإحصائيات
    if (empty($response) && isAdmin()) {
        $admin_response = processAdminQuery($message);
        if ($admin_response) {
            $response = $admin_response;
        }
    }
    
    // ثالثاً: إذا فشل، استخدم ردود افتراضية حسب نوع الرسالة
    if (empty($response)) {
        $message_lower = mb_strtolower(trim($message));
        
        // محاولة استخدام الدوال الموجودة أولاً
        if (mb_strpos($message_lower, 'راتب') !== false || mb_strpos($message_lower, 'مرتب') !== false || mb_strpos($message_lower, 'salary') !== false) {
            try {
                if (function_exists('getSalaryInfo')) {
                    $response = getSalaryInfo($employee_id);
                }
            } catch (Throwable $e) {
                error_log("getSalaryInfo error: " . $e->getMessage());
            }
            if (empty($response)) {
                $response = "أنت مدير النظام. للاستفسار عن الراتب، يرجى التواصل مع قسم الموارد البشرية. 💼";
            }
        } elseif (mb_strpos($message_lower, 'إجاز') !== false || mb_strpos($message_lower, 'عطلة') !== false || mb_strpos($message_lower, 'leave') !== false) {
            try {
                if (function_exists('getLeaveInfo')) {
                    $response = getLeaveInfo($employee_id);
                }
            } catch (Throwable $e) {
                error_log("getLeaveInfo error: " . $e->getMessage());
            }
            if (empty($response)) {
                $response = "حالياً لا توجد معلومات عن الإجازات متاحة. يمكنك التواصل مع قسم الموارد البشرية لمزيد من المعلومات. 📅";
            }
        } elseif (mb_strpos($message_lower, 'حالة') !== false || mb_strpos($message_lower, 'وضع') !== false || mb_strpos($message_lower, 'status') !== false) {
            try {
                if (function_exists('getEmployeeStatusInfo')) {
                    $response = getEmployeeStatusInfo($employee_id);
                }
            } catch (Throwable $e) {
                error_log("getEmployeeStatusInfo error: " . $e->getMessage());
            }
            if (empty($response)) {
                $response = "حالتك الوظيفية: نشط ✅\nأنت مدير النظام.";
            }
        } elseif (mb_strpos($message_lower, 'مرحبا') !== false || mb_strpos($message_lower, 'أهلا') !== false || mb_strpos($message_lower, 'سلام') !== false || mb_strpos($message_lower, 'hello') !== false || mb_strpos($message_lower, 'hi') !== false) {
            // تحية
            $hour = (int)date('H');
            if ($hour >= 5 && $hour < 12) {
                $response = "صباح الخير! 👋 كيف يمكنني مساعدتك اليوم؟";
            } elseif ($hour >= 12 && $hour < 17) {
                $response = "مرحباً! 👋 كيف يمكنني مساعدتك؟";
            } else {
                $response = "مساء الخير! 👋 كيف يمكنني مساعدتك؟";
            }
        } elseif (mb_strpos($message_lower, 'مساعدة') !== false || mb_strpos($message_lower, 'help') !== false || mb_strpos($message_lower, 'ماذا') !== false) {
            // مساعدة
            $response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n\n";
            $response .= "💰 الاستفسار عن الراتب\n";
            $response .= "📅 الاستفسار عن الإجازات\n";
            $response .= "✅ معرفة حالتك الوظيفية\n";
            $response .= "💪 إرسال رسالة تحفيزية\n";
            $response .= "😄 إخبارك بنكتة";
        } elseif (mb_strpos($message_lower, 'نكت') !== false || mb_strpos($message_lower, 'ضحك') !== false || mb_strpos($message_lower, 'joke') !== false) {
            // نكتة
            $jokes = [
                'لماذا الكمبيوتر بارد؟ لأنه Windows مفتوح! 😄',
                'ما هو البرنامج المفضل للطبيب؟ الدواء! 💊',
                'لماذا لا ينام المبرمج؟ لأنه يبحث عن البق! 🐛',
                'ما هو الحيوان المفضل للمبرمج؟ الكلب (Dog) لأنه صديق الإنسان! 🐕'
            ];
            $response = $jokes[array_rand($jokes)];
        } elseif (mb_strpos($message_lower, 'تحفيز') !== false || mb_strpos($message_lower, 'شجعة') !== false || mb_strpos($message_lower, 'motivation') !== false) {
            // رسالة تحفيزية
            $motivations = [
                'صباح الخير! يوم جديد يعني فرص جديدة للنجاح 🌟',
                'أنت تقوم بعمل رائع! استمر في التقدم 💪',
                'تذكر: كل خطوة صغيرة تقربك من هدفك الكبير 🎯'
            ];
            $response = $motivations[array_rand($motivations)];
        } else {
            // رد افتراضي
            $response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n\n";
            $response .= "💰 الاستفسار عن الراتب - اكتب \"ما هو راتبي؟\"\n";
            $response .= "📅 الاستفسار عن الإجازات - اكتب \"كم إجازة متبقية؟\"\n";
            $response .= "✅ معرفة حالتك الوظيفية - اكتب \"ما هي حالتي؟\"\n";
            $response .= "💪 رسالة تحفيزية - اكتب \"شجعة\"\n";
            $response .= "😄 نكتة - اكتب \"نكتة\"";
        }
    }
    
    // التأكد من وجود رد - استخدام رد طبيعي
    if (empty($response)) {
        try {
            if (function_exists('getNaturalResponse')) {
                $response = getNaturalResponse($message, $employee_id);
            }
        } catch (Throwable $e) {
            error_log("Final getNaturalResponse error: " . $e->getMessage());
        }
        
        // إذا فشل كل شيء، رد افتراضي طبيعي
        if (empty($response)) {
            $hour = (int)date('H');
            if ($hour >= 5 && $hour < 12) {
                $response = "صباح الخير! 👋 كيف يمكنني أساعدك اليوم؟";
            } elseif ($hour >= 12 && $hour < 17) {
                $response = "مرحباً! 👋 شلون أقدر أساعدك؟";
            } else {
                $response = "مساء الخير! 👋 كيف يمكنني أساعدك؟";
            }
        }
    }
    
    // محاولة الحفظ (اختياري)
    try {
        if (function_exists('saveBotInteraction')) {
            saveBotInteraction($employee_id, $message, $response, 'rule_based', 1.0);
        }
    } catch (Throwable $e) {
        // تجاهل
    }
    
    // إرجاع الرد
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'response' => $response,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'إجراء غير صحيح. استخدم action=process'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * معالجة أسئلة المدير عن الإحصائيات
 */
function processAdminQuery($message) {
    if (!isAdmin()) {
        return null;
    }
    
    $db = getDB();
    $message_lower = mb_strtolower(trim($message));
    
    // أكثر موظف أخذ إجازة هذا الشهر
    if (mb_strpos($message_lower, 'أكثر موظف') !== false && mb_strpos($message_lower, 'إجاز') !== false) {
        try {
            $stmt = $db->query("SELECT e.first_name, e.last_name, COUNT(l.id) as leave_count 
                                FROM employees e 
                                JOIN employee_leaves l ON e.id = l.employee_id 
                                WHERE MONTH(l.start_date) = MONTH(CURRENT_DATE()) 
                                AND YEAR(l.start_date) = YEAR(CURRENT_DATE())
                                GROUP BY e.id, e.first_name, e.last_name 
                                ORDER BY leave_count DESC 
                                LIMIT 1");
            $result = $stmt->fetch();
            if ($result) {
                return "أكثر موظف أخذ إجازة هذا الشهر هو: {$result['first_name']} {$result['last_name']} ({$result['leave_count']} إجازة) 📅";
            } else {
                return "لا يوجد موظفين أخذوا إجازة هذا الشهر حتى الآن. 📅";
            }
        } catch (Exception $e) {
            error_log("Admin query error: " . $e->getMessage());
        }
    }
    
    // عدد الموظفين النشطين
    if (mb_strpos($message_lower, 'عدد الموظفين') !== false || mb_strpos($message_lower, 'كم موظف') !== false) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
            $result = $stmt->fetch();
            return "عدد الموظفين النشطين: {$result['total']} موظف 👥";
        } catch (Exception $e) {
            error_log("Admin query error: " . $e->getMessage());
        }
    }
    
    // الموظفين الجدد هذا الشهر
    if (mb_strpos($message_lower, 'موظفين جدد') !== false || mb_strpos($message_lower, 'جديد') !== false) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $result = $stmt->fetch();
            return "عدد الموظفين الجدد هذا الشهر: {$result['total']} موظف 🆕";
        } catch (Exception $e) {
            error_log("Admin query error: " . $e->getMessage());
        }
    }
    
    // إحصائيات عامة
    if (mb_strpos($message_lower, 'إحصائيات') !== false || mb_strpos($message_lower, 'إحصائية') !== false) {
        try {
            $stats = [];
            $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
            $stats['active'] = $stmt->fetch()['total'];
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $stats['new'] = $stmt->fetch()['total'];
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM departments");
            $stats['departments'] = $stmt->fetch()['total'];
            
            return "إحصائيات النظام:\n" .
                   "👥 الموظفين النشطين: {$stats['active']}\n" .
                   "🆕 موظفين جدد هذا الشهر: {$stats['new']}\n" .
                   "🏢 عدد الأقسام: {$stats['departments']}";
        } catch (Exception $e) {
            error_log("Admin query error: " . $e->getMessage());
        }
    }
    
    return null;
}

