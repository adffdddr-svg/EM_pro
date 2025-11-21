<?php
/**
 * Employee Management System
 * تعديل سجل
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$error = '';
$success = '';

// الحصول على معرف السجل
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/records/index.php');
}

// الحصول على بيانات السجل
$record = getRecord($id);

if (!$record) {
    redirect(SITE_URL . '/admin/records/index.php');
}

$record_types = getRecordTypes();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $record_date = cleanInput($_POST['record_date'] ?? '');
    $record_type = cleanInput($_POST['record_type'] ?? '');
    
    if (empty($title)) {
        $error = 'عنوان السجل مطلوب';
    } elseif (empty($record_date)) {
        $error = 'تاريخ السجل مطلوب';
    } else {
        try {
            if (updateRecord($id, $title, $description, $record_date, $record_type)) {
                $success = 'تم تحديث السجل بنجاح';
                // إعادة جلب البيانات
                $record = getRecord($id);
            } else {
                $error = 'حدث خطأ أثناء تحديث السجل';
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

$page_title = 'تعديل سجل';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">✏️ تعديل سجل</h1>
        <a href="<?php echo SITE_URL; ?>/admin/records/view.php?id=<?php echo $record['id']; ?>" class="btn btn-secondary">← رجوع</a>
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
            <form method="POST" class="form">
                <div class="form-group">
                    <label>الموظف:</label>
                    <input type="text" value="<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name'] . ' (' . $record['employee_code'] . ')'); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>نوع السجل <span class="required">*</span></label>
                    <select name="record_type" required>
                        <?php foreach ($record_types as $type_key => $type_label): ?>
                            <option value="<?php echo $type_key; ?>" <?php echo $record['record_type'] == $type_key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>عنوان السجل <span class="required">*</span></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($record['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label>تاريخ السجل <span class="required">*</span></label>
                    <input type="date" name="record_date" value="<?php echo $record['record_date']; ?>" required>
                </div>

                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" rows="5"><?php echo htmlspecialchars($record['description'] ?? ''); ?></textarea>
                </div>

                <?php if ($record['document_file']): ?>
                <div class="form-group">
                    <label>الملف المرفق الحالي:</label>
                    <a href="<?php echo SITE_URL . '/' . $record['document_path']; ?>" target="_blank" class="btn btn-info">
                        📄 <?php echo htmlspecialchars($record['document_file']); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 حفظ التغييرات</button>
                    <a href="<?php echo SITE_URL; ?>/admin/records/view.php?id=<?php echo $record['id']; ?>" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

