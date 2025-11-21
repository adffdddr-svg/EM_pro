<?php
/**
 * Employee Management System
 * عرض تفاصيل الإجازة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();

// الحصول على معرف الإجازة
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على بيانات الإجازة
$stmt = $db->prepare("SELECT l.*, 
                             e.first_name, e.last_name, e.employee_code, e.position, e.email, e.phone,
                             d.name as department_name,
                             u.username as approved_by_name,
                             se.first_name as substitute_first_name, se.last_name as substitute_last_name, se.employee_code as substitute_code
                      FROM employee_leaves l 
                      JOIN employees e ON l.employee_id = e.id 
                      LEFT JOIN departments d ON e.department_id = d.id
                      LEFT JOIN users u ON l.approved_by = u.id
                      LEFT JOIN employees se ON l.substitute_employee_id = se.id
                      WHERE l.id = ?");
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على الموافقات
$stmt = $db->prepare("SELECT * FROM leave_approvals WHERE leave_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$approvals = $stmt->fetchAll();

// الحصول على رصيد الموظف
$balance = getLeaveBalance($leave['employee_id']);

$leave_types = getLeaveTypes();
$status_labels = [
    'pending' => 'قيد الانتظار',
    'approved' => 'موافق عليها',
    'rejected' => 'مرفوضة',
    'cancelled' => 'ملغاة'
];
$status_colors = [
    'pending' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'cancelled' => 'secondary'
];

$approver_types = [
    'leave_unit' => 'مسؤول وحدة الإجازات',
    'direct_supervisor' => 'المسؤول المباشر',
    'assistant_dean' => 'معاون العميد الإداري'
];

$page_title = 'تفاصيل الإجازة';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">تفاصيل الإجازة</h3>
            <div class="action-buttons">
                <a href="<?php echo SITE_URL; ?>/admin/leaves/index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> العودة للقائمة
                </a>
                <?php if ($leave['status'] === 'pending'): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/leaves/approve.php?id=<?php echo $leave['id']; ?>&action=approve" 
                       class="btn btn-success">
                        <i class="fas fa-check"></i> موافقة
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/leaves/approve.php?id=<?php echo $leave['id']; ?>&action=reject" 
                       class="btn btn-danger">
                        <i class="fas fa-times"></i> رفض
                    </a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/admin/leaves/print.php?id=<?php echo $leave['id']; ?>" 
                   class="btn btn-info" target="_blank">
                    <i class="fas fa-print"></i> طباعة
                </a>
            </div>
        </div>

        <div class="leave-details">
            <!-- معلومات الإجازة -->
            <div class="info-section">
                <h4>معلومات الإجازة</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>رقم الإجازة:</label>
                        <span>#<?php echo $leave['id']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>نوع الإجازة:</label>
                        <span class="badge badge-info">
                            <?php echo $leave_types[$leave['leave_type']] ?? $leave['leave_type']; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>الحالة:</label>
                        <span class="badge badge-<?php echo $status_colors[$leave['status']]; ?>">
                            <?php echo $status_labels[$leave['status']]; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>من تاريخ:</label>
                        <span>
                            <?php echo formatDate($leave['start_date']); ?>
                            <?php if ($leave['start_time']): ?>
                                <small class="text-muted"><?php echo date('H:i', strtotime($leave['start_time'])); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>إلى تاريخ:</label>
                        <span>
                            <?php echo formatDate($leave['end_date']); ?>
                            <?php if ($leave['end_time']): ?>
                                <small class="text-muted"><?php echo date('H:i', strtotime($leave['end_time'])); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>عدد الأيام:</label>
                        <span><strong><?php echo $leave['days']; ?></strong> يوم</span>
                    </div>
                    <?php if ($leave['purpose']): ?>
                        <div class="info-item full-width">
                            <label>الغرض من الإجازة:</label>
                            <span><?php echo nl2br(htmlspecialchars($leave['purpose'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- معلومات الموظف -->
            <div class="info-section">
                <h4>معلومات الموظف</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>الاسم:</label>
                        <span><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>الرمز الوظيفي:</label>
                        <span><?php echo htmlspecialchars($leave['employee_code']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>المسمى الوظيفي:</label>
                        <span><?php echo htmlspecialchars($leave['position']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>القسم:</label>
                        <span><?php echo htmlspecialchars($leave['department_name'] ?? 'غير محدد'); ?></span>
                    </div>
                </div>
            </div>

            <!-- الموظف البديل -->
            <?php if ($leave['substitute_first_name']): ?>
                <div class="info-section">
                    <h4>الموظف البديل</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>الاسم:</label>
                            <span><?php echo htmlspecialchars($leave['substitute_first_name'] . ' ' . $leave['substitute_last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>الرمز الوظيفي:</label>
                            <span><?php echo htmlspecialchars($leave['substitute_code']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- رصيد الإجازات -->
            <?php if ($balance): ?>
                <div class="info-section">
                    <h4>رصيد الإجازات</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>الرصيد الكلي:</label>
                            <span><strong><?php echo $balance['total_balance']; ?></strong> يوم</span>
                        </div>
                        <div class="info-item">
                            <label>الرصيد الشهري:</label>
                            <span><strong><?php echo $balance['monthly_balance']; ?></strong> يوم</span>
                        </div>
                        <div class="info-item">
                            <label>الرصيد المتبقي:</label>
                            <span><strong><?php echo $balance['remaining_balance']; ?></strong> يوم</span>
                        </div>
                        <div class="info-item">
                            <label>المستخدم هذا العام:</label>
                            <span><strong><?php echo $balance['used_this_year']; ?></strong> يوم</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- الموافقات -->
            <div class="info-section">
                <h4>سجل الموافقات</h4>
                <div class="approvals-list">
                    <?php foreach ($approvals as $approval): ?>
                        <div class="approval-item">
                            <div class="approval-header">
                                <strong><?php echo $approver_types[$approval['approver_type']] ?? $approval['approver_type']; ?></strong>
                                <span class="badge badge-<?php echo $approval['status'] === 'approved' ? 'success' : ($approval['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php 
                                    echo $approval['status'] === 'approved' ? 'موافق' : 
                                         ($approval['status'] === 'rejected' ? 'مرفوض' : 'قيد الانتظار'); 
                                    ?>
                                </span>
                            </div>
                            <?php if ($approval['approver_name']): ?>
                                <div class="approval-details">
                                    <p><strong>الاسم:</strong> <?php echo htmlspecialchars($approval['approver_name']); ?></p>
                                    <?php if ($approval['approver_position']): ?>
                                        <p><strong>المسمى الوظيفي:</strong> <?php echo htmlspecialchars($approval['approver_position']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($approval['approved_at']): ?>
                                        <p><strong>تاريخ الموافقة:</strong> <?php echo formatDate($approval['approved_at'], 'Y-m-d H:i:s'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($approval['notes']): ?>
                                        <p><strong>ملاحظات:</strong> <?php echo nl2br(htmlspecialchars($approval['notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- معلومات إضافية -->
            <div class="info-section">
                <h4>معلومات إضافية</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>تاريخ الإنشاء:</label>
                        <span><?php echo formatDate($leave['created_at'], 'Y-m-d H:i:s'); ?></span>
                    </div>
                    <?php if ($leave['approved_at']): ?>
                        <div class="info-item">
                            <label>تاريخ الموافقة:</label>
                            <span><?php echo formatDate($leave['approved_at'], 'Y-m-d H:i:s'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($leave['approved_by_name']): ?>
                        <div class="info-item">
                            <label>وافق عليها:</label>
                            <span><?php echo htmlspecialchars($leave['approved_by_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($leave['rejection_reason']): ?>
                        <div class="info-item full-width">
                            <label>سبب الرفض:</label>
                            <span><?php echo nl2br(htmlspecialchars($leave['rejection_reason'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.leave-details {
    padding: 20px 0;
}

.info-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-section h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.info-item span {
    color: #2c3e50;
    font-size: 15px;
}

.approvals-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.approval-item {
    padding: 15px;
    background: white;
    border-radius: 8px;
    border-right: 4px solid #667eea;
}

.approval-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.approval-details p {
    margin: 5px 0;
    color: #555;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
