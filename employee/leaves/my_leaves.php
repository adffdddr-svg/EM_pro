<?php
/**
 * Employee Management System
 * إجازاتي
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

// توجيه المدير
if (isAdmin()) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على معلومات الموظف
$employee = getEmployeeByUserId($_SESSION['user_id']);

if (!$employee) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();
$employee_id = $employee['id'];

// الحصول على الإجازات
$stmt = $db->prepare("SELECT l.*, 
                             se.first_name as substitute_first_name, se.last_name as substitute_last_name
                      FROM employee_leaves l 
                      LEFT JOIN employees se ON l.substitute_employee_id = se.id
                      WHERE l.employee_id = ? 
                      ORDER BY l.created_at DESC");
$stmt->execute([$employee_id]);
$leaves = $stmt->fetchAll();

// الحصول على رصيد الإجازات
$balance = getLeaveBalance($employee_id);

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

$page_title = 'إجازاتي';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">إجازاتي</h3>
            <a href="<?php echo SITE_URL; ?>/employee/leaves/request.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> طلب إجازة جديدة
            </a>
        </div>

        <!-- رصيد الإجازات -->
        <?php if ($balance): ?>
            <div class="balance-card">
                <h4>رصيد الإجازات</h4>
                <div class="balance-grid">
                    <div class="balance-item">
                        <div class="balance-label">الرصيد الكلي</div>
                        <div class="balance-value"><?php echo $balance['total_balance']; ?> يوم</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">الرصيد الشهري</div>
                        <div class="balance-value"><?php echo $balance['monthly_balance']; ?> يوم</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">الرصيد المتبقي</div>
                        <div class="balance-value highlight"><?php echo $balance['remaining_balance']; ?> يوم</div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">المستخدم هذا العام</div>
                        <div class="balance-value"><?php echo $balance['used_this_year']; ?> يوم</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- قائمة الإجازات -->
        <div class="leaves-list">
            <h4>سجل الإجازات</h4>
            <?php if (count($leaves) > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نوع الإجازة</th>
                                <th>من تاريخ</th>
                                <th>إلى تاريخ</th>
                                <th>عدد الأيام</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $index => $leave): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $leave_types[$leave['leave_type']] ?? $leave['leave_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo formatDate($leave['start_date']); ?>
                                        <?php if ($leave['start_time']): ?>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($leave['start_time'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatDate($leave['end_date']); ?>
                                        <?php if ($leave['end_time']): ?>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($leave['end_time'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $leave['days']; ?></strong> يوم</td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_colors[$leave['status']]; ?>">
                                            <?php echo $status_labels[$leave['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/leaves/view.php?id=<?php echo $leave['id']; ?>" 
                                           class="btn btn-sm btn-info" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="<?php echo SITE_URL; ?>/employee/leaves/cancel.php?id=<?php echo $leave['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('هل أنت متأكد من إلغاء هذه الإجازة؟');" 
                                               title="إلغاء">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>لا توجد إجازات مسجلة</p>
                    <a href="<?php echo SITE_URL; ?>/employee/leaves/request.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> طلب إجازة جديدة
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.balance-card {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
}

.balance-card h4 {
    margin: 0 0 20px 0;
    color: white;
}

.balance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.balance-item {
    background: rgba(255, 255, 255, 0.2);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.balance-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.balance-value {
    font-size: 28px;
    font-weight: 700;
}

.balance-value.highlight {
    color: #ffd700;
}

.leaves-list {
    margin-top: 30px;
}

.leaves-list h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ddd;
}

.empty-state p {
    font-size: 18px;
    margin-bottom: 20px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
