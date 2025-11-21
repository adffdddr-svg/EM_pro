<?php
/**
 * اختبار OpenAI API
 * افتح هذا الملف في المتصفح بعد تسجيل الدخول
 */

define('ACCESS_ALLOWED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    die('يجب تسجيل الدخول أولاً. <a href="auth/login.php">تسجيل الدخول</a>');
}

require_once __DIR__ . '/bot/openai_api.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار OpenAI API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test-form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        input[type="text"] {
            width: 70%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover { background: #0056b3; }
        .result {
            margin: 20px 0;
            padding: 20px;
            border-radius: 5px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 14px;
        }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 اختبار OpenAI API</h1>
        
        <?php
        $user_id = $_SESSION['user_id'];
        echo '<div class="info result">';
        echo '<strong>المستخدم:</strong> ' . htmlspecialchars($_SESSION['username']) . ' (ID: ' . $user_id . ')';
        echo '</div>';
        
        // التحقق من API Key
        $openai = new OpenAIAPI();
        $api_key = getenv('OPENAI_API_KEY') ?: 
                   (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
        
        if (empty($api_key)) {
            $key_file = __DIR__ . '/config/openai_key.txt';
            if (file_exists($key_file)) {
                $api_key = trim(file_get_contents($key_file));
            }
        }
        
        echo '<div class="test-form">';
        if (empty($api_key)) {
            echo '<div class="error result">';
            echo '<strong>❌ خطأ:</strong> OpenAI API Key غير موجود!<br><br>';
            echo 'يرجى إضافة API Key في أحد الأماكن التالية:<br>';
            echo '1. ملف: <code>config/openai_key.txt</code><br>';
            echo '2. في <code>config/config.php</code>: <code>define(\'OPENAI_API_KEY\', \'sk-...\');</code><br>';
            echo '3. متغير البيئة: <code>OPENAI_API_KEY</code><br><br>';
            echo 'راجع ملف <code>OPENAI_SETUP.md</code> للتعليمات الكاملة.';
            echo '</div>';
        } else {
            echo '<div class="success result">';
            echo '<strong>✓ API Key موجود:</strong> ' . substr($api_key, 0, 10) . '...';
            echo '</div>';
            
            // اختبار الاتصال
            if (isset($_GET['test'])) {
                $test_message = $_GET['message'] ?? 'مرحبا';
                echo '<div class="info result">';
                echo '<strong>جاري الاختبار...</strong><br><br>';
                echo '<strong>الرسالة:</strong> ' . htmlspecialchars($test_message) . '<br><br>';
                
                $response = $openai->chat($test_message, $user_id);
                
                if ($response['success']) {
                    echo '<div class="success result">';
                    echo '<strong>✓ نجح!</strong><br><br>';
                    echo '<strong>الرد:</strong><br>';
                    echo htmlspecialchars($response['response']);
                    if (isset($response['suggested_query'])) {
                        echo '<br><br><strong>Query المقترح:</strong><br>';
                        echo '<code>' . htmlspecialchars($response['suggested_query']) . '</code>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="error result">';
                    echo '<strong>✗ فشل!</strong><br><br>';
                    echo '<strong>الخطأ:</strong> ' . htmlspecialchars($response['error'] ?? 'خطأ غير معروف');
                    echo '</div>';
                }
                echo '</div>';
            }
            
            echo '<form method="GET">';
            echo '<label><strong>رسالة الاختبار:</strong></label><br>';
            echo '<input type="text" name="message" value="' . htmlspecialchars($_GET['message'] ?? 'مرحبا') . '" placeholder="اكتب رسالة للبوت">';
            echo '<button type="submit" name="test" value="1">اختبار</button>';
            echo '</form>';
        }
        echo '</div>';
        ?>
        
        <hr>
        <p>
            <a href="bot/index.php">الانتقال إلى صفحة البوت</a> | 
            <a href="admin/dashboard.php">لوحة التحكم</a> |
            <a href="OPENAI_SETUP.md" target="_blank">دليل الإعداد</a>
        </p>
    </div>
</body>
</html>

