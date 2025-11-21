<?php
/**
 * اختبار مباشر لـ api_simple.php
 */

define('ACCESS_ALLOWED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    die('يجب تسجيل الدخول');
}

$user_id = $_SESSION['user_id'];
$test_message = $_POST['message'] ?? $_GET['message'] ?? 'مرحبا';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار API المبسط</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
        input { padding: 10px; width: 300px; }
        button { padding: 10px 20px; }
    </style>
</head>
<body>
    <h1>🧪 اختبار API المبسط</h1>
    
    <div class="test">
        <form method="POST">
            <input type="text" name="message" value="<?php echo htmlspecialchars($test_message); ?>" placeholder="اكتب رسالة...">
            <button type="submit">إرسال</button>
        </form>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['message'])) {
        echo '<div class="test">';
        echo '<h2>النتيجة:</h2>';
        
        // محاكاة الطلب
        $_POST['action'] = 'process';
        $_POST['message'] = $test_message;
        $_SESSION['user_id'] = $user_id;
        
        ob_start();
        try {
            include __DIR__ . '/bot/api_simple.php';
            $output = ob_get_clean();
            
            echo '<p class="success">✓ تم التنفيذ بنجاح</p>';
            echo '<h3>الرد الخام:</h3>';
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
            
            $json = json_decode($output, true);
            if ($json) {
                echo '<h3>JSON Parsed:</h3>';
                echo '<pre>' . print_r($json, true) . '</pre>';
                
                if (isset($json['success']) && $json['success']) {
                    echo '<h3 class="success">✓ الرد:</h3>';
                    echo '<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; white-space: pre-wrap;">' . htmlspecialchars($json['response']) . '</div>';
                } else {
                    echo '<h3 class="error">✗ خطأ:</h3>';
                    echo '<div style="background: #ffebee; padding: 15px; border-radius: 5px;">' . htmlspecialchars($json['error'] ?? 'خطأ غير معروف') . '</div>';
                }
            } else {
                echo '<p class="error">✗ فشل تحليل JSON</p>';
            }
        } catch (Throwable $e) {
            ob_end_clean();
            echo '<p class="error">✗ خطأ: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        echo '</div>';
    }
    ?>
    
    <hr>
    <p><a href="bot/index.php">الانتقال إلى البوت</a> | <a href="debug_bot.php">تشخيص البوت</a></p>
</body>
</html>

