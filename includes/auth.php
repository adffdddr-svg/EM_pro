<?php
/**
 * Employee Management System
 * دوال المصادقة والأمان
 */

// منع الوصول المباشر
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

// تضمين ملف الدوال المساعدة (للاستخدام في redirect)
require_once __DIR__ . '/functions.php';

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * تسجيل الدخول
 * @param string $username اسم المستخدم أو البريد الإلكتروني
 * @param string $password كلمة المرور
 * @param bool $remember_me تذكرني (حفظ تسجيل الدخول لمدة 30 يوم)
 * @return bool
 */
function login($username, $password, $remember_me = false) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password, email, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['just_logged_in'] = true; // للترحيب

        // Remember Me - حفظ في Cookie لمدة 30 يوم
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 يوم
            
            // حفظ Token في قاعدة البيانات
            try {
                $stmt = $db->prepare("INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
                $stmt->execute([$user['id'], $token, date('Y-m-d H:i:s', $expires), $token, date('Y-m-d H:i:s', $expires)]);
                
                // حفظ في Cookie
                setcookie('remember_token', $token, $expires, '/', '', false, true);
            } catch (Exception $e) {
                // إذا لم يكن الجدول موجوداً، تجاهل الخطأ
                error_log("Remember token error: " . $e->getMessage());
            }
        }
        
        return true;
    }

    return false;
}

/**
 * تسجيل الخروج
 */
function logout() {
    // حذف Remember Token من قاعدة البيانات
    if (isset($_COOKIE['remember_token'])) {
        $db = getDB();
        try {
            $stmt = $db->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
        } catch (Exception $e) {
            error_log("Logout token error: " . $e->getMessage());
        }
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * التحقق من Remember Token وتسجيل الدخول تلقائياً
 * @return bool
 */
function checkRememberToken() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        $db = getDB();
        $token = $_COOKIE['remember_token'];
        
        try {
            $stmt = $db->prepare("SELECT u.id, u.username, u.email, u.role, t.user_id 
                                  FROM user_remember_tokens t 
                                  JOIN users u ON t.user_id = u.id 
                                  WHERE t.token = ? AND t.expires_at > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                return true;
            } else {
                // حذف Cookie غير صالح
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (Exception $e) {
            // إذا لم يكن الجدول موجوداً، تجاهل الخطأ
            error_log("Remember token check error: " . $e->getMessage());
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    return false;
}

/**
 * التحقق من انتهاء الجلسة
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * حماية الصفحة - يتطلب تسجيل الدخول
 */
function requireLogin() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        redirect(SITE_URL . '/auth/login.php');
    }
    
    // إصلاح تلقائي: إذا كان الدور غير موجود في الجلسة، جلبّه من قاعدة البيانات
    if (!isset($_SESSION['role']) && isset($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && isset($user['role'])) {
            $_SESSION['role'] = $user['role'];
        } else {
            // إذا لم يوجد دور، افترض أنه admin
            $_SESSION['role'] = 'admin';
        }
    }
}

/**
 * الحصول على معلومات المستخدم الحالي
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * التحقق من الصلاحيات
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * التحقق من أن المستخدم هو مدير (Admin)
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * التحقق من أن المستخدم هو موظف (Employee)
 */
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

/**
 * حماية الصفحة - يتطلب صلاحيات المدير
 * إذا لم يكن المستخدم مديراً، يتم توجيهه إلى صفحة "لا يوجد صلاحية"
 */
function requireAdmin() {
    requireLogin(); // تأكد من تسجيل الدخول أولاً
    
    if (!isAdmin()) {
        redirect(SITE_URL . '/auth/no_access.php');
    }
}

/**
 * توليد رمز CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من رمز CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

