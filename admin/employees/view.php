<?php
/**
 * Employee Management System
 * عرض تفاصيل موظف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

// الحصول على معرف الموظف
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/employees/index.php');
}

// الحصول على بيانات الموظف
$stmt = $db->prepare("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect(SITE_URL . '/admin/employees/index.php');
}

$page_title = 'تفاصيل الموظف';
include __DIR__ . '/../../includes/header.php';
?>

<div class="employee-view">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">تفاصيل الموظف</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo SITE_URL; ?>/admin/records/index.php?employee=<?php echo $employee['id']; ?>" class="btn btn-info">📁 السجلات</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-success">تعديل</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php?archive_id=<?php echo $employee['id']; ?>" 
                   class="btn btn-warning" 
                   onclick="return confirm('هل أنت متأكد من أرشفة هذا الموظف؟');">أرشفة</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/delete.php?id=<?php echo $employee['id']; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('هل أنت متأكد من حذف هذا الموظف؟ هذا الإجراء لا يمكن التراجع عنه.');">حذف</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
            </div>
        </div>

        <div class="employee-card">
            <div class="employee-photo-container">
                <?php if ($employee['photo']): ?>
                    <img src="<?php echo UPLOAD_URL . $employee['photo']; ?>" 
                         alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>" 
                         class="employee-photo">
                <?php else: ?>
                    <div class="employee-photo" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 18px;">
                        بدون صورة
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="employee-info">
                <h2><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                <p style="color: #666; margin-bottom: 30px;"><?php echo htmlspecialchars($employee['employee_code']); ?></p>
                
                <div class="info-section">
                    <h3>المعلومات الشخصية</h3>
                    <div class="info-row">
                        <span class="info-label">الاسم الكامل:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">البريد الإلكتروني:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهاتف:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['phone'] ?? 'غير محدد'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">العنوان:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['address'] ?? 'غير محدد'); ?></span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>المعلومات الوظيفية</h3>
                    <div class="info-row">
                        <span class="info-label">الرمز الوظيفي:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['employee_code']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">القسم:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المسمى الوظيفي:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['position']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الراتب:</span>
                        <span class="info-value"><?php echo formatCurrency($employee['salary']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ التوظيف:</span>
                        <span class="info-value"><?php echo formatDate($employee['hire_date']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الحالة:</span>
                        <span class="info-value">
                            <?php if ($employee['status'] == 'active'): ?>
                                <span style="color: var(--success-color); font-weight: bold;">نشط</span>
                            <?php else: ?>
                                <span style="color: var(--warning-color); font-weight: bold;">غير نشط</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>معلومات النظام</h3>
                    <div class="info-row">
                        <span class="info-label">تاريخ الإضافة:</span>
                        <span class="info-value"><?php echo formatDate($employee['created_at'], 'Y-m-d H:i:s'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">آخر تحديث:</span>
                        <span class="info-value"><?php echo formatDate($employee['updated_at'], 'Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.employee-view .employee-card {
    display: flex;
    gap: 40px;
    padding: 30px 0;
}

.employee-photo-container {
    flex-shrink: 0;
}

.employee-info {
    flex: 1;
}

.employee-info h2 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.info-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--bg-color);
}

.info-section:last-child {
    border-bottom: none;
}

.info-section h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
    font-size: 18px;
}

.info-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    padding: 12px 0;
    gap: 20px;
}

.info-label {
    font-weight: 600;
    color: var(--text-color);
}

.info-value {
    color: #666;
}

@media (max-width: 768px) {
    .employee-view .employee-card {
        flex-direction: column;
    }
    
    .employee-photo {
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
    }
    
    .info-row {
        grid-template-columns: 1fr;
        gap: 5px;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

