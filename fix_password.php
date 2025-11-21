<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح كلمة المرور</title>
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
            max-width: 600px;
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
        <h1>إصلاح كلمة المرور</h1>
        
        <?php
        require_once __DIR__ . '/config/config.php';
        
        $db_host = DB_HOST;
        $db_user = DB_USER;
        $db_pass = DB_PASS;
        $db_name = DB_NAME;
        
        $errors = [];
        $success = [];
        
        try {
            // الاتصال بقاعدة البيانات
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // التحقق من وجود المستخدم
            $stmt = $pdo->query("SELECT id, username, email FROM users WHERE username = 'admin'");
            $user = $stmt->fetch();
            
            if ($user) {
                // تحديث كلمة المرور
                $new_password = 'admin123';
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                $stmt->execute([$password_hash]);
                
                $success[] = "✓ تم تحديث كلمة مرور المستخدم 'admin' بنجاح";
                $success[] = "كلمة المرور الجديدة: <strong>admin123</strong>";
                
                // التحقق من أن كلمة المرور تعمل
                if (password_verify($new_password, $password_hash)) {
                    $success[] = "✓ تم التحقق من كلمة المرور - تعمل بشكل صحيح";
                }
                
            } else {
                // إنشاء المستخدم إذا لم يكن موجوداً
                $new_password = 'admin123';
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute(['admin', $password_hash, 'admin@example.com', 'admin']);
                
                $success[] = "✓ تم إنشاء المستخدم 'admin' بنجاح";
                $success[] = "كلمة المرور: <strong>admin123</strong>";
            }
            
            // عرض جميع المستخدمين
            $stmt = $pdo->query("SELECT id, username, email, role FROM users");
            $all_users = $stmt->fetchAll();
            
            if (count($all_users) > 0) {
                $success[] = "<br><strong>المستخدمون الموجودون:</strong>";
                $success[] = "<ul>";
                foreach ($all_users as $u) {
                    $success[] = "<li>اسم المستخدم: <strong>{$u['username']}</strong> - البريد: {$u['email']} - الدور: {$u['role']}</li>";
                }
                $success[] = "</ul>";
            }
            
        } catch (PDOException $e) {
            $errors[] = "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
            $errors[] = "تأكد من أن قاعدة البيانات موجودة وأن الجداول تم إنشاؤها";
        }
        
        // عرض النتائج
        if (!empty($errors)) {
            echo '<div class="status error">';
            echo '<h3>✗ الأخطاء:</h3>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
            echo '<p><a href="setup.php" class="btn">إنشاء قاعدة البيانات</a></p>';
            echo '</div>';
        }
        
        if (!empty($success)) {
            echo '<div class="status success">';
            echo '<h3>✓ النجاح!</h3>';
            foreach ($success as $msg) {
                echo $msg;
            }
            echo '<br><br>';
            echo '<a href="index.php" class="btn btn-success">الانتقال إلى صفحة تسجيل الدخول</a>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

