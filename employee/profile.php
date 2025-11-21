<?php
/**
 * Employee Management System
 * صفحة الملف الشخصي للموظف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// توجيه المدير إلى لوحة التحكم
if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

// الحصول على معلومات الموظف
$employee = getEmployeeByUserId($_SESSION['user_id']);

if (!$employee) {
    // إذا لم يكن الموظف مرتبطاً بـ user_id، عرض رسالة
    $page_title = 'الملف الشخصي';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="dashboard">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">الملف الشخصي</h3>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-user-slash" style="font-size: 64px; color: #999; margin-bottom: 20px;"></i>
                <h3 style="color: #666; margin-bottom: 15px;">لم يتم العثور على ملفك الشخصي</h3>
                <p style="color: #999; line-height: 1.8;">
                    يبدو أن حسابك غير مرتبط بملف موظف في النظام.<br>
                    يرجى التواصل مع المدير لإضافة ملفك الشخصي.
                </p>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$page_title = 'الملف الشخصي';
$additional_css = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.employee-profile {
    padding: 0;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 30px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: white;
}

.profile-info h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.profile-info .position {
    margin: 0 0 5px 0;
    font-size: 18px;
    opacity: 0.9;
}

.profile-info .department {
    margin: 0;
    font-size: 14px;
    opacity: 0.8;
}

.profile-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.profile-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.profile-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
}

.info-grid {
    display: grid;
    gap: 20px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border-right: 4px solid #667eea;
}

.info-item label {
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.info-item span {
    color: #2c3e50;
    font-size: 15px;
    font-weight: 500;
}


/* تحسينات للهاتف */
@media (max-width: 768px) {
    .employee-profile {
        padding: 0;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 25px 15px;
        margin-bottom: 20px;
        border-radius: 10px;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        margin-bottom: 15px;
    }
    
    .profile-info h1 {
        font-size: 24px;
        margin-bottom: 8px;
    }
    
    .profile-info .position {
        font-size: 16px;
    }
    
    .profile-info .department {
        font-size: 13px;
    }
    
    .profile-content {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 0 10px;
    }
    
    .profile-card {
        padding: 20px 15px;
        border-radius: 12px;
    }
    
    .profile-card h3 {
        font-size: 18px;
        margin-bottom: 15px;
    }
    
    .info-grid {
        gap: 15px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 12px;
        border-radius: 8px;
    }
    
    .info-item label {
        font-size: 13px;
    }
    
    .info-item span {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .profile-header {
        padding: 20px 12px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-info h1 {
        font-size: 20px;
    }
    
    .profile-card {
        padding: 15px 12px;
    }
    
    .profile-card h3 {
        font-size: 16px;
    }
    
    .info-item {
        padding: 10px;
    }
    
    .info-item label,
    .info-item span {
        font-size: 13px;
    }
}
</style>

<div class="employee-profile">
    <!-- رأس الملف الشخصي -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php if ($employee['photo']): ?>
                <img src="<?php echo UPLOAD_URL . $employee['photo']; ?>" alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
            <p class="position"><?php echo htmlspecialchars($employee['position']); ?></p>
            <p class="department">
                <i class="fas fa-building"></i>
                <?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?>
            </p>
        </div>
    </div>

    <!-- محتوى الملف الشخصي -->
    <div class="profile-content">
        <!-- المعلومات الشخصية -->
        <div class="profile-card">
            <h3><i class="fas fa-user-circle"></i> المعلومات الشخصية</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>الرمز الوظيفي:</label>
                    <span><?php echo htmlspecialchars($employee['employee_code']); ?></span>
                </div>
                <div class="info-item">
                    <label>البريد الإلكتروني:</label>
                    <span><?php echo htmlspecialchars($employee['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>رقم الهاتف:</label>
                    <span><?php echo htmlspecialchars($employee['phone'] ?? 'غير محدد'); ?></span>
                </div>
                <div class="info-item">
                    <label>العنوان:</label>
                    <span><?php echo htmlspecialchars($employee['address'] ?? 'غير محدد'); ?></span>
                </div>
            </div>
        </div>

        <!-- المعلومات الوظيفية -->
        <div class="profile-card">
            <h3><i class="fas fa-briefcase"></i> المعلومات الوظيفية</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>تاريخ التوظيف:</label>
                    <span><?php echo formatDate($employee['hire_date']); ?></span>
                </div>
                <div class="info-item">
                    <label>الراتب:</label>
                    <span><?php echo formatCurrency($employee['salary']); ?></span>
                </div>
                <div class="info-item">
                    <label>الحالة:</label>
                    <span style="color: #27ae60; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> نشط
                    </span>
                </div>
                <div class="info-item">
                    <label>القسم:</label>
                    <span><?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

