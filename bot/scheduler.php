<?php
/**
 * Employee Management System
 * جدولة المهام التلقائية للبوت الذكي
 * يمكن تشغيله عبر Cron Job أو Task Scheduler
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';

// التحقق من تفعيل البوت
if (!isBotEnabled()) {
    die("البوت غير مفعّل\n");
}

$db = getDB();
$processed = 0;

echo "بدء معالجة المهام التلقائية...\n";

// 1. إرسال رسائل تحفيزية يومية للموظفين النشطين
if (getBotSetting('motivational_messages_enabled', '1') == '1') {
    $stmt = $db->query("SELECT id FROM employees WHERE status = 'active'");
    $employees = $stmt->fetchAll();
    
    foreach ($employees as $employee) {
        // التحقق من عدم إرسال رسالة تحفيزية اليوم
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM bot_messages 
                                   WHERE employee_id = ? 
                                   AND message_type = 'motivational' 
                                   AND DATE(created_at) = CURDATE()");
        $check_stmt->execute([$employee['id']]);
        $count = $check_stmt->fetch()['count'];
        
        if ($count == 0) {
            // إرسال رسالة تحفيزية عشوائية
            $message = getRandomMotivationalMessage();
            if (sendBotMessage($employee['id'], $message, 'motivational')) {
                $processed++;
                echo "✓ تم إرسال رسالة تحفيزية للموظف ID: {$employee['id']}\n";
            }
        }
    }
}

// 2. التحقق من أعياد الميلاد
if (getBotSetting('birthday_notifications_enabled', '1') == '1') {
    // ملاحظة: يتطلب إضافة حقل birth_date في جدول employees
    // للآن سنستخدم hire_date كبديل للذكرى السنوية
    echo "ميزة أعياد الميلاد تتطلب إضافة حقل birth_date في جدول employees\n";
}

// 3. التحقق من الذكرى السنوية لتوظيف الموظفين
if (getBotSetting('anniversary_notifications_enabled', '1') == '1') {
    $stmt = $db->query("SELECT id, hire_date FROM employees WHERE status = 'active'");
    $employees = $stmt->fetchAll();
    
    foreach ($employees as $employee) {
        $years = checkEmployeeAnniversary($employee['id']);
        
        if ($years > 0) {
            // التحقق من عدم إرسال رسالة ذكرى سنوية اليوم
            $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM bot_messages 
                                       WHERE employee_id = ? 
                                       AND message_type = 'anniversary' 
                                       AND DATE(created_at) = CURDATE()");
            $check_stmt->execute([$employee['id']]);
            $count = $check_stmt->fetch()['count'];
            
            if ($count == 0) {
                if (sendAnniversaryMessage($employee['id'])) {
                    $processed++;
                    echo "✓ تم إرسال رسالة ذكرى سنوية للموظف ID: {$employee['id']} ({$years} سنة)\n";
                }
            }
        }
    }
}

// 4. إرسال نكات ورسائل إيجابية (مرة واحدة في اليوم)
if (getBotSetting('jokes_enabled', '1') == '1') {
    // إرسال نكتة عشوائية لموظف عشوائي
    $stmt = $db->query("SELECT id FROM employees WHERE status = 'active' ORDER BY RAND() LIMIT 1");
    $employee = $stmt->fetch();
    
    if ($employee) {
        // التحقق من عدم إرسال نكتة اليوم
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM bot_messages 
                                   WHERE employee_id = ? 
                                   AND message_type = 'joke' 
                                   AND DATE(created_at) = CURDATE()");
        $check_stmt->execute([$employee['id']]);
        $count = $check_stmt->fetch()['count'];
        
        if ($count == 0) {
            $joke = getRandomJoke();
            if (sendBotMessage($employee['id'], $joke, 'joke')) {
                $processed++;
                echo "✓ تم إرسال نكتة للموظف ID: {$employee['id']}\n";
            }
        }
    }
}

// 5. إرسال تحيات يومية (في الصباح فقط)
if (getBotSetting('daily_greetings_enabled', '1') == '1') {
    $hour = (int)date('H');
    
    // إرسال التحية فقط بين الساعة 8-10 صباحاً
    if ($hour >= 8 && $hour < 10) {
        $stmt = $db->query("SELECT id FROM employees WHERE status = 'active'");
        $employees = $stmt->fetchAll();
        
        foreach ($employees as $employee) {
            // التحقق من عدم إرسال تحية اليوم
            $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM bot_messages 
                                       WHERE employee_id = ? 
                                       AND message_type = 'greeting' 
                                       AND DATE(created_at) = CURDATE()");
            $check_stmt->execute([$employee['id']]);
            $count = $check_stmt->fetch()['count'];
            
            if ($count == 0) {
                if (sendDailyGreeting($employee['id'])) {
                    $processed++;
                    echo "✓ تم إرسال تحية للموظف ID: {$employee['id']}\n";
                }
            }
        }
    }
}

// 6. معالجة الإشعارات المجدولة
$stmt = $db->query("SELECT * FROM bot_notifications 
                   WHERE is_read = 0 
                   AND scheduled_at IS NOT NULL 
                   AND scheduled_at <= NOW() 
                   AND sent_at IS NULL 
                   LIMIT 50");
$notifications = $stmt->fetchAll();

foreach ($notifications as $notification) {
    if (sendBotMessage($notification['employee_id'], $notification['message'], 'notification')) {
        // تحديث وقت الإرسال
        $update_stmt = $db->prepare("UPDATE bot_notifications SET sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$notification['id']]);
        $processed++;
        echo "✓ تم إرسال إشعار ID: {$notification['id']}\n";
    }
}

echo "\nتم معالجة {$processed} مهمة بنجاح!\n";
echo "انتهى في: " . date('Y-m-d H:i:s') . "\n";

