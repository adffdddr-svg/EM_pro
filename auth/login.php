<?php
/**
 * Employee Management System
 * صفحة تسجيل الدخول - تصميم قوي ومطور
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if (isLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
        if (login($username, $password, $remember_me)) {
            $_SESSION['just_logged_in'] = true;
            header('Location: ' . SITE_URL . '/auth/welcome.php');
            exit();
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>تسجيل الدخول - نظام إدارة الموظفين</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            position: relative;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Animated Background */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }
        
        .bg-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .bg-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 50%;
            right: -50px;
            animation-delay: 5s;
        }
        
        .bg-circle:nth-child(3) {
            width: 250px;
            height: 250px;
            bottom: -50px;
            left: 20%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -30px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }
        
        .login-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            animation: slideInUp 0.8s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            border-radius: 20px;
            padding: 30px 25px 25px 25px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.6);
            position: relative;
            overflow: visible;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 4s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 0 0 0 6px rgba(102, 126, 234, 0.1);
            animation: pulse 2s infinite, rotate 20s linear infinite;
            position: relative;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .login-logo::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: white;
            border-right-color: white;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-logo i {
            font-size: 35px;
            color: white;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            letter-spacing: -1px;
        }
        
        .login-header p {
            color: #666;
            font-size: 12px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 15px;
            position: relative;
            display: block;
            visibility: visible;
            opacity: 1;
            width: 100%;
            clear: both;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
            width: 100%;
            text-align: right;
        }
        
        .form-group label i {
            margin-left: 8px;
            color: #667eea;
        }
        
        .input-wrapper {
            position: relative;
            width: 100%;
            display: block;
            clear: both;
        }
        
        .input-wrapper i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 10;
            pointer-events: none;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Cairo', sans-serif;
            color: #333;
            font-weight: 500;
            display: block;
            visibility: visible;
            opacity: 1;
            position: relative;
            z-index: 1;
            box-sizing: border-box;
            min-height: 45px;
            line-height: 1.5;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 5px rgba(102, 126, 234, 0.15), 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }
        
        .form-group input:focus ~ i {
            color: #764ba2;
            transform: translateY(-50%) scale(1.2) rotate(5deg);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 200% 200%;
            animation: gradientMove 3s ease infinite;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 0 5px 15px rgba(0,0,0,0.2);
            font-family: 'Cairo', sans-serif;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            margin-bottom: 0;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 400px;
            height: 400px;
        }
        
        .btn-login:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.6);
        }
        
        .btn-login:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-login.loading {
            pointer-events: none;
        }
        
        .btn-login .btn-text,
        .btn-login .btn-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login .btn-text {
            display: flex;
            visibility: visible;
            opacity: 1;
        }
        
        .btn-login .btn-loader {
            display: none;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        .btn-login.loading .btn-loader {
            display: flex;
        }
        
        .btn-login i {
            margin-left: 10px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            animation: shake 0.5s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-error {
            background: #fee;
            border: 2px solid #fcc;
            color: #c0392b;
        }
        
        .alert-error i {
            font-size: 18px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 11px;
        }
        
        .login-footer i {
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .login-wrapper {
                padding: 10px;
                align-items: flex-start;
                padding-top: 20px;
            }
            
            .login-box {
                padding: 25px 20px 20px 20px;
                border-radius: 15px;
            }
            
            .login-header {
                margin-bottom: 15px;
            }
            
            .login-header h1 {
                font-size: 20px;
            }
            
            .login-header p {
                font-size: 11px;
            }
            
            .login-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 12px;
            }
            
            .login-logo i {
                font-size: 30px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-group label {
                font-size: 12px;
                margin-bottom: 6px;
            }
            
            .form-group input {
                padding: 10px 40px 10px 12px;
                font-size: 13px;
                min-height: 42px;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 14px;
                min-height: 48px;
                margin-top: 12px;
            }
            
            .alert {
                padding: 10px 12px;
                font-size: 12px;
                margin-bottom: 15px;
            }
            
            .login-footer {
                margin-top: 12px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>
    
    <div class="login-wrapper">
        <div class="login-container" data-aos="fade-up" data-aos-duration="800">
            <div class="login-box">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h1>نظام إدارة الموظفين</h1>
                    <p>جامعة البصرة - كلية علوم الحاسوب</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" data-aos="fade-down" data-aos-duration="500">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group" data-aos="fade-right" data-aos-delay="200">
                        <label for="username">
                            <i class="fas fa-user"></i> اسم المستخدم
                        </label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" 
                                   placeholder="أدخل اسم المستخدم" required autofocus>
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px !important; margin-bottom: 10px !important; display: block !important; width: 100% !important; visibility: visible !important; opacity: 1 !important;">
                        <label for="password" style="display: block !important; margin-bottom: 8px !important; visibility: visible !important; opacity: 1 !important;">
                            <i class="fas fa-lock"></i> كلمة المرور
                        </label>
                        <div class="input-wrapper" style="display: block !important; width: 100% !important; position: relative !important; visibility: visible !important; opacity: 1 !important;">
                            <input type="password" id="password" name="password" 
                                   placeholder="أدخل كلمة المرور" required
                                   style="width: 100% !important; padding: 12px 45px 12px 15px !important; border: 2px solid #e0e0e0 !important; border-radius: 10px !important; font-size: 14px !important; background: #f8f9fa !important; min-height: 45px !important; box-sizing: border-box !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; z-index: 1 !important;">
                            <i class="fas fa-lock" style="position: absolute !important; right: 15px !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 10 !important; pointer-events: none !important; font-size: 16px !important;"></i>
                        </div>
                    </div>
                    
                    <!-- Remember Me Checkbox -->
                    <div class="form-group" style="margin-top: 12px !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 8px !important; visibility: visible !important; opacity: 1 !important;">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1" style="width: 16px !important; height: 16px !important; cursor: pointer !important; margin: 0 !important;">
                        <label for="remember_me" style="margin: 0 !important; font-size: 12px !important; cursor: pointer !important; color: #555 !important; font-weight: 500 !important; display: flex !important; align-items: center !important; gap: 5px !important;">
                            <i class="fas fa-check-circle" style="color: #667eea; font-size: 14px;"></i>
                            تذكرني (تسجيل دخول تلقائي لمدة 30 يوم)
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn" style="width: 100% !important; padding: 14px !important; margin-top: 12px !important; margin-bottom: 0 !important; display: flex !important; visibility: visible !important; opacity: 1 !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%) !important; color: white !important; border: none !important; border-radius: 10px !important; font-size: 16px !important; font-weight: 700 !important; cursor: pointer !important; min-height: 50px !important; align-items: center !important; justify-content: center !important; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5) !important; position: relative !important; z-index: 1 !important;">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt"></i>
                            تسجيل الدخول
                        </span>
                        <span class="btn-loader">
                            <i class="fas fa-spinner fa-spin"></i>
                            جاري تسجيل الدخول...
                        </span>
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        البيانات الافتراضية: admin / admin123
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Force reload to prevent cache
        if (performance.navigation.type === 1) {
            console.log('Page reloaded');
        }
        
        // Initialize AOS but disable for password and button
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true,
            disable: function() {
                // Disable AOS for password field and login button
                return false;
            }
        });
        
        // Disable AOS animations for password and button after init
        setTimeout(function() {
            const passwordGroup = document.querySelector('#password')?.closest('.form-group');
            const loginBtn = document.getElementById('loginBtn');
            
            if (passwordGroup) {
                passwordGroup.removeAttribute('data-aos');
                passwordGroup.removeAttribute('data-aos-delay');
            }
            
            if (loginBtn) {
                loginBtn.removeAttribute('data-aos');
                loginBtn.removeAttribute('data-aos-delay');
            }
        }, 100);
        
        // Force all elements to be visible immediately
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const passwordGroup = passwordField ? passwordField.closest('.form-group') : null;
            
            // Force password field visibility
            if (passwordField) {
                passwordField.style.cssText = 'width: 100% !important; padding: 16px 50px 16px 18px !important; border: 3px solid #e0e0e0 !important; border-radius: 12px !important; font-size: 16px !important; background: #f8f9fa !important; min-height: 50px !important; box-sizing: border-box !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; z-index: 1 !important;';
            }
            
            // Force password group visibility
            if (passwordGroup) {
                passwordGroup.style.cssText = 'margin-top: 18px !important; margin-bottom: 12px !important; display: block !important; width: 100% !important; visibility: visible !important; opacity: 1 !important; position: relative !important;';
            }
            
            // Force login button visibility
            if (loginBtn) {
                loginBtn.style.cssText = 'width: 100% !important; padding: 18px !important; margin-top: 18px !important; margin-bottom: 0 !important; display: flex !important; visibility: visible !important; opacity: 1 !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%) !important; color: white !important; border: none !important; border-radius: 12px !important; font-size: 18px !important; font-weight: 700 !important; cursor: pointer !important; min-height: 55px !important; align-items: center !important; justify-content: center !important; box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5) !important; position: relative !important; z-index: 1 !important;';
                
                // Ensure button text is visible
                const btnText = loginBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.style.cssText = 'display: flex !important; align-items: center !important; gap: 10px !important; visibility: visible !important; opacity: 1 !important;';
                }
            }
            
            console.log('Password field:', passwordField ? 'Found and visible' : 'Not found');
            console.log('Login button:', loginBtn ? 'Found and visible' : 'Not found');
        });
        
        // Also run on window load as backup
        window.addEventListener('load', function() {
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            if (passwordField) {
                passwordField.style.display = 'block';
                passwordField.style.visibility = 'visible';
                passwordField.style.opacity = '1';
            }
            
            if (loginBtn) {
                loginBtn.style.display = 'flex';
                loginBtn.style.visibility = 'visible';
                loginBtn.style.opacity = '1';
            }
        });
        
        // Add focus animation to inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Prevent double submission
        const loginForm = document.querySelector('form');
        const loginBtn = document.getElementById('loginBtn');
        let isSubmitting = false;
        
        loginForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            
            // Allow form to submit normally
            return true;
        });
        
        // Handle Enter key
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !isSubmitting) {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    e.preventDefault();
                    loginForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    </script>
</body>
</html>
