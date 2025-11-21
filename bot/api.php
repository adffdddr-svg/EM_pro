<?php
/**
 * Employee Management System
 * API للبوت الذكي
 */

define('ACCESS_ALLOWED', true);

// بدء الجلسة إذا لم تكن بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تفعيل عرض الأخطاء للتطوير (يمكن تعطيله في الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';

// تحديد مزود AI (OpenRouter أو OpenAI)
$ai_provider = defined('AI_PROVIDER') ? AI_PROVIDER : 'openrouter'; // افتراضي: openrouter

if ($ai_provider === 'openrouter') {
    require_once __DIR__ . '/openrouter_api.php';
} else {
    require_once __DIR__ . '/openai_api.php';
}

// دعم النظام القديم كـ fallback - يجب تحميله دائماً
require_once __DIR__ . '/conversation_manager.php';
require_once __DIR__ . '/processor.php';
require_once __DIR__ . '/natural_responses.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'غير مصرح - يرجى تسجيل الدخول']);
    exit;
}

// الحصول على معرف المستخدم (user_id)
// ملاحظة: في هذا النظام، المستخدمون هم admins وليسوا employees
// لذلك نستخدم user_id مباشرة
$user_id = $_SESSION['user_id'] ?? null;
$employee_id = $user_id; // للتوافق مع الدوال الموجودة

// الحصول على نوع الطلب
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// معالجة الطلبات
try {
    switch ($action) {
        case 'send':
            handleSendMessage($employee_id);
            break;
        
        case 'get-messages':
            handleGetMessages($employee_id);
            break;
        
        case 'process':
            handleProcessMessage($employee_id);
            break;
        
        case 'mark-read':
            handleMarkAsRead($employee_id);
            break;
        
        case 'mark-all-read':
            handleMarkAllAsRead($employee_id);
            break;
        
        case 'unread-count':
            handleUnreadCount($employee_id);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'إجراء غير صحيح. الإجراءات المتاحة: send, get-messages, process, mark-read, mark-all-read, unread-count']);
            break;
    }
} catch (Throwable $e) {
    error_log("Bot API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
        'debug_message' => $e->getMessage(),
        'debug_file' => $e->getFile(),
        'debug_line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * معالجة إرسال رسالة
 */
function handleSendMessage($employee_id) {
    try {
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'الرسالة فارغة']);
            return;
        }
        
        // تنظيف الرسالة
        $message = cleanInput($message);
        
        // معالجة الرسالة والحصول على الرد
        $response = processMessage($message, $employee_id);
        
        // إرسال رسالة البوت
        sendBotMessage($employee_id, $response, 'question');
        
        echo json_encode([
            'success' => true,
            'response' => $response,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Bot API Error (handleSendMessage): " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'حدث خطأ في معالجة الرسالة'
        ]);
    }
}

/**
 * الحصول على الرسائل
 */
function handleGetMessages($employee_id) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
    
    $messages = getBotMessages($employee_id, $limit, $unread_only);
    
    // تنسيق الرسائل
    $formatted_messages = [];
    foreach ($messages as $msg) {
        $formatted_messages[] = [
            'id' => $msg['id'],
            'type' => $msg['message_type'],
            'text' => $msg['message_text'],
            'is_read' => (bool)$msg['is_read'],
            'created_at' => $msg['created_at'],
            'is_bot' => true // جميع الرسائل من البوت
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formatted_messages
    ]);
}

/**
 * معالجة رسالة والحصول على رد من OpenAI
 */
function handleProcessMessage($employee_id) {
    try {
        $message = $_POST['message'] ?? $_GET['message'] ?? '';
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'الرسالة فارغة'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // التحقق من employee_id
        if (empty($employee_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'معرف المستخدم غير موجود'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // تنظيف الرسالة
        $message = cleanInput($message);
        
        // التحقق من وجود API Key أولاً (OpenRouter أو OpenAI)
        $has_api_key = false;
        $suggested_query = null;
        $needs_query = false;
        $ai_client = null;
        
        // تحديد مزود AI
        $ai_provider = defined('AI_PROVIDER') ? AI_PROVIDER : 'openrouter';
        
        try {
            if ($ai_provider === 'openrouter' && class_exists('OpenRouterAPI')) {
                $ai_client = new OpenRouterAPI();
                $has_api_key = !empty($ai_client->getApiKey());
            } elseif ($ai_provider === 'openai' && class_exists('OpenAIAPI')) {
                $ai_client = new OpenAIAPI();
                $has_api_key = !empty($ai_client->getApiKey());
            }
        } catch (Throwable $e) {
            error_log("AI init error ({$ai_provider}): " . $e->getMessage());
            $has_api_key = false;
            $ai_client = null;
        }
        
        if (!$has_api_key || $ai_client === null) {
            // لا يوجد API Key - استخدام النظام القديم مع Small Talk
            try {
                // أولاً: محاولة Small Talk للأسئلة العامة
                if (function_exists('addSmallTalk')) {
                    $small_talk = addSmallTalk($message, $employee_id);
                    if ($small_talk !== null) {
                        $response = $small_talk;
                    }
                }
                
                // ثانياً: استخدام getNaturalResponse
                if (empty($response) && function_exists('getNaturalResponse')) {
                    $response = getNaturalResponse($message, $employee_id);
                }
                
                // ثالثاً: استخدام processMessage
                if (empty($response)) {
                    if (!function_exists('processMessage')) {
                        throw new Exception('processMessage function not found');
                    }
                    $response = processMessage($message, $employee_id);
                }
                
                // إذا كان الرد فارغاً، استخدم رد افتراضي
                if (empty($response)) {
                    $response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n";
                    $response .= "💰 الاستفسار عن الراتب\n";
                    $response .= "📅 الاستفسار عن الإجازات\n";
                    $response .= "✅ معرفة حالتك الوظيفية\n\n";
                    $response .= "ملاحظة: لإضافة ميزات الذكاء الاصطناعي، يرجى إضافة OpenAI API Key.";
                }
            } catch (Throwable $e) {
                error_log("Fallback error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // رد افتراضي ودود
                $response = "مرحباً! 👋 أنا مساعد HR. كيف يمكنني مساعدتك اليوم؟\n\n";
                $response .= "يمكنك أن تسألني عن:\n";
                $response .= "• الراتب 💰\n";
                $response .= "• الإجازات 📅\n";
                $response .= "• حالتك الوظيفية ✅";
            }
            $suggested_query = null;
            $needs_query = false;
        } else {
            // استخدام AI (OpenRouter أو OpenAI)
            try {
                if ($ai_client === null) {
                    throw new Exception('AI client not initialized');
                }
                
                // الحصول على تاريخ المحادثة
                $conversation_history = $ai_client->getConversationHistory($employee_id, 5);
                
                // إرسال الرسالة إلى AI
                $ai_response = $ai_client->chat($message, $employee_id, $conversation_history);
                
                if (!$ai_response['success']) {
                    // في حالة فشل AI، استخدام النظام القديم كـ fallback
                    try {
                        $response = processMessage($message, $employee_id);
                        
                        // إذا كان الرد فارغاً
                        if (empty($response)) {
                            $provider_name = $ai_provider === 'openrouter' ? 'OpenRouter' : 'OpenAI';
                            $response = "عذراً، حدث خطأ في الاتصال بـ {$provider_name}. لكن يمكنني مساعدتك:\n\n";
                            $response .= "• الاستفسار عن الراتب 💰\n";
                            $response .= "• الاستفسار عن الإجازات 📅\n";
                            $response .= "• معرفة حالتك الوظيفية ✅";
                        }
                    } catch (Exception $e) {
                        error_log("Fallback error: " . $e->getMessage());
                        $response = "مرحباً! 👋 كيف يمكنني مساعدتك اليوم؟";
                    }
                    $suggested_query = null;
                    $needs_query = false;
                } else {
                    $response = $ai_response['response'];
                    $suggested_query = $ai_response['suggested_query'] ?? null;
                    $needs_query = $ai_response['needs_query'] ?? false;
                    
                    // إذا كان هناك Query مقترح، تنفيذه
                    if ($needs_query && !empty($suggested_query)) {
                        $query_result = executeSuggestedQuery($suggested_query, $employee_id);
                        
                        if ($query_result['success']) {
                            // إرسال النتائج إلى AI لصياغة رد نهائي
                            $context_message = "النتائج من قاعدة البيانات:\n" . json_encode($query_result['data'], JSON_UNESCAPED_UNICODE);
                            $context_message .= "\n\nصغ رداً باللهجة العراقية بناءً على هذه النتائج.";
                            
                            $final_response = $openai->chat($context_message, $employee_id, array_merge($conversation_history, [
                                ['role' => 'user', 'content' => $message],
                                ['role' => 'assistant', 'content' => $response]
                            ]));
                            
                            if ($final_response['success']) {
                                $response = $final_response['response'];
                            } else {
                                // إذا فشل، استخدم النتائج مباشرة
                                $response = formatQueryResults($query_result['data'], $message);
                            }
                        } else {
                            // إذا فشل تنفيذ الـ Query، أضف رسالة خطأ للرد
                            $response .= "\n\n(ملاحظة: لم أتمكن من الحصول على البيانات من قاعدة البيانات)";
                        }
                    }
                }
            } catch (Throwable $e) {
                $provider_name = $ai_provider === 'openrouter' ? 'OpenRouter' : 'OpenAI';
                error_log("{$provider_name} Error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // استخدام النظام القديم كـ fallback
                try {
                    if (function_exists('processMessage')) {
                        $response = processMessage($message, $employee_id);
                    } else {
                        throw new Exception('processMessage not available');
                    }
                    
                    if (empty($response)) {
                        $response = "مرحباً! 👋 كيف يمكنني مساعدتك اليوم؟";
                    }
                } catch (Throwable $e2) {
                    error_log("Fallback error: " . $e2->getMessage());
                    $response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n";
                    $response .= "💰 الاستفسار عن الراتب\n";
                    $response .= "📅 الاستفسار عن الإجازات\n";
                    $response .= "✅ معرفة حالتك الوظيفية";
                }
                $suggested_query = null;
                $needs_query = false;
            }
        }
        
        // التأكد من وجود رد
        if (empty($response)) {
            $response = "مرحباً! 👋 أنا مساعد HR. كيف يمكنني مساعدتك اليوم؟";
        }
        
        // حفظ التفاعل (مع معالجة الأخطاء - اختياري)
        try {
            if (function_exists('saveBotInteraction')) {
                $intent = $has_api_key ? 'ai_chat' : 'rule_based';
                saveBotInteraction($employee_id, $message, $response, $intent, 1.0);
            }
        } catch (Throwable $e) {
            // تجاهل خطأ الحفظ - لا نريد أن يفشل الرد بسبب الحفظ
            error_log("Failed to save bot interaction: " . $e->getMessage());
        }
        
        try {
            if (function_exists('sendBotMessage')) {
                sendBotMessage($employee_id, $response, 'question');
            }
        } catch (Throwable $e) {
            // تجاهل خطأ الحفظ
            error_log("Failed to save bot message: " . $e->getMessage());
        }
        
        // إرجاع الرد بنجاح
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response' => $response,
            'suggested_query' => $suggested_query ?? null,
            'needs_query' => $needs_query ?? false,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Throwable $e) {
        error_log("Bot API Error (handleProcessMessage): " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // محاولة أخيرة - رد افتراضي
        $fallback_response = "مرحباً! 👋 أنا مساعد HR. يمكنني مساعدتك في:\n";
        $fallback_response .= "💰 الاستفسار عن الراتب\n";
        $fallback_response .= "📅 الاستفسار عن الإجازات\n";
        $fallback_response .= "✅ معرفة حالتك الوظيفية";
        
        http_response_code(200); // 200 حتى يعمل البوت
        echo json_encode([
            'success' => true,
            'response' => $fallback_response,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * تنفيذ Query مقترح من AI
 */
function executeSuggestedQuery($query, $employee_id) {
    try {
        // تنظيف الـ Query
        $query = trim($query);
        
        // التحقق من الأمان (فقط SELECT)
        if (!preg_match('/^\s*SELECT\s+/i', $query)) {
            return [
                'success' => false,
                'error' => 'يُسمح فقط بـ SELECT queries'
            ];
        }
        
        // استبدال employee_id
        $query = str_replace('{employee_id}', $employee_id, $query);
        $query = preg_replace('/\bemployee_id\s*=\s*\?\b/i', "employee_id = {$employee_id}", $query);
        
        // تنفيذ الـ Query
        $db = getDB();
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ];
        
    } catch (Exception $e) {
        error_log("Query Execution Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * تنسيق نتائج Query كرد نصي
 */
function formatQueryResults($data, $original_question) {
    if (empty($data)) {
        return "ما لقيت معلومات بهذا الخصوص. ممكن تحاول سؤال آخر؟";
    }
    
    $response = "هاي المعلومات:\n\n";
    
    foreach ($data as $row) {
        foreach ($row as $key => $value) {
            $response .= "{$key}: {$value}\n";
        }
        $response .= "\n";
    }
    
    return $response;
}

/**
 * تحديد رسالة كمقروءة
 */
function handleMarkAsRead($employee_id) {
    $message_id = $_POST['message_id'] ?? $_GET['message_id'] ?? 0;
    
    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'معرف الرسالة غير صحيح']);
        return;
    }
    
    $result = markBotMessageAsRead($message_id, $employee_id);
    
    echo json_encode([
        'success' => $result
    ]);
}

/**
 * تحديد جميع الرسائل كمقروءة
 */
function handleMarkAllAsRead($employee_id) {
    $result = markAllBotMessagesAsRead($employee_id);
    
    echo json_encode([
        'success' => $result
    ]);
}

/**
 * الحصول على عدد الرسائل غير المقروءة
 */
function handleUnreadCount($employee_id) {
    $count = getUnreadBotMessagesCount($employee_id);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
}

