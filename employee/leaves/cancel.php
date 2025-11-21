<?php
/**
 * Employee Management System
 * إلغاء الإجازة
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

// الحصول على معرف الإجازة
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/employee/leaves/my_leaves.php');
}

// الحصول على بيانات الإجازة
$stmt = $db->prepare("SELECT * FROM employee_leaves WHERE id = ? AND employee_id = ?");
$stmt->execute([$id, $employee_id]);
$leave = $stmt->fetch();

if (!$leave) {
    redirect(SITE_URL . '/employee/leaves/my_leaves.php');
}

if ($leave['status'] !== 'pending') {
    $_SESSION['error'] = 'لا يمكن إلغاء هذه الإجازة لأنها ' . ($leave['status'] === 'approved' ? 'موافق عليها' : 'مرفوضة');
    redirect(SITE_URL . '/employee/leaves/my_leaves.php');
}

// معالجة الإلغاء
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        $stmt = $db->prepare("UPDATE employee_leaves SET status = 'cancelled' WHERE id = ? AND employee_id = ?");
        $stmt->execute([$id, $employee_id]);
        
        $_SESSION['success'] = 'تم إلغاء الإجازة بنجاح';
        redirect(SITE_URL . '/employee/leaves/my_leaves.php');
    } catch (PDOException $e) {
        error_log("Error cancelling leave: " . $e->getMessage());
        $_SESSION['error'] = 'حدث خطأ أثناء إلغاء الإجازة';
        redirect(SITE_URL . '/employee/leaves/my_leaves.php');
    }
}

$page_title = 'إلغاء الإجازة';
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">إلغاء الإجازة</h3>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>تحذير:</strong> هل أنت متأكد من إلغاء هذه الإجازة؟
        </div>

        <div class="leave-summary">
            <h4>معلومات الإجازة</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <label>نوع الإجازة:</label>
                    <span><?php echo getLeaveTypes()[$leave['leave_type']] ?? $leave['leave_type']; ?></span>
                </div>
                <div class="summary-item">
                    <label>من تاريخ:</label>
                    <span><?php echo formatDate($leave['start_date']); ?></span>
                </div>
                <div class="summary-item">
                    <label>إلى تاريخ:</label>
                    <span><?php echo formatDate($leave['end_date']); ?></span>
                </div>
                <div class="summary-item">
                    <label>عدد الأيام:</label>
                    <span><strong><?php echo $leave['days']; ?></strong> يوم</span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="?id=<?php echo $id; ?>&confirm=yes" class="btn btn-danger">
                <i class="fas fa-times"></i> نعم، إلغاء الإجازة
            </a>
            <a href="<?php echo SITE_URL; ?>/employee/leaves/my_leaves.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> إلغاء
            </a>
        </div>
    </div>
</div>

<style>
.leave-summary {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.leave-summary h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.summary-item label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.summary-item span {
    color: #2c3e50;
    font-size: 15px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

