<?php
/**
 * Employee Management System
 * OpenRouter.ai API Integration
 * نظام البوت الذكي باستخدام OpenRouter.ai
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';
require_once __DIR__ . '/prompts/system_prompt.php';
require_once __DIR__ . '/knowledge_base.php';
require_once __DIR__ . '/memory_manager.php';

/**
 * إعدادات OpenRouter.ai API
 */
class OpenRouterAPI {
    private $api_key;
    private $api_url = 'https://openrouter.ai/api/v1/chat/completions';
    private $model = 'openai/gpt-4o-mini'; // يمكن تغييره لأي نموذج متاح في OpenRouter
    private $provider = 'openrouter'; // 'openrouter' أو 'openai'
    
    public function __construct() {
        // الحصول على API Key من ملف الإعدادات أو متغير البيئة
        $this->api_key = '';
        
        // 1. محاولة من متغير البيئة
        $this->api_key = getenv('OPENROUTER_API_KEY') ?: '';
        
        // 2. محاولة من config.php
        if (empty($this->api_key) && defined('OPENROUTER_API_KEY')) {
            $this->api_key = OPENROUTER_API_KEY;
        }
        
        // 3. محاولة من ملف نصي
        if (empty($this->api_key)) {
            $key_file = __DIR__ . '/../config/openrouter_key.txt';
            if (file_exists($key_file)) {
                $this->api_key = trim(file_get_contents($key_file));
            }
        }
        
        // الحصول على النموذج من الإعدادات
        if (defined('OPENROUTER_MODEL')) {
            $this->model = OPENROUTER_MODEL;
        }
    }
    
    public function getApiKey() {
        return $this->api_key;
    }
    
    public function getModel() {
        return $this->model;
    }

    /**
     * إرسال رسالة إلى OpenRouter والحصول على رد
     */
    public function chat($user_message, $employee_id, $conversation_history = []) {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'OpenRouter API Key غير موجود. يرجى إضافته في config/config.php أو ملف config/openrouter_key.txt',
                'fallback' => true // إشارة لاستخدام النظام القديم
            ];
        }
        
        try {
            // الحصول على معلومات الموظف
            $employee_info = $this->getEmployeeContext($employee_id);
            
            $messages = $this->buildMessages($user_message, $employee_info, $conversation_history);
            $response_data = $this->sendRequest($messages);

            if (!$response_data['success']) {
                return $response_data;
            }

            $ai_response_content = $response_data['response'];
            
            // حفظ رد البوت في الذاكرة
            MemoryManager::saveMessage('bot', $ai_response_content, [
                'employee_id' => $employee_id,
                'important' => false
            ]);
            
            // استخراج الـ SQL Query إذا وجد
            $suggested_query = $this->extractSQLQuery($ai_response_content);
            $needs_query = !empty($suggested_query);

            return [
                'success' => true,
                'response' => $ai_response_content,
                'suggested_query' => $suggested_query,
                'needs_query' => $needs_query
            ];
        } catch (Exception $e) {
            error_log("OpenRouter Chat Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'حدث خطأ داخلي أثناء الاتصال بـ OpenRouter: ' . $e->getMessage()
            ];
        }
    }

    /**
     * بناء رسائل المحادثة مع System Prompt
     */
    private function buildMessages($user_message, $employee_info, $conversation_history) {
        // الحصول على قاعدة المعرفة
        $knowledge_base = getKnowledgeBase();
        
        // بناء System Prompt من الملف الثابت
        $system_prompt = buildSystemPrompt($employee_info, $knowledge_base);
        
        // الحصول على السياق من الذاكرة
        $memory_context = MemoryManager::getFullContext();
        
        // إضافة السياق إلى System Prompt
        if (!empty($memory_context['important_facts'])) {
            $important_facts = array_map(function($fact) {
                return is_array($fact) ? ($fact['fact'] ?? $fact['content'] ?? '') : $fact;
            }, $memory_context['important_facts']);
            
            $system_prompt .= "\n\n# معلومات مهمة من المحادثة السابقة:\n" . implode("\n", array_slice($important_facts, -5));
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $system_prompt
            ]
        ];
        
        // إضافة تاريخ المحادثة (آخر 5 رسائل)
        if (!empty($conversation_history)) {
            $recent_history = array_slice($conversation_history, -5);
            foreach ($recent_history as $msg) {
                $messages[] = [
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content'] ?? ''
                ];
            }
        }
        
        // إضافة الرسالة الحالية
        $messages[] = [
            'role' => 'user',
            'content' => $user_message
        ];
        
        return $messages;
    }
    
    /**
     * إرسال الطلب إلى OpenRouter
     */
    private function sendRequest($messages) {
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'HTTP-Referer: ' . SITE_URL, // مطلوب من OpenRouter
            'X-Title: Employee Management System' // اختياري
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("cURL Error: " . $error);
            return [
                'success' => false,
                'error' => 'خطأ في الاتصال: ' . $error
            ];
        }
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            $error_msg = $error_data['error']['message'] ?? 'خطأ غير معروف من OpenRouter';
            error_log("OpenRouter API Error (HTTP {$http_code}): " . $error_msg);
            return [
                'success' => false,
                'error' => 'خطأ من OpenRouter: ' . $error_msg
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            error_log("Unexpected response from OpenRouter: " . json_encode($response_data));
            return [
                'success' => false,
                'error' => 'رد غير متوقع من OpenRouter'
            ];
        }
        
        return [
            'success' => true,
            'response' => $response_data['choices'][0]['message']['content']
        ];
    }

    /**
     * استخراج SQL Query من رد OpenRouter
     */
    private function extractSQLQuery($text) {
        preg_match('/```sql\s*(.*?)\s*```/s', $text, $matches);
        return $matches[1] ?? null;
    }

    /**
     * الحصول على سياق الموظف (معلومات أساسية)
     */
    private function getEmployeeContext($employee_id) {
        $employee = getEmployeeInfoForBot($employee_id); // دالة موجودة في bot_functions.php
        if ($employee) {
            return [
                'id' => $employee['id'],
                'first_name' => $employee['first_name'] ?? $employee['username'],
                'last_name' => $employee['last_name'] ?? '',
                'email' => $employee['email'] ?? '',
                'position' => $employee['position'] ?? 'غير محدد',
                'department' => $employee['department_name'] ?? 'غير محدد',
                'status' => $employee['status'] ?? 'غير محدد',
                // لا نرسل معلومات حساسة مثل الراتب مباشرة إلى AI
            ];
        }
        return ['id' => $employee_id, 'status' => 'غير موجود'];
    }

    /**
     * الحصول على تاريخ المحادثة من قاعدة البيانات
     */
    public function getConversationHistory($employee_id, $limit = 5) {
        try {
            $db = getDB();
            $check_table = $db->query("SHOW TABLES LIKE 'bot_interactions'");
            if (!$check_table->fetch()) {
                return [];
            }
            
            $stmt = $db->prepare("SELECT user_message, bot_response FROM bot_interactions WHERE employee_id = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$employee_id, $limit]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formatted_history = [];
            foreach (array_reverse($history) as $interaction) {
                $formatted_history[] = ['role' => 'user', 'content' => $interaction['user_message']];
                $formatted_history[] = ['role' => 'assistant', 'content' => $interaction['bot_response']];
            }
            return $formatted_history;
        } catch (Exception $e) {
            error_log("Error getting conversation history: " . $e->getMessage());
            return [];
        }
    }
}

