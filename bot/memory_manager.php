<?php
/**
 * Employee Management System
 * Memory Manager - إدارة الذاكرة والسياق
 * 
 * Session-based context memory
 */

if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

/**
 * إدارة الذاكرة والسياق للبوت
 */
class MemoryManager {
    private static $session_key = 'bot_memory';
    private static $max_context_length = 20; // أقصى عدد رسائل في السياق
    
    /**
     * حفظ رسالة في الذاكرة
     */
    public static function saveMessage($role, $content, $metadata = []) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $memory = self::getMemory();
        
        $message = [
            'role' => $role, // 'user' أو 'bot'
            'content' => $content,
            'timestamp' => time(),
            'metadata' => $metadata
        ];
        
        $memory['messages'][] = $message;
        
        // الحفاظ على أقصى عدد من الرسائل
        if (count($memory['messages']) > self::$max_context_length) {
            $memory['messages'] = array_slice($memory['messages'], -self::$max_context_length);
        }
        
        // حفظ المعلومات المهمة
        if (!empty($metadata['important'])) {
            $memory['important_facts'][] = [
                'content' => $content,
                'timestamp' => time(),
                'metadata' => $metadata
            ];
        }
        
        $_SESSION[self::$session_key] = $memory;
    }
    
    /**
     * الحصول على الذاكرة الكاملة
     */
    public static function getMemory() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$session_key])) {
            $_SESSION[self::$session_key] = [
                'messages' => [],
                'important_facts' => [],
                'context' => [],
                'user_preferences' => []
            ];
        }
        
        return $_SESSION[self::$session_key];
    }
    
    /**
     * الحصول على تاريخ المحادثة (للـ AI)
     */
    public static function getConversationHistory($limit = 10) {
        $memory = self::getMemory();
        $messages = $memory['messages'] ?? [];
        
        // أخذ آخر $limit رسائل
        $recent_messages = array_slice($messages, -$limit);
        
        // تحويل إلى تنسيق مناسب للـ AI
        $history = [];
        foreach ($recent_messages as $msg) {
            $history[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        
        return $history;
    }
    
    /**
     * الحصول على السياق المهم
     */
    public static function getImportantContext() {
        $memory = self::getMemory();
        return $memory['important_facts'] ?? [];
    }
    
    /**
     * حفظ معلومة مهمة
     */
    public static function saveImportantFact($fact, $metadata = []) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $memory = self::getMemory();
        
        $memory['important_facts'][] = [
            'fact' => $fact,
            'timestamp' => time(),
            'metadata' => $metadata
        ];
        
        $_SESSION[self::$session_key] = $memory;
    }
    
    /**
     * الحصول على تفضيلات المستخدم
     */
    public static function getUserPreferences() {
        $memory = self::getMemory();
        return $memory['user_preferences'] ?? [];
    }
    
    /**
     * حفظ تفضيلات المستخدم
     */
    public static function saveUserPreference($key, $value) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $memory = self::getMemory();
        $memory['user_preferences'][$key] = $value;
        
        $_SESSION[self::$session_key] = $memory;
    }
    
    /**
     * الحصول على السياق الكامل (للـ AI)
     */
    public static function getFullContext() {
        $memory = self::getMemory();
        
        return [
            'conversation_history' => self::getConversationHistory(self::$max_context_length),
            'important_facts' => $memory['important_facts'] ?? [],
            'user_preferences' => $memory['user_preferences'] ?? [],
            'context' => $memory['context'] ?? []
        ];
    }
    
    /**
     * تنظيف الذاكرة
     */
    public static function clearMemory() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$session_key]);
    }
    
    /**
     * تنظيف الذاكرة القديمة (أكثر من X ساعة)
     */
    public static function cleanOldMemory($hours = 24) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $memory = self::getMemory();
        $cutoff_time = time() - ($hours * 3600);
        
        // تنظيف الرسائل القديمة
        if (isset($memory['messages'])) {
            $memory['messages'] = array_filter($memory['messages'], function($msg) use ($cutoff_time) {
                return ($msg['timestamp'] ?? 0) > $cutoff_time;
            });
        }
        
        // تنظيف الحقائق المهمة القديمة
        if (isset($memory['important_facts'])) {
            $memory['important_facts'] = array_filter($memory['important_facts'], function($fact) use ($cutoff_time) {
                return ($fact['timestamp'] ?? 0) > $cutoff_time;
            });
        }
        
        $_SESSION[self::$session_key] = $memory;
    }
    
    /**
     * البحث في الذاكرة
     */
    public static function searchMemory($query) {
        $memory = self::getMemory();
        $results = [];
        
        // البحث في الرسائل
        foreach ($memory['messages'] ?? [] as $msg) {
            if (stripos($msg['content'], $query) !== false) {
                $results[] = $msg;
            }
        }
        
        // البحث في الحقائق المهمة
        foreach ($memory['important_facts'] ?? [] as $fact) {
            $fact_content = is_array($fact) ? ($fact['fact'] ?? $fact['content'] ?? '') : $fact;
            if (stripos($fact_content, $query) !== false) {
                $results[] = $fact;
            }
        }
        
        return $results;
    }
}

