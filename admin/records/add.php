<?php
/**
 * Employee Management System
 * إضافة سجل جديد
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();
$error = '';
$success = '';

// التحقق من وجود الجداول
try {
    $db->query("SELECT 1 FROM employee_records LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error = "جداول نظام السجلات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_records_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// الحصول على الموظفين والأنواع
$employees_stmt = $db->query("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

$record_types = getRecordTypes();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $record_type = cleanInput($_POST['record_type'] ?? '');
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $record_date = cleanInput($_POST['record_date'] ?? '');
    
    // التحقق من المدخلات
    if ($employee_id <= 0) {
        $error = 'يرجى اختيار الموظف';
    } elseif (empty($record_type) || !array_key_exists($record_type, $record_types)) {
        $error = 'نوع السجل غير صحيح';
    } elseif (empty($title)) {
        $error = 'عنوان السجل مطلوب';
    } elseif (empty($record_date)) {
        $error = 'تاريخ السجل مطلوب';
    } else {
        // معالجة الملف المرفق
        $document_file = null;
        $document_path = null;
        
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['document']);
            if ($upload_result['success']) {
                $document_file = $upload_result['filename'];
                $document_path = 'assets/images/uploads/' . $document_file;
            } else {
                $error = $upload_result['message'];
            }
        }
        
        if (empty($error)) {
            try {
                $record_id = addRecord(
                    $employee_id, 
                    $record_type, 
                    $title, 
                    $description, 
                    $record_date, 
                    $document_file, 
                    $document_path, 
                    $_SESSION['user_id']
                );
                
                if ($record_id) {
                    $success = 'تم إضافة السجل بنجاح';
                    // إعادة تعيين النموذج
                    $_POST = [];
                } else {
                    $error = 'حدث خطأ أثناء إضافة السجل';
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'إضافة سجل جديد';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">➕ إضافة سجل جديد</h1>
        <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="btn btn-secondary">← رجوع</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>📝 بيانات السجل</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="form">
                <div class="form-group">
                    <label>الموظف <span class="required">*</span></label>
                    <select name="employee_id" required>
                        <option value="">اختر الموظف</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>نوع السجل <span class="required">*</span></label>
                    <select name="record_type" required>
                        <option value="">اختر نوع السجل</option>
                        <?php foreach ($record_types as $type_key => $type_label): ?>
                            <option value="<?php echo $type_key; ?>" <?php echo (isset($_POST['record_type']) && $_POST['record_type'] == $type_key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>عنوان السجل <span class="required">*</span></label>
                    <input type="text" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required placeholder="مثال: تقييم أداء شهري">
                </div>

                <div class="form-group">
                    <label>تاريخ السجل <span class="required">*</span></label>
                    <input type="date" name="record_date" value="<?php echo isset($_POST['record_date']) ? htmlspecialchars($_POST['record_date']) : date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" rows="5" placeholder="وصف تفصيلي للسجل"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>مرفق (اختياري)</label>
                    <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                    <small>يمكن رفع ملفات PDF، Word، أو صور (حجم أقصى: 5MB)</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 حفظ</button>
                    <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

