<?php
/**
 * Employee Management System
 * سجل الرواتب الكامل لموظف
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'سجل الرواتب';
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
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if ($employee_id <= 0) {
    redirect(SITE_URL . '/admin/salaries/index.php');
}

// جلب بيانات الموظف
$stmt = $db->prepare("SELECT e.*, d.name as department_name 
                     FROM employees e 
                     LEFT JOIN departments d ON e.department_id = d.id 
                     WHERE e.id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect(SITE_URL . '/admin/salaries/index.php');
}

// جلب سجل الرواتب الكامل
$history = getSalaryHistory($employee_id);

include __DIR__ . '/../../includes/header.php';
?>

<style>
.salary-history-container {
    padding: 30px;
}

.employee-info-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.history-timeline {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.timeline-item {
    position: relative;
    padding-right: 40px;
    padding-bottom: 30px;
    border-right: 3px solid #e0e0e0;
}

.timeline-item:last-child {
    border-right: none;
}

.timeline-item::before {
    content: '';
    position: absolute;
    right: -8px;
    top: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #e0e0e0;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #e0e0e0;
}

.timeline-item.increase::before {
    background: #28a745;
    box-shadow: 0 0 0 3px #28a745;
}

.timeline-item.decrease::before {
    background: #dc3545;
    box-shadow: 0 0 0 3px #dc3545;
}

.timeline-item.initial::before {
    background: #17a2b8;
    box-shadow: 0 0 0 3px #17a2b8;
}

.timeline-content {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 10px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.timeline-date {
    color: #666;
    font-size: 14px;
}

.timeline-amount {
    font-size: 28px;
    font-weight: bold;
    color: var(--primary-color);
}

.timeline-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .salary-history-container {
        padding: 15px;
    }
    
    .timeline-item {
        padding-right: 30px;
    }
}
</style>

<div class="salary-history-container">
    <div class="employee-info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
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
                <a href="view.php?id=<?php echo $employee['id']; ?>" class="btn btn-info">عرض التفاصيل</a>
                <a href="add.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-success">إضافة راتب</a>
                <a href="index.php" class="btn btn-secondary">العودة</a>
            </div>
        </div>
    </div>
    
    <div class="history-timeline">
        <h2 style="margin-bottom: 30px; color: var(--primary-color);">
            📋 سجل الرواتب الكامل (<?php echo count($history); ?> سجل)
        </h2>
        
        <?php if (empty($history)): ?>
            <p style="text-align: center; color: #999; padding: 40px;">
                لا يوجد سجل رواتب لهذا الموظف
            </p>
        <?php else: ?>
            <?php foreach ($history as $record): ?>
                <div class="timeline-item <?php echo $record['change_type']; ?>">
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <div>
                                <div class="timeline-date">
                                    📅 <?php echo formatDate($record['effective_date']); ?>
                                    <?php if ($record['created_at']): ?>
                                        - <?php echo date('H:i', strtotime($record['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="color: #666; font-size: 14px; margin-top: 5px;">
                                    <?php echo getSalaryChangeTypeText($record['change_type']); ?>
                                    <?php if ($record['created_by_name']): ?>
                                        - بواسطة: <?php echo htmlspecialchars($record['created_by_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="timeline-amount">
                                <?php echo number_format($record['new_salary'], 2); ?> د.ع
                            </div>
                        </div>
                        
                        <?php if ($record['old_salary']): ?>
                            <div class="timeline-details">
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
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <strong>السبب:</strong> <?php echo htmlspecialchars($record['reason']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($record['notes']): ?>
                            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                                <strong>ملاحظات:</strong> <?php echo htmlspecialchars($record['notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

