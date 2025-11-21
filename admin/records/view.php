<?php
/**
 * Employee Management System
 * عرض تفاصيل سجل
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

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

$page_title = 'تفاصيل السجل';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">📄 تفاصيل السجل</h1>
        <div class="page-actions">
            <?php if (isAdmin()): ?>
                <a href="<?php echo SITE_URL; ?>/admin/records/edit.php?id=<?php echo $record['id']; ?>" class="btn btn-success">✏️ تعديل</a>
                <a href="<?php echo SITE_URL; ?>/admin/records/delete.php?id=<?php echo $record['id']; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟');">🗑️ حذف</a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="btn btn-secondary">← رجوع</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📋 معلومات السجل</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>الموظف:</label>
                    <div>
                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
                        <small>الرمز الوظيفي: <?php echo htmlspecialchars($record['employee_code']); ?></small>
                    </div>
                </div>

                <div class="info-item">
                    <label>نوع السجل:</label>
                    <span class="badge badge-info"><?php echo htmlspecialchars(getRecordTypeText($record['record_type'])); ?></span>
                </div>

                <div class="info-item">
                    <label>العنوان:</label>
                    <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                </div>

                <div class="info-item">
                    <label>تاريخ السجل:</label>
                    <?php echo formatDate($record['record_date']); ?>
                </div>

                <div class="info-item">
                    <label>الوصف:</label>
                    <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($record['description'] ?? 'لا يوجد وصف'); ?></div>
                </div>

                <?php if ($record['document_file']): ?>
                <div class="info-item">
                    <label>الملف المرفق:</label>
                    <a href="<?php echo SITE_URL . '/' . $record['document_path']; ?>" target="_blank" class="btn btn-info">
                        📄 <?php echo htmlspecialchars($record['document_file']); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <label>أنشأ بواسطة:</label>
                    <?php echo htmlspecialchars($record['created_by_name'] ?? '-'); ?>
                </div>

                <div class="info-item">
                    <label>تاريخ الإنشاء:</label>
                    <?php echo formatDate($record['created_at'], DATETIME_FORMAT); ?>
                </div>

                <div class="info-item">
                    <label>آخر تحديث:</label>
                    <?php echo formatDate($record['updated_at'], DATETIME_FORMAT); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

