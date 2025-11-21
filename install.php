<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت قاعدة البيانات - نظام إدارة الموظفين</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        ul {
            margin: 15px 0;
            padding-right: 30px;
            line-height: 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تثبيت قاعدة البيانات</h1>
        
        <?php
        // إعدادات قاعدة البيانات
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        
        $errors = [];
        $success = [];
        
        // قراءة ملف SQL
        $sql_file = __DIR__ . '/database/schema.sql';
        
        if (!file_exists($sql_file)) {
            $errors[] = "ملف schema.sql غير موجود في: $sql_file";
        } else {
            $sql_content = file_get_contents($sql_file);
            
            if (!$sql_content) {
                $errors[] = "لا يمكن قراءة ملف schema.sql";
            } else {
                try {
                    // الاتصال بـ MySQL بدون تحديد قاعدة بيانات
                    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    echo '<div class="status info">جاري إنشاء قاعدة البيانات والجداول...</div>';
                    echo '<pre>';
                    
                    // تقسيم ملف SQL إلى استعلامات منفصلة
                    // إزالة التعليقات أولاً
                    $lines = explode("\n", $sql_content);
                    $cleaned_lines = [];
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // تجاهل التعليقات والأسطر الفارغة
                        if (empty($line) || 
                            strpos($line, '--') === 0 || 
                            strpos($line, '/*') === 0 ||
                            strpos($line, '*/') !== false) {
                            continue;
                        }
                        $cleaned_lines[] = $line;
                    }
                    $sql_content = implode("\n", $cleaned_lines);
                    
                    // تقسيم الاستعلامات
                    $statements = explode(';', $sql_content);
                    
                    $executed = 0;
                    $skipped = 0;
                    
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        
                        if (empty($statement) || strlen($statement) < 10) {
                            continue;
                        }
                        
                        try {
                            $pdo->exec($statement);
                            $executed++;
                            $stmt_preview = substr($statement, 0, 50);
                            echo "✓ تم تنفيذ: $stmt_preview...\n";
                        } catch (PDOException $e) {
                            $error_msg = $e->getMessage();
                            
                            // تجاهل الأخطاء المتعلقة بإنشاء الجداول الموجودة مسبقاً
                            if (stripos($error_msg, 'already exists') !== false || 
                                stripos($error_msg, 'Duplicate entry') !== false ||
                                stripos($error_msg, 'Duplicate key') !== false ||
                                stripos($error_msg, 'Table') !== false && stripos($error_msg, 'already exists') !== false) {
                                $skipped++;
                                // لا نعرض هذه الأخطاء لأنها طبيعية
                            } else {
                                $errors[] = "خطأ في الاستعلام: " . substr($error_msg, 0, 200);
                                echo "✗ خطأ: " . substr($error_msg, 0, 100) . "\n";
                            }
                        }
                    }
                    
                    echo "</pre>";
                    
                    if (empty($errors) || $executed > 0) {
                        $success[] = "تم إنشاء قاعدة البيانات بنجاح!";
                        $success[] = "تم تنفيذ $executed استعلام بنجاح";
                        if ($skipped > 0) {
                            $success[] = "تم تخطي $skipped استعلام (موجود مسبقاً)";
                        }
                    }
                    
                } catch (PDOException $e) {
                    $errors[] = "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
                }
            }
        }
        
        // عرض النتائج
        if (!empty($errors)) {
            echo '<div class="status error">';
            echo '<h3>حدثت الأخطاء التالية:</h3>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (!empty($success)) {
            echo '<div class="status success">';
            echo '<h3>✓ النجاح!</h3>';
            echo '<ul>';
            foreach ($success as $msg) {
                echo '<li>' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul>';
            
            echo '<p><strong>بيانات الدخول الافتراضية:</strong></p>';
            echo '<ul>';
            echo '<li>اسم المستخدم: <strong>admin</strong></li>';
            echo '<li>كلمة المرور: <strong>admin123</strong></li>';
            echo '</ul>';
            
            echo '<a href="index.php" class="btn">الانتقال إلى الصفحة الرئيسية</a>';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<h3>فشل التثبيت</h3>';
            echo '<p>تأكد من:</p>';
            echo '<ul>';
            echo '<li>تشغيل WAMP Server</li>';
            echo '<li>تشغيل MySQL (يجب أن تكون الأيقونة خضراء)</li>';
            echo '<li>إعدادات قاعدة البيانات في config/config.php صحيحة</li>';
            echo '<li>كلمة مرور MySQL (قد تكون فارغة أو "root")</li>';
            echo '</ul>';
            echo '<p><strong>ملاحظة:</strong> يمكنك إنشاء قاعدة البيانات يدوياً من phpMyAdmin باستخدام ملف database/schema.sql</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
