<?php
/**
 * Employee Management System
 * صفحة عدم الصلاحية
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// إذا لم يكن المستخدم مسجل دخول، توجهه إلى صفحة تسجيل الدخول
if (!isLoggedIn()) {
    redirect(SITE_URL . '/auth/login.php');
}

$page_title = 'لا يوجد صلاحية';
include __DIR__ . '/../includes/header.php';
?>

<div class="no-access-container">
    <div class="no-access-card">
        <div class="no-access-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1 class="no-access-title">لا يوجد صلاحية</h1>
        <p class="no-access-message">
            عذراً، ليس لديك صلاحية للوصول إلى هذه الصفحة.
            <br>
            هذه الصفحة متاحة فقط للمديرين.
        </p>
        <div class="no-access-info">
            <p><strong>دورك الحالي:</strong> 
                <?php 
                $role = $_SESSION['role'] ?? 'غير محدد';
                echo $role === 'admin' ? 'مدير' : ($role === 'employee' ? 'موظف' : 'غير محدد');
                ?>
            </p>
            <p><strong>اسم المستخدم:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'غير محدد'); ?></p>
        </div>
        <div class="no-access-actions">
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                العودة إلى لوحة التحكم
            </a>
            <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i>
                تسجيل الخروج
            </a>
        </div>
    </div>
</div>

<style>
.no-access-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.no-access-card {
    background: white;
    border-radius: 20px;
    padding: 50px 40px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    text-align: center;
    animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.no-access-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(231, 76, 60, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.no-access-icon i {
    font-size: 60px;
    color: white;
}

.no-access-title {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.no-access-message {
    font-size: 18px;
    color: #666;
    line-height: 1.8;
    margin-bottom: 30px;
}

.no-access-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: right;
    border: 2px solid #e9ecef;
}

.no-access-info p {
    margin: 10px 0;
    color: #555;
    font-size: 15px;
}

.no-access-info strong {
    color: #2c3e50;
    margin-left: 10px;
}

.no-access-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.no-access-actions .btn {
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.no-access-actions .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.no-access-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.no-access-actions .btn-secondary {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.no-access-actions .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

@media (max-width: 768px) {
    .no-access-card {
        padding: 40px 30px;
    }
    
    .no-access-title {
        font-size: 26px;
    }
    
    .no-access-message {
        font-size: 16px;
    }
    
    .no-access-actions {
        flex-direction: column;
    }
    
    .no-access-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

