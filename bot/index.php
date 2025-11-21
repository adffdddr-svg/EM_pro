<?php
/**
 * Employee Management System
 * صفحة البوت الذكي الرئيسية
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/bot_functions.php';

requireLogin();

$db = getDB();

// تحديد سياق البوت حسب الدور
$bot_context = 'general';
$bot_title = 'البوت الذكي - مساعد HR';
$employee_id = null;

if (isAdmin()) {
    // بوت المدير - يساعد في الإحصائيات وإدارة الموظفين
    $bot_context = 'admin';
    $bot_title = 'البوت الذكي - مساعد المدير';
    $employee_id = null; // المدير ليس موظفاً
} else if (isEmployee()) {
    // بوت الموظف - يساعد في معلوماته الشخصية
    $bot_context = 'employee';
    $bot_title = 'البوت الذكي - مساعدك الشخصي';
    // الحصول على employee_id من user_id
    $employee = getEmployeeByUserId($_SESSION['user_id']);
    $employee_id = $employee ? $employee['id'] : null;
} else {
    // حالة افتراضية
    $employee_id = $_SESSION['user_id'] ?? null;
}

// الحصول على الرسائل
$messages = [];
$unread_count = 0;
if ($employee_id) {
    $messages = getBotMessages($employee_id, 50);
    $unread_count = getUnreadBotMessagesCount($employee_id);
}

$page_title = $bot_title;
$additional_css = ['bot.css'];
$additional_js = ['bot.js'];
include __DIR__ . '/../includes/header.php';
?>

<div class="bot-page">
    <div class="bot-page-wrapper">
        <!-- Main Chat Panel -->
        <div class="bot-chat-panel">
            <div class="chat-panel-header">
                <h2 class="chat-title">
                    <?php if ($bot_context === 'admin'): ?>
                        🤖 البوت الذكي - مساعد المدير
                    <?php elseif ($bot_context === 'employee'): ?>
                        🤖 البوت الذكي - مساعدك الشخصي
                    <?php else: ?>
                        🤖 البوت الذكي - مساعد HR
                    <?php endif; ?>
                </h2>
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="bot-chat-section">
                <div class="bot-messages-list" id="botMessagesList">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="bot-message-item <?php echo $msg['message_type']; ?> animate-fade-in">
                                <div class="message-icon">
                                    <?php
                                    $icons = [
                                        'motivational' => '💪',
                                        'greeting' => '👋',
                                        'question' => '❓',
                                        'notification' => '🔔',
                                        'joke' => '😄',
                                        'birthday' => '🎂',
                                        'anniversary' => '🎉',
                                        'info' => '🤖'
                                    ];
                                    echo $icons[$msg['message_type']] ?? '🤖';
                                    ?>
                                </div>
                                <div class="message-content">
                                    <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                                    <span class="message-time"><?php echo formatDate($msg['created_at'], 'Y-m-d H:i:s'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-messages">
                            <p>لا توجد رسائل بعد. ابدأ المحادثة مع البوت! 👋</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Replies -->
                <div class="quick-replies-section">
                    <button class="quick-reply-btn" onclick="askQuestion('عرض معلومات موظف')">
                        <span class="quick-reply-icon">👤</span>
                        عرض معلومات موظف
                    </button>
                    <button class="quick-reply-btn" onclick="askQuestion('إضافة موظف جديد')">
                        <span class="quick-reply-icon">➕</span>
                        إضافة موظف جديد
                    </button>
                    <button class="quick-reply-btn" onclick="askQuestion('تعديل راتب')">
                        <span class="quick-reply-icon">💰</span>
                        تعديل راتب
                    </button>
                    <button class="quick-reply-btn" onclick="askQuestion('شكد راتبي؟')">
                        <span class="quick-reply-icon">💵</span>
                        راتبي
                    </button>
                    <button class="quick-reply-btn" onclick="askQuestion('كم إجازة متبقية؟')">
                        <span class="quick-reply-icon">📅</span>
                        الإجازات
                    </button>
                </div>

                <div class="bot-input-section">
                    <form id="botForm" onsubmit="return sendBotMessage(event)">
                        <button type="button" id="voiceRecordBtn" class="bot-voice-button" title="تسجيل صوتي">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/>
                            </svg>
                        </button>
                        <input type="text" id="botMessageInput" class="bot-input-field" 
                               placeholder="اكتب رسالتك هنا..." required>
                        <button type="submit" class="bot-send-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </form>
                    <div id="recordingIndicator" class="recording-indicator hidden">
                        <span class="recording-dot"></span>
                        <span>جاري التسجيل... اضغط مرة أخرى للتوقف</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating 3D Bot Avatar -->
        <div class="bot-avatar-3d">
            <div class="avatar-container">
                <!-- Placeholder for 3D bot image - replace with your 3D model/image -->
                <div class="bot-avatar-image">
                    <img src="<?php echo SITE_URL; ?>/assets/images/bot-avatar-placeholder.png" alt="3D Bot Avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="bot-avatar-fallback" style="display: none;">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                            <circle cx="60" cy="60" r="50" fill="#667eea" opacity="0.2"/>
                            <circle cx="60" cy="45" r="20" fill="#667eea"/>
                            <rect x="35" y="70" width="50" height="40" rx="10" fill="#667eea"/>
                            <circle cx="50" cy="85" r="5" fill="white"/>
                            <circle cx="70" cy="85" r="5" fill="white"/>
                            <rect x="45" y="95" width="30" height="5" rx="2" fill="white"/>
                        </svg>
                    </div>
                </div>
                <div class="avatar-glow"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern 3D Bot Page Styles */
.bot-page {
    padding: 0;
    min-height: calc(100vh - 100px);
    position: relative;
    overflow: hidden;
}

.bot-page-wrapper {
    position: relative;
    display: flex;
    gap: 30px;
    align-items: flex-start;
    padding: 20px;
    min-height: calc(100vh - 140px);
}

/* Gradient Background - مطابق للصورة */
.bot-page::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    /* تدرج عمودي من الأزرق الفاتح إلى الخوخي/البرتقالي */
    background: linear-gradient(180deg, 
        #a8c8ec 0%,      /* أزرق فاتح في الأعلى */
        #b8d4f0 15%,     /* أزرق فاتح جداً */
        #d4e4f7 30%,     /* أزرق فاتح جداً */
        #e8f0f8 45%,     /* أزرق فاتح شفاف */
        #f0e6d2 60%,     /* بيج فاتح */
        #f8e8d0 75%,     /* خوخي فاتح */
        #ffd3a5 85%,     /* خوخي */
        #fd9853 100%     /* برتقالي فاتح في الأسفل */
    );
    z-index: 0;
    opacity: 1;
}

/* أنماط cloud-like patterns */
.bot-page::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 15% 25%, rgba(255, 255, 255, 0.15) 0%, transparent 45%),
        radial-gradient(circle at 85% 75%, rgba(255, 255, 255, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 60%),
        radial-gradient(circle at 30% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
        radial-gradient(circle at 70% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 40%);
    z-index: 0;
    pointer-events: none;
    animation: cloudMove 25s ease-in-out infinite;
}

@keyframes cloudMove {
    0%, 100% { 
        transform: translateY(0) translateX(0); 
        opacity: 1;
    }
    50% { 
        transform: translateY(-15px) translateX(8px); 
        opacity: 0.9;
    }
}

.bot-page-wrapper > * {
    position: relative;
    z-index: 1;
}

/* Main Chat Panel - مطابق للصورة */
.bot-chat-panel {
    flex: 1;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12),
                0 0 0 1px rgba(255, 255, 255, 0.6);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-width: 850px;
    min-height: 650px;
    margin: 0 auto;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.chat-panel-header {
    padding: 24px 30px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-title {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.unread-badge {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.bot-chat-section {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
}

.bot-messages-list {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
    background: transparent;
    scroll-behavior: smooth;
    min-height: 400px;
}

.bot-messages-list::-webkit-scrollbar {
    width: 8px;
}

.bot-messages-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.bot-messages-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.bot-messages-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.bot-message-item {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    padding: 18px 22px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    max-width: 70%;
    position: relative;
    animation: fadeInUp 0.4s ease-out;
}

.bot-message-item:hover {
    transform: translateY(-2px);
}

/* فقاعات المستخدم - على اليسار (RTL) - رمادي فاتح */
.bot-message-item.user {
    background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
    color: #2c3e50;
    margin-right: 0;
    margin-left: auto;
    border-radius: 24px 24px 4px 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08),
                0 2px 6px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.bot-message-item.user .message-content p {
    color: #2c3e50;
    margin: 0;
    line-height: 1.6;
}

.bot-message-item.user .message-time {
    color: rgba(44, 62, 80, 0.6);
    font-size: 11px;
    margin-top: 8px;
}

.bot-message-item.user .message-icon {
    background: rgba(0, 0, 0, 0.05);
    border: 2px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* فقاعات البوت - على اليمين (RTL) - أزرق فاتح */
.bot-message-item.info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #2c3e50;
    margin-left: 0;
    margin-right: auto;
    border-radius: 24px 24px 24px 4px;
    box-shadow: 0 4px 16px rgba(33, 150, 243, 0.15),
                0 2px 6px rgba(33, 150, 243, 0.1);
    border: 1px solid rgba(33, 150, 243, 0.2);
}

.bot-message-item.info .message-content p {
    color: #2c3e50;
    margin: 0;
    line-height: 1.6;
}

.bot-message-item.info .message-time {
    color: rgba(44, 62, 80, 0.6);
    font-size: 11px;
    margin-top: 8px;
}

.bot-message-item.info .message-icon {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.2) 0%, rgba(33, 150, 243, 0.15) 100%);
    border: 2px solid rgba(33, 150, 243, 0.3);
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.animate-fade-in {
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-icon {
    font-size: 22px;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.message-content {
    flex: 1;
}

.message-content p {
    margin: 0 0 10px 0;
    color: var(--text-color);
    line-height: 1.7;
    font-size: 14px;
    font-weight: 400;
}

.message-time {
    font-size: 11px;
    color: #999;
    display: block;
    margin-top: 8px;
    font-weight: 300;
}

.loading-dots {
    display: flex;
    gap: 6px;
    align-items: center;
    padding: 8px 0;
}

.loading-dots span {
    width: 8px;
    height: 8px;
    background: #667eea;
    border-radius: 50%;
    animation: loading-bounce 1.4s infinite ease-in-out;
}

.loading-dots span:nth-child(1) {
    animation-delay: -0.32s;
}

.loading-dots span:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes loading-bounce {
    0%, 80%, 100% {
        transform: scale(0);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.bot-input-section {
    padding: 20px 30px;
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    backdrop-filter: blur(20px);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
}

/* Quick Replies Section */
.quick-replies-section {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px 30px;
    background: rgba(255, 255, 255, 0.7);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    backdrop-filter: blur(15px);
}

.quick-reply-btn {
    padding: 12px 20px;
    background: white;
    border: 2px solid transparent;
    border-radius: 24px;
    color: #667eea;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.quick-reply-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: left 0.3s ease;
    z-index: -1;
    border-radius: 24px;
}

.quick-reply-btn:hover {
    color: white;
    border-color: transparent;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.quick-reply-btn:hover::before {
    left: 0;
}

.quick-reply-icon {
    font-size: 16px;
}

.bot-input-section {
    position: relative;
}

.bot-input-section form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bot-voice-button {
    width: 48px;
    height: 48px;
    background: rgba(240, 240, 240, 0.8);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bot-voice-button:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    color: #667eea;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.bot-voice-button.recording {
    background: #e74c3c;
    border-color: #c0392b;
    color: white;
    animation: pulse-recording 1.5s infinite;
}

@keyframes pulse-recording {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
    }
}

.recording-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 20px;
    color: #c0392b;
    font-size: 12px;
    margin-top: 8px;
}

.recording-indicator.hidden {
    display: none;
}

.recording-dot {
    width: 10px;
    height: 10px;
    background: #e74c3c;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.bot-input-field {
    flex: 1;
    padding: 16px 24px;
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 28px;
    font-size: 15px;
    outline: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: inherit;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    color: #2c3e50;
}

.bot-input-field:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1),
                0 4px 12px rgba(102, 126, 234, 0.15);
    background: white;
}

.bot-input-field::placeholder {
    color: rgba(44, 62, 80, 0.5);
}

.bot-send-button {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4),
                0 2px 4px rgba(102, 126, 234, 0.2);
}

.bot-send-button:hover {
    transform: scale(1.15) rotate(5deg);
    box-shadow: 0 6px 24px rgba(102, 126, 234, 0.5),
                0 4px 8px rgba(102, 126, 234, 0.3);
}

.audio-player {
    width: 100%;
    margin-top: 5px;
    outline: none;
}

/* Floating 3D Bot Avatar - مطابق للصورة */
.bot-avatar-3d {
    position: fixed;
    bottom: 40px;
    left: 40px;  /* على اليمين في RTL */
    width: 200px;
    height: 200px;
    z-index: 100;
    pointer-events: none;
}

.avatar-container {
    position: relative;
    width: 100%;
    height: 100%;
    animation: float 4s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { 
        transform: translateY(0px) rotate(0deg); 
    }
    50% { 
        transform: translateY(-25px) rotate(3deg); 
    }
}

.bot-avatar-image {
    width: 100%;
    height: 100%;
    position: relative;
    filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.2));
}

.bot-avatar-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    animation: rotate3d 20s linear infinite;
}

@keyframes rotate3d {
    0% { transform: perspective(1000px) rotateY(0deg); }
    100% { transform: perspective(1000px) rotateY(360deg); }
}

.bot-avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 50%;
    border: 3px solid rgba(102, 126, 234, 0.3);
}

.avatar-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 150%;
    height: 150%;
    background: radial-gradient(circle, 
        rgba(102, 126, 234, 0.4) 0%, 
        rgba(102, 126, 234, 0.25) 30%,
        rgba(102, 126, 234, 0.15) 50%,
        transparent 70%
    );
    border-radius: 50%;
    animation: glow 3s ease-in-out infinite alternate;
    z-index: -1;
    filter: blur(25px);
}

@keyframes glow {
    from { 
        opacity: 0.6; 
        transform: translate(-50%, -50%) scale(1); 
    }
    to { 
        opacity: 0.9; 
        transform: translate(-50%, -50%) scale(1.15); 
    }
}

.empty-messages {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-messages p {
    font-size: 16px;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .bot-avatar-3d {
        display: none;
    }
    
    .bot-chat-panel {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .bot-page-wrapper {
        padding: 15px;
        gap: 0;
    }
    
    .bot-chat-panel {
        min-height: calc(100vh - 100px);
        border-radius: 20px;
    }
    
    .chat-panel-header {
        padding: 20px;
    }
    
    .chat-title {
        font-size: 20px;
    }
    
    .bot-messages-list {
        padding: 20px;
    }
    
    .bot-message-item {
        max-width: 85%;
        padding: 14px 18px;
    }
    
    .quick-replies-section {
        padding: 15px 20px;
        gap: 8px;
    }
    
    .quick-reply-btn {
        padding: 8px 14px;
        font-size: 12px;
    }
    
    .bot-input-section {
        padding: 15px 20px;
    }
    
    .bot-input-field {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .bot-voice-button,
    .bot-send-button {
        width: 44px;
        height: 44px;
    }
}

/* تحسينات للهاتف - شاشات صغيرة */
@media (max-width: 768px) {
    .bot-page-wrapper {
        padding: 0;
    }
    
    .bot-chat-panel {
        border-radius: 0;
        height: 100vh;
    }
    
    .chat-panel-header {
        padding: 15px 20px;
        border-radius: 0;
    }
    
    .chat-title {
        font-size: 18px;
    }
    
    .bot-chat-section {
        padding: 15px;
        height: calc(100vh - 200px);
    }
    
    .bot-message-item {
        padding: 12px;
        margin-bottom: 12px;
        gap: 10px;
    }
    
    .message-icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }
    
    .message-content p {
        font-size: 14px;
    }
    
    .message-time {
        font-size: 10px;
    }
    
    .quick-replies-section {
        padding: 15px;
        gap: 8px;
        flex-wrap: wrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .quick-reply-btn {
        padding: 8px 14px;
        font-size: 12px;
        white-space: nowrap;
        min-width: auto;
    }
    
    .quick-reply-icon {
        font-size: 14px;
    }
    
    .bot-input-section {
        padding: 15px;
    }
    
    .bot-input-wrapper {
        gap: 10px;
    }
    
    .bot-input-field {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .bot-send-button,
    .bot-voice-button {
        width: 44px;
        height: 44px;
        min-width: 44px;
    }
    
    .bot-avatar-3d {
        display: none; /* إخفاء على الشاشات الصغيرة */
    }
}

@media (max-width: 480px) {
    .bot-chat-panel {
        height: 100vh;
        border-radius: 0;
    }
    
    .chat-panel-header {
        padding: 12px 15px;
    }
    
    .chat-title {
        font-size: 16px;
    }
    
    .bot-chat-section {
        padding: 12px;
        height: calc(100vh - 180px);
    }
    
    .bot-message-item {
        padding: 10px;
        margin-bottom: 10px;
    }
    
    .message-icon {
        width: 32px;
        height: 32px;
        font-size: 16px;
    }
    
    .message-content p {
        font-size: 13px;
    }
    
    .quick-replies-section {
        padding: 12px;
        gap: 6px;
    }
    
    .quick-reply-btn {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .bot-input-section {
        padding: 12px;
    }
    
    .bot-input-field {
        padding: 10px 14px;
        font-size: 13px;
    }
    
    .bot-send-button,
    .bot-voice-button {
        width: 40px;
        height: 40px;
    }
    
    /* تحسين touch targets للهاتف */
    .bot-send-button,
    .bot-voice-button,
    .quick-reply-btn {
        -webkit-tap-highlight-color: rgba(102, 126, 234, 0.3);
        touch-action: manipulation;
    }
}
</style>

<script>
function askQuestion(question) {
    document.getElementById('botMessageInput').value = question;
    sendBotMessage(null);
}

function sendBotMessage(e) {
    if (e) e.preventDefault();
    
    const input = document.getElementById('botMessageInput');
    const message = input.value.trim();
    
    if (!message) return false;
    
    // إضافة رسالة المستخدم
    addMessageToPage(message, 'user');
    input.value = '';
    
    // إظهار loading
    showLoading();
    
    // إرسال للبوت
    // محاولة استخدام api_simple.php أولاً (أكثر موثوقية)
    const apiUrl = '<?php echo SITE_URL; ?>/bot/api_simple.php';
    console.log('Sending to:', apiUrl);
    console.log('Message:', message);
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=process&message=${encodeURIComponent(message)}`,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            // محاولة قراءة JSON حتى في حالة الخطأ
            return response.text().then(text => {
                console.error('Error response:', text);
                try {
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                } catch (e) {
                    throw new Error(`HTTP error! status: ${response.status}, body: ${text.substring(0, 100)}`);
                }
            });
        }
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        console.log('Parsed data:', data);
        hideLoading();
        if (data && data.success) {
            addMessageToPage(data.response || 'تم استلام الرد', 'bot');
        } else {
            const errorMsg = (data && data.error) ? data.error : 'حدث خطأ غير معروف';
            addMessageToPage('عذراً، ' + errorMsg + '. يرجى المحاولة مرة أخرى.', 'bot');
            console.error('Bot API Error:', data);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Bot Error:', error);
        console.error('Error details:', error.message, error.stack);
        
        // محاولة إظهار رسالة خطأ أكثر وضوحاً
        let errorMsg = 'عذراً، حدث خطأ في الاتصال.';
        
        if (error.message) {
            console.error('Error message:', error.message);
        }
        
        // إذا كان الخطأ متعلقاً بالشبكة
        if (error.message && error.message.includes('Failed to fetch')) {
            errorMsg = 'عذراً، لا يمكن الاتصال بالخادم. تأكد من اتصالك بالإنترنت.';
        } else if (error.message && error.message.includes('HTTP error')) {
            errorMsg = 'عذراً، حدث خطأ في الخادم. يرجى المحاولة مرة أخرى.';
        }
        
        addMessageToPage(errorMsg, 'bot');
    });
    
    return false;
}

function addMessageToPage(text, type) {
    const messagesList = document.getElementById('botMessagesList');
    const emptyMsg = messagesList.querySelector('.empty-messages');
    if (emptyMsg) emptyMsg.remove();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `bot-message-item ${type === 'bot' ? 'info' : 'user'} animate-fade-in`;
    messageDiv.innerHTML = `
        <div class="message-icon">${type === 'bot' ? '🤖' : '👤'}</div>
        <div class="message-content">
            <p>${text.replace(/\n/g, '<br>')}</p>
            <span class="message-time">${new Date().toLocaleTimeString('ar')}</span>
        </div>
    `;
    
    messagesList.appendChild(messageDiv);
    messagesList.scrollTop = messagesList.scrollHeight;
}

// Voice Recording
let isRecording = false;
let mediaRecorder = null;
let audioChunks = [];

document.getElementById('voiceRecordBtn').addEventListener('click', async function() {
    if (!isRecording) {
        await startRecording();
    } else {
        stopRecording();
    }
});

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            addAudioMessage(audioBlob);
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        isRecording = true;
        document.getElementById('recordingIndicator').classList.remove('hidden');
        document.getElementById('voiceRecordBtn').classList.add('recording');
    } catch (error) {
        console.error('Error starting recording:', error);
        alert('لا يمكن الوصول إلى الميكروفون. يرجى التحقق من الصلاحيات.');
    }
}

function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('recordingIndicator').classList.add('hidden');
        document.getElementById('voiceRecordBtn').classList.remove('recording');
    }
}

function addAudioMessage(audioBlob) {
    const audioUrl = URL.createObjectURL(audioBlob);
    const messagesList = document.getElementById('botMessagesList');
    const emptyMsg = messagesList.querySelector('.empty-messages');
    if (emptyMsg) emptyMsg.remove();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'bot-message-item user animate-fade-in';
    messageDiv.innerHTML = `
        <div class="message-icon">🎤</div>
        <div class="message-content">
            <audio controls class="audio-player">
                <source src="${audioUrl}" type="audio/webm">
                متصفحك لا يدعم تشغيل الصوت.
            </audio>
            <span class="message-time">${new Date().toLocaleTimeString('ar')}</span>
        </div>
    `;
    
    messagesList.appendChild(messageDiv);
    messagesList.scrollTop = messagesList.scrollHeight;
}

// Show loading indicator
function showLoading() {
    const messagesList = document.getElementById('botMessagesList');
    const emptyMsg = messagesList.querySelector('.empty-messages');
    if (emptyMsg) emptyMsg.remove();
    
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingMessage';
    loadingDiv.className = 'bot-message-item info animate-fade-in';
    loadingDiv.innerHTML = `
        <div class="message-icon">🤖</div>
        <div class="message-content">
            <div class="loading-dots">
                <span></span><span></span><span></span>
            </div>
            <span class="message-time">يكتب...</span>
        </div>
    `;
    
    messagesList.appendChild(loadingDiv);
    messagesList.scrollTop = messagesList.scrollHeight;
}

function hideLoading() {
    const loadingDiv = document.getElementById('loadingMessage');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

