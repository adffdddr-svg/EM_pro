<?php
/**
 * Employee Management System
 * قائمة السجلات
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

// التحقق من وجود الجداول
try {
    $db->query("SELECT 1 FROM employee_records LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error_message = "جداول نظام السجلات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_records_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// معالجة البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$employee_filter = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;
$type_filter = cleanInput($_GET['type'] ?? '');
$status_filter = cleanInput($_GET['status'] ?? 'active');

// بناء الاستعلام
$where_conditions = ["r.status = ?"];
$params = [$status_filter];

if (!empty($search)) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($employee_filter > 0) {
    $where_conditions[] = "r.employee_id = ?";
    $params[] = $employee_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "r.record_type = ?";
    $params[] = $type_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// الحصول على العدد الإجمالي
try {
    $count_sql = "SELECT COUNT(*) as total 
                  FROM employee_records r 
                  JOIN employees e ON r.employee_id = e.id 
                  $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_records = 0;
}

// الترقيم
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ITEMS_PER_PAGE;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// الحصول على السجلات
$records = [];
try {
    $sql = "SELECT r.*, 
                   e.first_name, e.last_name, e.employee_code, e.position,
                   d.name as department_name,
                   u.username as created_by_name
            FROM employee_records r 
            JOIN employees e ON r.employee_id = e.id 
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users u ON r.created_by = u.id
            $where_clause
            ORDER BY r.record_date DESC, r.created_at DESC
            LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error_message = "جداول نظام السجلات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_records_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// الحصول على قائمة الموظفين والأنواع
$employees_stmt = $db->query("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

$record_types = getRecordTypes();

$page_title = 'السجلات';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">📁 السجلات</h1>
        <div class="page-actions">
            <a href="<?php echo SITE_URL; ?>/admin/records/add.php" class="btn btn-primary">
                ➕ إضافة سجل جديد
            </a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-warning">
            ⚠️ <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- البحث والفلترة -->
    <div class="card">
        <div class="card-header">
            <h3>🔍 البحث والفلترة</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="search-filter">
                <div class="form-group">
                    <label>البحث</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="عنوان السجل، الوصف، أو اسم الموظف">
                </div>
                <div class="form-group">
                    <label>الموظف</label>
                    <select name="employee">
                        <option value="">جميع الموظفين</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo $employee_filter == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع السجل</label>
                    <select name="type">
                        <option value="">جميع الأنواع</option>
                        <?php foreach ($record_types as $type_key => $type_label): ?>
                            <option value="<?php echo $type_key; ?>" <?php echo $type_filter == $type_key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>الحالة</label>
                    <select name="status">
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="archived" <?php echo $status_filter == 'archived' ? 'selected' : ''; ?>>مؤرشف</option>
                        <option value="deleted" <?php echo $status_filter == 'deleted' ? 'selected' : ''; ?>>محذوف</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 بحث</button>
                    <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="btn btn-secondary">🔄 إعادة تعيين</a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول السجلات -->
    <div class="card">
        <div class="card-header">
            <h3>📋 قائمة السجلات (<?php echo $total_records; ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (count($records) > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الموظف</th>
                                <th>نوع السجل</th>
                                <th>العنوان</th>
                                <th>الوصف</th>
                                <th>المرفق</th>
                                <th>أنشأ بواسطة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['record_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($record['employee_code']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars(getRecordTypeText($record['record_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                    <td>
                                        <?php 
                                        $desc = htmlspecialchars($record['description'] ?? '');
                                        echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($record['document_file']): ?>
                                            <a href="<?php echo SITE_URL . '/' . $record['document_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                📄 عرض
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['created_by_name'] ?? '-'); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/records/view.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-sm btn-info" title="عرض">
                                            👁️
                                        </a>
                                        <?php if (isAdmin()): ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/records/edit.php?id=<?php echo $record['id']; ?>" 
                                               class="btn btn-sm btn-success" title="تعديل">
                                                ✏️
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/records/delete.php?id=<?php echo $record['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               title="حذف"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟');">
                                                🗑️
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- الترقيم -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">« السابق</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">التالي »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>لا توجد سجلات</p>
                    <a href="<?php echo SITE_URL; ?>/admin/records/add.php" class="btn btn-primary">➕ إضافة سجل جديد</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

