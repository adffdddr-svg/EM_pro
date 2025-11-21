<?php
/**
 * Employee Management System
 * عرض تفاصيل الراتب
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'تفاصيل الراتب';
$db = getDB();

// التحقق من وجود جدول salary_history وإنشاؤه إذا لم يكن موجوداً
try {
    $db->query("SELECT 1 FROM salary_history LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS salary_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            old_salary DECIMAL(10, 2) NULL,
            new_salary DECIMAL(10, 2) NOT NULL,
            change_type ENUM('increase', 'decrease', 'initial', 'adjustment') DEFAULT 'adjustment',
            change_amount DECIMAL(10, 2) NULL,
            change_percentage DECIMAL(5, 2) NULL,
            effective_date DATE NOT NULL,
            reason TEXT,
            notes TEXT,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_employee_id (employee_id),
            INDEX idx_effective_date (effective_date),
            INDEX idx_change_type (change_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $create_error) {
        // تجاهل الخطأ، سيتم التعامل معه لاحقاً
    }
}

// الحصول على معرف الموظف
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/salaries/index.php');
}

// جلب بيانات الموظف
$stmt = $db->prepare("SELECT e.*, d.name as department_name 
                     FROM employees e 
                     LEFT JOIN departments d ON e.department_id = d.id 
                     WHERE e.id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect(SITE_URL . '/admin/salaries/index.php');
}

// جلب سجل الرواتب
$history = getSalaryHistory($employee['id'], 10);

// جلب آخر راتب
$last_salary = getLastSalary($employee['id']);

include __DIR__ . '/../../includes/header.php';
?>

<style>
.salary-view-container {
    padding: 30px;
}

.employee-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.employee-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.current-salary {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    margin: 20px 0;
}

.current-salary .label {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.current-salary .value {
    font-size: 48px;
    font-weight: bold;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.info-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-item .label {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.info-item .value {
    font-size: 18px;
    font-weight: bold;
    color: var(--primary-color);
}

.history-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.history-item {
    padding: 20px;
    border-right: 4px solid #e0e0e0;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s;
}

.history-item:hover {
    background: #e9ecef;
    transform: translateX(-5px);
}

.history-item.increase {
    border-right-color: #28a745;
}

.history-item.decrease {
    border-right-color: #dc3545;
}

.history-item.initial {
    border-right-color: #17a2b8;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.history-date {
    color: #666;
    font-size: 14px;
}

.history-amount {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary-color);
}

.history-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

@media (max-width: 768px) {
    .salary-view-container {
        padding: 15px;
    }
    
    .employee-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="salary-view-container">
    <div class="employee-card">
        <div class="employee-header">
            <div>
                <h1 style="margin: 0; color: var(--primary-color);">
                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                </h1>
                <p style="color: #666; margin-top: 5px;">
                    <?php echo htmlspecialchars($employee['employee_code']); ?> - 
                    <?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="add.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-success">تعديل الراتب</a>
                <a href="history.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-info">السجل الكامل</a>
                <a href="index.php" class="btn btn-secondary">العودة</a>
            </div>
        </div>
        
        <div class="current-salary">
            <div class="label">الراتب الحالي</div>
            <div class="value"><?php echo number_format($employee['salary'], 2); ?> د.ع</div>
        </div>
        
        <?php if ($last_salary): ?>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">نوع آخر تغيير</div>
                    <div class="value"><?php echo getSalaryChangeTypeText($last_salary['change_type']); ?></div>
                </div>
                <div class="info-item">
                    <div class="label">تاريخ آخر تغيير</div>
                    <div class="value"><?php echo formatDate($last_salary['effective_date']); ?></div>
                </div>
                <?php if ($last_salary['change_amount'] > 0): ?>
                    <div class="info-item">
                        <div class="label">مبلغ التغيير</div>
                        <div class="value"><?php echo number_format($last_salary['change_amount'], 2); ?> د.ع</div>
                    </div>
                    <div class="info-item">
                        <div class="label">نسبة التغيير</div>
                        <div class="value"><?php echo number_format($last_salary['change_percentage'], 2); ?>%</div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- سجل الرواتب -->
    <div class="history-section">
        <h2 style="margin-bottom: 20px; color: var(--primary-color);">📋 سجل الرواتب (آخر 10 تغييرات)</h2>
        
        <?php if (empty($history)): ?>
            <p style="text-align: center; color: #999; padding: 40px;">
                لا يوجد سجل رواتب لهذا الموظف
            </p>
        <?php else: ?>
            <?php foreach ($history as $record): ?>
                <div class="history-item <?php echo $record['change_type']; ?>">
                    <div class="history-header">
                        <div>
                            <div class="history-date">📅 <?php echo formatDate($record['effective_date']); ?></div>
                            <div style="color: #666; font-size: 14px; margin-top: 5px;">
                                <?php echo getSalaryChangeTypeText($record['change_type']); ?>
                                <?php if ($record['created_by_name']): ?>
                                    - بواسطة: <?php echo htmlspecialchars($record['created_by_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="history-amount">
                            <?php echo number_format($record['new_salary'], 2); ?> د.ع
                        </div>
                    </div>
                    
                    <?php if ($record['old_salary']): ?>
                        <div class="history-details">
                            <div>
                                <strong>الراتب السابق:</strong><br>
                                <?php echo number_format($record['old_salary'], 2); ?> د.ع
                            </div>
                            <?php if ($record['change_amount'] > 0): ?>
                                <div>
                                    <strong>مبلغ التغيير:</strong><br>
                                    <span style="color: <?php echo $record['change_type'] == 'increase' ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo ($record['change_type'] == 'increase' ? '+' : '-'); ?>
                                        <?php echo number_format($record['change_amount'], 2); ?> د.ع
                                    </span>
                                </div>
                                <div>
                                    <strong>نسبة التغيير:</strong><br>
                                    <?php echo number_format($record['change_percentage'], 2); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($record['reason']): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0;">
                            <strong>السبب:</strong> <?php echo htmlspecialchars($record['reason']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($record['notes']): ?>
                        <div style="margin-top: 10px; color: #666; font-size: 14px;">
                            <strong>ملاحظات:</strong> <?php echo htmlspecialchars($record['notes']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="history.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                    عرض السجل الكامل
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

