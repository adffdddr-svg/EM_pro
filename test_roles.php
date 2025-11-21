<?php
/**
 * Employee Management System
 * صفحة اختبار الأدوار والصلاحيات
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار الأدوار والصلاحيات</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>اختبار نظام الأدوار والصلاحيات</h1>
    
    <div class="test-box">
        <h2>1. حالة تسجيل الدخول</h2>
        <?php if (isLoggedIn()): ?>
            <p class="success">✓ تم تسجيل الدخول</p>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'غير موجود'; ?></p>
            <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'غير موجود'; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['email'] ?? 'غير موجود'; ?></p>
        <?php else: ?>
            <p class="error">✗ لم يتم تسجيل الدخول</p>
            <p><a href="<?php echo SITE_URL; ?>/auth/login.php">تسجيل الدخول</a></p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>2. الدور في الجلسة (Session)</h2>
        <?php if (isset($_SESSION['role'])): ?>
            <p class="info"><strong>الدور الحالي:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        <?php else: ?>
            <p class="error">✗ الدور غير موجود في الجلسة</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>3. اختبار الدوال</h2>
        <p><strong>isAdmin():</strong> 
            <?php 
            $result = isAdmin();
            echo $result ? '<span class="success">✓ true</span>' : '<span class="error">✗ false</span>';
            ?>
        </p>
        <p><strong>isEmployee():</strong> 
            <?php 
            $result = isEmployee();
            echo $result ? '<span class="success">✓ true</span>' : '<span class="error">✗ false</span>';
            ?>
        </p>
        <p><strong>hasRole('admin'):</strong> 
            <?php 
            $result = hasRole('admin');
            echo $result ? '<span class="success">✓ true</span>' : '<span class="error">✗ false</span>';
            ?>
        </p>
        <p><strong>hasRole('employee'):</strong> 
            <?php 
            $result = hasRole('employee');
            echo $result ? '<span class="success">✓ true</span>' : '<span class="error">✗ false</span>';
            ?>
        </p>
    </div>

    <div class="test-box">
        <h2>4. معلومات من قاعدة البيانات</h2>
        <?php
        if (isLoggedIn()) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<p class='info'><strong>من قاعدة البيانات:</strong></p>";
                echo "<pre>";
                print_r($user);
                echo "</pre>";
                
                if ($user['role'] !== $_SESSION['role']) {
                    echo "<p class='error'>⚠ تحذير: الدور في قاعدة البيانات ({$user['role']}) يختلف عن الدور في الجلسة ({$_SESSION['role']})</p>";
                    echo "<p>الحل: سجل خروج ثم سجل دخول مرة أخرى</p>";
                }
            } else {
                echo "<p class='error'>✗ المستخدم غير موجود في قاعدة البيانات</p>";
            }
        } else {
            echo "<p class='error'>يجب تسجيل الدخول أولاً</p>";
        }
        ?>
    </div>

    <div class="test-box">
        <h2>5. محتوى الجلسة الكامل</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="test-box">
        <h2>6. اختبار العناصر المشروطة</h2>
        <?php if (isAdmin()): ?>
            <p class="success">✓ هذا النص يظهر فقط للمديرين</p>
        <?php else: ?>
            <p class="error">✗ أنت لست مديراً - هذا النص لا يظهر</p>
        <?php endif; ?>

        <?php if (isEmployee()): ?>
            <p class="success">✓ هذا النص يظهر فقط للموظفين</p>
        <?php else: ?>
            <p class="error">✗ أنت لست موظفاً - هذا النص لا يظهر</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>7. روابط سريعة</h2>
        <p><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">لوحة التحكم</a></p>
        <p><a href="<?php echo SITE_URL; ?>/auth/logout.php">تسجيل الخروج</a></p>
        <p><a href="<?php echo SITE_URL; ?>/auth/login.php">تسجيل الدخول</a></p>
    </div>
</body>
</html>

