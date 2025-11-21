<?php
/**
 * Employee Management System
 * Middleware لمعالجة الأخطاء
 * 
 * يمنع أي استجابة غير مكتملة
 * يعيد المحاولة بلطف عند فشل API
 * يسجل الأخطاء في Console بدون إظهارها للمستخدم
 */

if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

/**
 * معالجة الأخطاء بشكل آمن
 */
class ErrorHandler {
    private static $error_log = [];
    
    /**
     * معالجة الاستجابة والتأكد من اكتمالها
     */
    public static function handleResponse($response, $default_message = 'حدث خطأ غير متوقع') {
        // التحقق من أن الاستجابة موجودة
        if (empty($response)) {
            self::logError('Empty response', ['response' => $response]);
            return [
                'success' => false,
                'error' => $default_message,
                'message' => $default_message
            ];
        }
        
        // التحقق من أن الاستجابة هي array
        if (!is_array($response)) {
            self::logError('Invalid response type', ['response' => $response]);
            return [
                'success' => false,
                'error' => $default_message,
                'message' => $default_message
            ];
        }
        
        // التأكد من وجود success key
        if (!isset($response['success'])) {
            $response['success'] = false;
        }
        
        // التأكد من وجود message أو error
        if (!isset($response['message']) && !isset($response['error'])) {
            $response['message'] = $response['success'] ? 'تمت العملية بنجاح' : $default_message;
        }
        
        return $response;
    }
    
    /**
     * إعادة المحاولة عند فشل API
     */
    public static function retryAPI($callback, $max_retries = 3, $delay = 1) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < $max_retries) {
            try {
                $result = $callback();
                
                // إذا نجحت العملية
                if (isset($result['success']) && $result['success']) {
                    return $result;
                }
                
                // إذا فشلت ولكن يمكن إعادة المحاولة
                $last_error = $result;
                $attempt++;
                
                if ($attempt < $max_retries) {
                    sleep($delay); // انتظار قبل إعادة المحاولة
                    self::logError("API retry attempt {$attempt}", ['error' => $last_error]);
                }
            } catch (Throwable $e) {
                $last_error = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e)
                ];
                $attempt++;
                
                if ($attempt < $max_retries) {
                    sleep($delay);
                    self::logError("API retry attempt {$attempt} (exception)", ['error' => $last_error, 'exception' => $e->getMessage()]);
                }
            }
        }
        
        // فشلت جميع المحاولات
        self::logError('API failed after retries', ['max_retries' => $max_retries, 'last_error' => $last_error]);
        return $last_error ?: [
            'success' => false,
            'error' => 'فشلت العملية بعد عدة محاولات'
        ];
    }
    
    /**
     * تسجيل الأخطاء في Console (error_log)
     */
    public static function logError($message, $context = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
            'file' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? 'unknown',
            'line' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'] ?? 'unknown',
        ];
        
        // إضافة إلى السجل المحلي
        self::$error_log[] = $log_entry;
        
        // تسجيل في error_log
        error_log('Bot Error: ' . json_encode($log_entry, JSON_UNESCAPED_UNICODE));
        
        // إذا كان DEBUG_MODE مفعلاً، يمكن إضافة المزيد من التفاصيل
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('Debug: ' . print_r($context, true));
        }
    }
    
    /**
     * الحصول على سجل الأخطاء
     */
    public static function getErrorLog() {
        return self::$error_log;
    }
    
    /**
     * تنظيف سجل الأخطاء
     */
    public static function clearErrorLog() {
        self::$error_log = [];
    }
    
    /**
     * معالجة استثناءات قاعدة البيانات
     */
    public static function handleDatabaseError($e) {
        $error_code = $e->getCode();
        $error_message = $e->getMessage();
        
        // تسجيل الخطأ
        self::logError('Database error', [
            'code' => $error_code,
            'message' => $error_message,
            'exception' => get_class($e)
        ]);
        
        // رسالة آمنة للمستخدم
        $user_message = 'حدث خطأ في قاعدة البيانات';
        
        // رسائل محددة حسب نوع الخطأ
        if (strpos($error_message, '1062') !== false || strpos($error_message, 'Duplicate') !== false) {
            $user_message = 'القيمة موجودة بالفعل (مثل: البريد الإلكتروني أو رمز الموظف)';
        } elseif (strpos($error_message, '1452') !== false || strpos($error_message, 'Foreign key') !== false) {
            $user_message = 'القيمة المرجعية غير موجودة (مثل: القسم)';
        } elseif (strpos($error_message, '1146') !== false || strpos($error_message, 'Table') !== false) {
            $user_message = 'الجدول غير موجود في قاعدة البيانات';
        } elseif (strpos($error_message, '1054') !== false || strpos($error_message, 'Unknown column') !== false) {
            $user_message = 'العمود غير موجود في الجدول';
        }
        
        return [
            'success' => false,
            'error' => $user_message,
            'message' => $user_message
        ];
    }
    
    /**
     * معالجة أخطاء التحقق (Validation)
     */
    public static function handleValidationError($errors) {
        self::logError('Validation error', ['errors' => $errors]);
        
        $message = 'خطأ في التحقق من المدخلات: ' . implode(', ', $errors);
        
        return [
            'success' => false,
            'error' => $message,
            'message' => $message,
            'validation_errors' => $errors
        ];
    }
    
    /**
     * معالجة أخطاء API
     */
    public static function handleAPIError($response, $default_message = 'فشل الاتصال بالخادم') {
        if (empty($response)) {
            self::logError('Empty API response');
            return [
                'success' => false,
                'error' => $default_message
            ];
        }
        
        // إذا كانت الاستجابة تحتوي على error
        if (isset($response['error'])) {
            self::logError('API error', ['error' => $response['error']]);
            return [
                'success' => false,
                'error' => $response['error']
            ];
        }
        
        // إذا كانت الاستجابة غير ناجحة
        if (isset($response['success']) && !$response['success']) {
            self::logError('API failed', ['response' => $response]);
            return $response;
        }
        
        return $response;
    }
}

