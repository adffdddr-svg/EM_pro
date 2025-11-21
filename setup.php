<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد قاعدة البيانات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        h1 { color: #2c3e50; margin-bottom: 30px; text-align: center; }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        ul { margin: 15px 0; padding-right: 30px; line-height: 2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>إعداد قاعدة البيانات</h1>
        
        <?php
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        
        $errors = [];
        $success = [];
        $output = [];
        
        // قراءة ملف SQL
        $sql_file = __DIR__ . '/database/schema.sql';
        
        if (!file_exists($sql_file)) {
            $errors[] = "ملف schema.sql غير موجود!";
        } else {
            $sql_content = file_get_contents($sql_file);
            
            if (empty($sql_content)) {
                $errors[] = "ملف schema.sql فارغ!";
            } else {
                try {
                    // الاتصال بـ MySQL
                    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $output[] = "✓ الاتصال بـ MySQL نجح";
                    
                    // قراءة ملف SQL الأساسي
                    $lines = file($sql_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    
                    // قراءة ملف SQL للبوت أيضاً
                    $bot_sql_file = __DIR__ . '/database/bot_schema.sql';
                    if (file_exists($bot_sql_file)) {
                        $bot_lines = file($bot_sql_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $lines = array_merge($lines, $bot_lines);
                    }
                    
                    $current_statement = '';
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        
                        // تجاهل التعليقات والأسطر الفارغة
                        if (empty($line) || 
                            strpos($line, '--') === 0 || 
                            strpos($line, '/*') === 0 ||
                            strpos($line, '*/') !== false) {
                            continue;
                        }
                        
                        // إضافة السطر إلى الاستعلام الحالي
                        $current_statement .= $line . ' ';
                        
                        // إذا انتهى الاستعلام بفاصلة منقوطة
                        if (substr(rtrim($current_statement), -1) === ';') {
                            $statement = trim($current_statement);
                            $statement = rtrim($statement, ';');
                            
                            if (!empty($statement) && strlen($statement) > 10) {
                                try {
                                    $pdo->exec($statement);
                                    $stmt_type = strtoupper(substr(trim($statement), 0, 6));
                                    $output[] = "✓ تم تنفيذ: $stmt_type...";
                                } catch (PDOException $e) {
                                    $error_msg = $e->getMessage();
                                    
                                    // تجاهل الأخطاء المتعلقة بالموجود مسبقاً
                                    if (stripos($error_msg, 'already exists') === false && 
                                        stripos($error_msg, 'Duplicate') === false) {
                                        $output[] = "⚠ تحذير: " . substr($error_msg, 0, 80);
                                    }
                                }
                            }
                            
                            $current_statement = '';
                        }
                    }
                    
                    // التحقق من إنشاء الجداول
                    $pdo->exec("USE employee_management");
                    $tables = ['users', 'departments', 'employees', 'employees_archive'];
                    $all_tables_exist = true;
                    
                    foreach ($tables as $table) {
                        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                        if (!$stmt->fetch()) {
                            $all_tables_exist = false;
                            $errors[] = "جدول '$table' غير موجود";
                        }
                    }
                    
                    if ($all_tables_exist) {
                        $success[] = "تم إنشاء قاعدة البيانات والجداول بنجاح!";
                        
                        // التحقق من وجود المستخدم الافتراضي
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                        $user_count = $stmt->fetch()['count'];
                        
                        if ($user_count > 0) {
                            // تحديث كلمة المرور للمستخدم الموجود
                            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
                            $pdo->exec("UPDATE users SET password = '$password_hash' WHERE username = 'admin'");
                            $success[] = "✓ تم تحديث كلمة مرور المستخدم 'admin'";
                        } else {
                            // إدراج المستخدم الافتراضي
                            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
                            $pdo->exec("INSERT INTO users (username, password, email, role) VALUES ('admin', '$password_hash', 'admin@example.com', 'admin')");
                            $success[] = "✓ تم إنشاء المستخدم الافتراضي (admin)";
                        }
                    }
                    
                } catch (PDOException $e) {
                    $errors[] = "خطأ في الاتصال: " . $e->getMessage();
                }
            }
        }
        
        // عرض النتائج
        if (!empty($output)) {
            echo '<div class="status info">';
            echo '<h3>سجل التنفيذ:</h3>';
            echo '<pre>' . implode("\n", $output) . '</pre>';
            echo '</div>';
        }
        
        if (!empty($errors)) {
            echo '<div class="status error">';
            echo '<h3>✗ الأخطاء:</h3>';
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
            
            echo '<p><strong>بيانات الدخول:</strong></p>';
            echo '<ul>';
            echo '<li>اسم المستخدم: <strong>admin</strong></li>';
            echo '<li>كلمة المرور: <strong>admin123</strong></li>';
            echo '</ul>';
            
            echo '<a href="index.php" class="btn btn-success">الانتقال إلى الصفحة الرئيسية</a>';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<h3>فشل التثبيت</h3>';
            echo '<p>تأكد من:</p>';
            echo '<ul>';
            echo '<li>✓ تشغيل WAMP Server</li>';
            echo '<li>✓ تشغيل MySQL (يجب أن تكون الأيقونة خضراء)</li>';
            echo '<li>✓ إعدادات قاعدة البيانات في config/config.php صحيحة</li>';
            echo '</ul>';
            echo '<p><a href="check_db.php" class="btn">فحص قاعدة البيانات</a></p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

