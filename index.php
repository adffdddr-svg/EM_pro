<?php
/**
 * Employee Management System
 * الصفحة الرئيسية
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// إذا كان المستخدم مسجل دخول، إعادة توجيه إلى لوحة التحكم
if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الموظفين - جامعة البصرة</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .welcome-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .welcome-card {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .welcome-card h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 32px;
        }
        .welcome-card p {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
            line-height: 1.8;
        }
        .welcome-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-large {
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        .network-link {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .network-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 16px;
        }
        .network-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .welcome-card {
                padding: 30px 20px;
            }
            .welcome-card h1 {
                font-size: 24px;
            }
            .welcome-buttons {
                flex-direction: column;
            }
            .btn-large {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-page">
        <div class="welcome-card">
            <h1>🏢 نظام إدارة الموظفين</h1>
            <p>جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات</p>
            
            <p style="color: #999; font-size: 16px; margin-top: 20px;">
                نظام متكامل لإدارة الموظفين، الرواتب، الإجازات، والمزيد
            </p>
            
            <div class="welcome-buttons">
                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn-large btn-primary">
                    🔐 تسجيل الدخول
                </a>
            </div>
        </div>
    </div>
</body>
</html>

