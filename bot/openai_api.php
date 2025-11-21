<?php
/**
 * Employee Management System
 * OpenAI API Integration - ChatGPT Integration
 * نظام البوت الذكي باستخدام OpenAI GPT
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';

/**
 * إعدادات OpenAI API
 */
class OpenAIAPI {
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4o-mini';
    
    public function __construct() {
        // الحصول على API Key من ملف الإعدادات أو متغير البيئة
        $this->api_key = '';
        
        // 1. محاولة من متغير البيئة
        $this->api_key = getenv('OPENAI_API_KEY') ?: '';
        
        // 2. محاولة من config.php
        if (empty($this->api_key) && defined('OPENAI_API_KEY')) {
            $this->api_key = OPENAI_API_KEY;
        }
        
        // 3. محاولة من ملف نصي
        if (empty($this->api_key)) {
            $key_file = __DIR__ . '/../config/openai_key.txt';
            if (file_exists($key_file)) {
                $this->api_key = trim(file_get_contents($key_file));
            }
        }
    }
    
    /**
     * إرسال رسالة إلى OpenAI والحصول على رد
     */
    public function chat($user_message, $employee_id, $conversation_history = []) {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'error' => 'OpenAI API Key غير موجود. يرجى إضافته في config/config.php أو ملف config/openai_key.txt',
                'fallback' => true // إشارة لاستخدام النظام القديم
            ];
        }
        
        try {
            // الحصول على معلومات الموظف
            $employee_info = $this->getEmployeeContext($employee_id);
            
            // بناء النظام Prompt
            $system_prompt = $this->buildSystemPrompt($employee_info);
            
            // بناء رسائل المحادثة
            $messages = $this->buildMessages($system_prompt, $user_message, $conversation_history);
            
            // إرسال الطلب إلى OpenAI
            $response = $this->sendRequest($messages);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'response' => $response['message'],
                    'suggested_query' => $response['suggested_query'] ?? null,
                    'needs_query' => $response['needs_query'] ?? false
                ];
            } else {
                return $response;
            }
            
        } catch (Exception $e) {
            error_log("OpenAI API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'حدث خطأ في الاتصال بـ OpenAI. يرجى المحاولة مرة أخرى.'
            ];
        }
    }
    
    /**
     * بناء النظام Prompt
     */
    private function buildSystemPrompt($employee_info) {
        $prompt = "أنت مساعد HR ذكي داخل نظام إدارة الموظفين لجامعة البصرة.\n\n";
        $prompt .= "**التعليمات الأساسية:**\n";
        $prompt .= "1. تجاوب باللهجة العراقية الطبيعية والودية.\n";
        $prompt .= "2. تخاطب الموظفين بلطف واحترام.\n";
        $prompt .= "3. استخدم الأسماء عند التحدث مع الموظفين.\n";
        $prompt .= "4. كن ودوداً وطبيعياً في الردود.\n\n";
        
        $prompt .= "**معلومات الموظف الحالي:**\n";
        if ($employee_info) {
            $name = $employee_info['first_name'] ?? $employee_info['username'] ?? 'عزيزي';
            $prompt .= "- الاسم: {$name}\n";
            if (isset($employee_info['department_name'])) {
                $prompt .= "- القسم: {$employee_info['department_name']}\n";
            }
            if (isset($employee_info['position'])) {
                $prompt .= "- المسمى الوظيفي: {$employee_info['position']}\n";
            }
        }
        
        $prompt .= "\n**قواعد قاعدة البيانات:**\n";
        $prompt .= "- جدول الموظفين: `employees` (الحقول: id, first_name, last_name, email, phone, salary, department_id, position, status, hire_date)\n";
        $prompt .= "- جدول الأقسام: `departments` (الحقول: id, name)\n";
        $prompt .= "- جدول المستخدمين: `users` (الحقول: id, username, email, role)\n";
        $prompt .= "- employee_id الحالي: {$employee_info['id'] ?? 'غير محدد'}\n\n";
        
        $prompt .= "**مهم جداً - قواعد SQL:**\n";
        $prompt .= "1. إذا طلب المستخدم معلومة تحتاج استعلام من قاعدة البيانات:\n";
        $prompt .= "   - اقترح SQL Query فقط في تنسيق خاص: [SQL_QUERY]SELECT ...[/SQL_QUERY]\n";
        $prompt .= "   - لا تنفذ الـ Query بنفسك\n";
        $prompt .= "   - اشرح للمستخدم أنك ستحصل على المعلومات\n";
        $prompt .= "2. إذا كانت المعلومة متوفرة في السياق، جاوب مباشرة.\n";
        $prompt .= "3. استخدم اللهجة العراقية في جميع الردود.\n\n";
        
        $prompt .= "**أمثلة على الردود:**\n";
        $prompt .= "- \"شكد رصيدي من الإجازات؟\" → اقترح: SELECT remaining_leave FROM employees WHERE id = {$employee_info['id']}\n";
        $prompt .= "- \"كم موظف في قسم IT؟\" → اقترح: SELECT COUNT(*) FROM employees WHERE department_id = (SELECT id FROM departments WHERE name = 'IT')\n";
        $prompt .= "- \"مرحبا\" → رد طبيعي بالعراقي: \"أهلاً وسهلاً! كيف يمكنني أساعدك اليوم؟\"\n\n";
        
        $prompt .= "تذكر: كن ودوداً، طبيعياً، واستخدم اللهجة العراقية في جميع الردود.";
        
        return $prompt;
    }
    
    /**
     * بناء رسائل المحادثة
     */
    private function buildMessages($system_prompt, $user_message, $conversation_history = []) {
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
     * إرسال الطلب إلى OpenAI
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
            'Authorization: Bearer ' . $this->api_key
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
            return [
                'success' => false,
                'error' => 'خطأ في الاتصال: ' . $error
            ];
        }
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            $error_msg = $error_data['error']['message'] ?? 'خطأ غير معروف من OpenAI';
            return [
                'success' => false,
                'error' => 'خطأ من OpenAI: ' . $error_msg
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'error' => 'رد غير متوقع من OpenAI'
            ];
        }
        
        $ai_response = $response_data['choices'][0]['message']['content'];
        
        // استخراج SQL Query إذا كان موجوداً
        $suggested_query = null;
        $needs_query = false;
        
        if (preg_match('/\[SQL_QUERY\](.*?)\[\/SQL_QUERY\]/s', $ai_response, $matches)) {
            $suggested_query = trim($matches[1]);
            $needs_query = true;
            // إزالة الـ Query من الرد للمستخدم
            $ai_response = preg_replace('/\[SQL_QUERY\].*?\[\/SQL_QUERY\]/s', '', $ai_response);
            $ai_response = trim($ai_response);
        }
        
        return [
            'success' => true,
            'message' => $ai_response,
            'suggested_query' => $suggested_query,
            'needs_query' => $needs_query
        ];
    }
    
    /**
     * الحصول على معلومات الموظف للسياق
     */
    private function getEmployeeContext($employee_id) {
        return getEmployeeInfoForBot($employee_id);
    }
    
    /**
     * الحصول على تاريخ المحادثة
     */
    public function getConversationHistory($employee_id, $limit = 5) {
        try {
            $db = getDB();
            $check_table = $db->query("SHOW TABLES LIKE 'bot_interactions'");
            if (!$check_table->fetch()) {
                return [];
            }
            
            $stmt = $db->prepare("
                SELECT user_message, bot_response 
                FROM bot_interactions 
                WHERE employee_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$employee_id, $limit]);
            $interactions = $stmt->fetchAll();
            
            $history = [];
            foreach (array_reverse($interactions) as $interaction) {
                $history[] = [
                    'role' => 'user',
                    'content' => $interaction['user_message']
                ];
                $history[] = [
                    'role' => 'assistant',
                    'content' => $interaction['bot_response']
                ];
            }
            
            return $history;
        } catch (Exception $e) {
            error_log("Error getting conversation history: " . $e->getMessage());
            return [];
        }
    }
}

