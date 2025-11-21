<?php
/**
 * Employee Management System
 * معالج الأوامر والأسئلة للبوت الذكي
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';

/**
 * معالجة السؤال وفهم النية
 */
function processQuestion($question, $employee_id) {
    try {
        // التحقق من تفعيل البوت (مع معالجة الأخطاء)
        $bot_enabled = true;
        try {
            $bot_enabled = isBotEnabled();
        } catch (Exception $e) {
            // إذا فشل التحقق، نفترض أن البوت مفعّل
            error_log("Bot enabled check failed: " . $e->getMessage());
            $bot_enabled = true;
        }
        
        if (!$bot_enabled) {
            return "عذراً، البوت غير مفعّل حالياً.";
        }
        
        $question = mb_strtolower(trim($question));
        $question = preg_replace('/\s+/', ' ', $question);
    
    // قائمة الكلمات المفتاحية والنوايا
    $intents = [
        'salary' => [
            'keywords' => ['راتب', 'مرتب', 'راتبي', 'مرتبي', 'الراتب', 'المرتب', 'كم راتبي', 'ما راتبي', 'salary', 'pay'],
            'response' => function($employee_id) {
                return getSalaryInfo($employee_id);
            }
        ],
        'leave' => [
            'keywords' => ['إجازة', 'إجازات', 'عطلة', 'راحة', 'إجازتي', 'عطلتي', 'leave', 'vacation', 'holiday'],
            'response' => function($employee_id) {
                return getLeaveInfo($employee_id);
            }
        ],
        'status' => [
            'keywords' => ['حالة', 'وضع', 'حالتي', 'وضعي', 'status', 'my status', 'حالة الموظف'],
            'response' => function($employee_id) {
                return getEmployeeStatusInfo($employee_id);
            }
        ],
        'greeting' => [
            'keywords' => ['مرحبا', 'أهلا', 'سلام', 'صباح', 'مساء', 'hello', 'hi', 'hey', 'السلام'],
            'response' => function($employee_id) {
                $greeting = getTimeBasedGreeting();
                $employee = getEmployeeInfoForBot($employee_id);
                $name = $employee ? ($employee['first_name'] ?? $employee['username'] ?? 'عزيزي') : 'عزيزي';
                return "{$greeting} {$name}! 👋 كيف يمكنني مساعدتك اليوم؟";
            }
        ],
        'motivation' => [
            'keywords' => ['تحفيز', 'شجعة', 'كلمة', 'رسالة', 'motivation', 'encourage'],
            'response' => function($employee_id) {
                return getRandomMotivationalMessage();
            }
        ],
        'joke' => [
            'keywords' => ['نكتة', 'نكت', 'ضحك', 'joke', 'funny', 'laugh'],
            'response' => function($employee_id) {
                return getRandomJoke();
            }
        ],
        'help' => [
            'keywords' => ['مساعدة', 'مساعدة', 'مساعدة', 'help', 'ماذا يمكنك', 'ما الذي'],
            'response' => function($employee_id) {
                return "يمكنني مساعدتك في:\n" .
                       "💰 الاستفسار عن الراتب\n" .
                       "📅 الاستفسار عن الإجازات\n" .
                       "✅ معرفة حالتك الوظيفية\n" .
                       "💪 إرسال رسالة تحفيزية\n" .
                       "😄 إخبارك بنكتة\n" .
                       "أو فقط قل مرحباً! 👋";
            }
        ],
        'thanks' => [
            'keywords' => ['شكرا', 'شكراً', 'مشكور', 'thanks', 'thank you', 'متشكر'],
            'response' => function($employee_id) {
                return "العفو! 😊 أنا هنا دائماً لمساعدتك. هل تحتاج أي شيء آخر؟";
            }
        ],
        'goodbye' => [
            'keywords' => ['وداعا', 'مع السلامة', 'باي', 'goodbye', 'bye', 'see you'],
            'response' => function($employee_id) {
                return "مع السلامة! 👋 أتمنى لك يوماً رائعاً!";
            }
        ]
    ];
    
    // البحث عن النية المناسبة
    $best_match = null;
    $best_confidence = 0;
    
    foreach ($intents as $intent_name => $intent_data) {
        $matches = 0;
        $total_keywords = count($intent_data['keywords']);
        
        foreach ($intent_data['keywords'] as $keyword) {
            if (mb_strpos($question, $keyword) !== false) {
                $matches++;
            }
        }
        
        if ($matches > 0) {
            $confidence = $matches / $total_keywords;
            if ($confidence > $best_confidence) {
                $best_confidence = $confidence;
                $best_match = $intent_name;
            }
        }
    }
    
    // إذا وجدت نية جيدة، قم بالرد
    if ($best_match && $best_confidence >= 0.1) {
        try {
            $response = $intents[$best_match]['response']($employee_id);
            $response_text = is_callable($response) ? $response() : $response;
            
            // حفظ التفاعل (مع معالجة الأخطاء)
            try {
                if (function_exists('saveBotInteraction')) {
                    saveBotInteraction($employee_id, $question, $response_text, $best_match, $best_confidence);
                }
            } catch (Exception $e) {
                error_log("Failed to save interaction: " . $e->getMessage());
            }
            
            return $response_text;
        } catch (Exception $e) {
            error_log("Error in intent response: " . $e->getMessage());
            return "عذراً، حدث خطأ في معالجة سؤالك. يرجى المحاولة مرة أخرى.";
        }
    }
    
    // إذا لم يتم العثور على نية واضحة
    $default_responses = [
        "عذراً، لم أفهم سؤالك تماماً. يمكنك أن تسألني عن:\n- الراتب 💰\n- الإجازات 📅\n- حالتك الوظيفية ✅\nأو قل 'مساعدة' لمعرفة المزيد!",
        "لم أتمكن من فهم سؤالك. جرب أن تسأل عن الراتب، الإجازات، أو حالتك. أو قل 'مساعدة'!",
        "أعتذر، لم أفهم. يمكنني مساعدتك في الاستفسار عن الراتب، الإجازات، أو حالتك. قل 'مساعدة' للمزيد!",
    ];
    
        $default_response = $default_responses[array_rand($default_responses)];
        
        // حفظ التفاعل (مع معالجة الأخطاء)
        try {
            if (function_exists('saveBotInteraction')) {
                saveBotInteraction($employee_id, $question, $default_response, 'unknown', 0);
            }
        } catch (Exception $e) {
            error_log("Failed to save interaction: " . $e->getMessage());
        }
        
        return $default_response;
    } catch (Exception $e) {
        error_log("Bot Processor Error (processQuestion): " . $e->getMessage());
        return "عذراً، حدث خطأ في معالجة سؤالك. يرجى المحاولة مرة أخرى.";
    }
}

/**
 * معالجة الأمر المباشر
 */
function processCommand($command, $employee_id) {
    $command = mb_strtolower(trim($command));
    
    // الأوامر المباشرة
    $commands = [
        '/help' => function() {
            return "الأوامر المتاحة:\n" .
                   "/help - عرض المساعدة\n" .
                   "/salary - معرفة الراتب\n" .
                   "/leave - معلومات الإجازات\n" .
                   "/status - حالة الموظف\n" .
                   "/motivate - رسالة تحفيزية\n" .
                   "/joke - نكتة";
        },
        '/salary' => function() use ($employee_id) {
            return getSalaryInfo($employee_id);
        },
        '/leave' => function() use ($employee_id) {
            return getLeaveInfo($employee_id);
        },
        '/status' => function() use ($employee_id) {
            return getEmployeeStatusInfo($employee_id);
        },
        '/motivate' => function() {
            return getRandomMotivationalMessage();
        },
        '/joke' => function() {
            return getRandomJoke();
        }
    ];
    
    if (isset($commands[$command])) {
        return $commands[$command]();
    }
    
    return null;
}

/**
 * معالجة الرسالة (سؤال أو أمر)
 */
function processMessage($message, $employee_id) {
    try {
        $message = trim($message);
        
        if (empty($message)) {
            return "يرجى إدخال رسالة!";
        }
        
        // التحقق من الأمر المباشر
        if (mb_substr($message, 0, 1) === '/') {
            $response = processCommand($message, $employee_id);
            if ($response !== null) {
                return $response;
            }
        }
        
        // معالجة كسؤال عادي
        return processQuestion($message, $employee_id);
    } catch (Exception $e) {
        error_log("Bot Processor Error (processMessage): " . $e->getMessage());
        return "عذراً، حدث خطأ في معالجة رسالتك. يرجى المحاولة مرة أخرى.";
    }
}

