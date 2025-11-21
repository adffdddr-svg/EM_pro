<?php
/**
 * Employee Management System
 * موافقة/رفض الإجازة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$error = '';
$success = '';

// الحصول على معرف الإجازة والإجراء
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = cleanInput($_GET['action'] ?? '');

if ($id <= 0 || !in_array($action, ['approve', 'reject'])) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

// الحصول على بيانات الإجازة
$stmt = $db->prepare("SELECT * FROM employee_leaves WHERE id = ?");
$stmt->execute([$id]);
$leave = $stmt->fetch();

if (!$leave) {
    redirect(SITE_URL . '/admin/leaves/index.php');
}

if ($leave['status'] !== 'pending') {
    $error = 'هذه الإجازة تمت معالجتها مسبقاً';
}

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $rejection_reason = cleanInput($_POST['rejection_reason'] ?? '');
    
    try {
        $db->beginTransaction();
        
        if ($action === 'approve') {
            // التحقق من الرصيد (للإجازات الاعتيادية فقط)
            if ($leave['leave_type'] === 'ordinary') {
                $balance = getLeaveBalance($leave['employee_id']);
                if ($balance && $balance['remaining_balance'] < $leave['days']) {
                    throw new Exception('الرصيد المتبقي غير كافي');
                }
            }
            
            // تحديث حالة الإجازة
            $stmt = $db->prepare("UPDATE employee_leaves 
                                 SET status = 'approved', 
                                     approved_by = ?, 
                                     approved_at = NOW() 
                                 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
            
            // تحديث الرصيد (للإجازات الاعتيادية فقط)
            if ($leave['leave_type'] === 'ordinary') {
                updateLeaveBalance($leave['employee_id'], $leave['days']);
            }
            
            // تحديث الموافقات
            $stmt = $db->prepare("UPDATE leave_approvals 
                                 SET status = 'approved', 
                                     approver_id = ?, 
                                     approver_name = ?, 
                                     approved_at = NOW() 
                                 WHERE leave_id = ? AND approver_type = 'assistant_dean'");
            $stmt->execute([
                $_SESSION['user_id'],
                $_SESSION['username'],
                $id
            ]);
            
            $success = 'تم الموافقة على الإجازة بنجاح';
        } else {
            // رفض الإجازة
            if (empty($rejection_reason)) {
                $error = 'يرجى إدخال سبب الرفض';
            } else {
                $stmt = $db->prepare("UPDATE employee_leaves 
                                     SET status = 'rejected', 
                                         rejection_reason = ? 
                                     WHERE id = ?");
                $stmt->execute([$rejection_reason, $id]);
                
                // تحديث الموافقات
                $stmt = $db->prepare("UPDATE leave_approvals 
                                     SET status = 'rejected', 
                                         approver_id = ?, 
                                         approver_name = ?, 
                                         notes = ?,
                                         approved_at = NOW() 
                                     WHERE leave_id = ? AND approver_type = 'assistant_dean'");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_SESSION['username'],
                    $rejection_reason,
                    $id
                ]);
                
                $success = 'تم رفض الإجازة';
            }
        }
        
        if (empty($error)) {
            $db->commit();
            header('Location: ' . SITE_URL . '/admin/leaves/view.php?id=' . $id);
            exit();
        } else {
            $db->rollBack();
        }
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error approving/rejecting leave: " . $e->getMessage());
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// الحصول على معلومات الموظف
$stmt = $db->prepare("SELECT e.*, d.name as department_name 
                      FROM employees e 
                      LEFT JOIN departments d ON e.department_id = d.id 
                      WHERE e.id = ?");
$stmt->execute([$leave['employee_id']]);
$employee = $stmt->fetch();

$page_title = $action === 'approve' ? 'موافقة على الإجازة' : 'رفض الإجازة';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <?php echo $action === 'approve' ? 'موافقة على الإجازة' : 'رفض الإجازة'; ?>
            </h3>
            <a href="<?php echo SITE_URL; ?>/admin/leaves/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="leave-summary">
            <h4>ملخص الإجازة</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <label>الموظف:</label>
                    <span><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                </div>
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

        <form method="POST" action="" class="form">
            <?php if ($action === 'reject'): ?>
                <div class="form-group">
                    <label for="rejection_reason">سبب الرفض <span class="required">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" 
                              placeholder="اذكر سبب رفض الإجازة..." required></textarea>
                </div>
            <?php else: ?>
                <?php 
                $balance = getLeaveBalance($leave['employee_id']);
                if ($leave['leave_type'] === 'ordinary' && $balance): 
                ?>
                    <div class="alert alert-info">
                        <strong>معلومات الرصيد:</strong><br>
                        الرصيد المتبقي: <strong><?php echo $balance['remaining_balance']; ?></strong> يوم<br>
                        عدد أيام الإجازة: <strong><?php echo $leave['days']; ?></strong> يوم<br>
                        الرصيد بعد الموافقة: <strong><?php echo $balance['remaining_balance'] - $leave['days']; ?></strong> يوم
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-<?php echo $action === 'approve' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $action === 'approve' ? 'check' : 'times'; ?>"></i>
                    <?php echo $action === 'approve' ? 'موافقة' : 'رفض'; ?>
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/leaves/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.leave-summary {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
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
