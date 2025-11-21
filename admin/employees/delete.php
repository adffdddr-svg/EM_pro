<?php
/**
 * Employee Management System
 * حذف موظف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin(); // يتطلب صلاحيات المدير

$db = getDB();

// الحصول على معرف الموظف
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/employees/index.php');
}

// الحصول على بيانات الموظف
$stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect(SITE_URL . '/admin/employees/index.php');
}

// معالجة الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'رمز الأمان غير صحيح';
        redirect(SITE_URL . '/admin/employees/index.php');
    }
    
    // التحقق من التأكيد
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        try {
            // حذف الصورة إن وجدت
            if ($employee['photo']) {
                deleteImage($employee['photo']);
            }
            
            // حذف الموظف من قاعدة البيانات
            $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = 'تم حذف الموظف بنجاح';
            redirect(SITE_URL . '/admin/employees/index.php');
        } catch (PDOException $e) {
            error_log("Error deleting employee: " . $e->getMessage());
            $_SESSION['error'] = 'حدث خطأ أثناء حذف الموظف';
            redirect(SITE_URL . '/admin/employees/index.php');
        }
    } else {
        redirect(SITE_URL . '/admin/employees/index.php');
    }
}

$page_title = 'حذف موظف';
include __DIR__ . '/../../includes/header.php';
?>

<div class="delete-employee">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">حذف موظف</h2>
            <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
        </div>

        <div class="alert alert-warning">
            <strong>تحذير!</strong> أنت على وشك حذف موظف. هذا الإجراء لا يمكن التراجع عنه.
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
                <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($employee['employee_code']); ?></p>
                
                <div class="info-section">
                    <div class="info-row">
                        <span class="info-label">البريد الإلكتروني:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">القسم:</span>
                        <span class="info-value"><?php echo htmlspecialchars(getDepartmentName($employee['department_id'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المسمى الوظيفي:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['position']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ التوظيف:</span>
                        <span class="info-value"><?php echo formatDate($employee['hire_date']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="" class="delete-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="confirm" value="yes">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد تماماً من حذف هذا الموظف؟ هذا الإجراء لا يمكن التراجع عنه.');">
                    نعم، احذف الموظف
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/employees/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">إلغاء</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
            </div>
        </form>
    </div>
</div>

<style>
.delete-employee .employee-card {
    display: flex;
    gap: 40px;
    padding: 30px 0;
    margin-bottom: 30px;
}

.delete-form {
    padding-top: 20px;
    border-top: 2px solid var(--bg-color);
}

@media (max-width: 768px) {
    .delete-employee .employee-card {
        flex-direction: column;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

