<?php
/**
 * Employee Management System
 * قائمة الإجازات
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();

// التحقق من وجود الجداول وإنشاؤها إذا لم تكن موجودة
try {
    $db->query("SELECT 1 FROM employee_leaves LIMIT 1");
} catch (PDOException $e) {
    // إذا كان الجدول غير موجود، توجيه المستخدم لإنشاء الجداول
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error_message = "جداول نظام الإجازات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_leaves_tables.php' style='color: #667eea; text-decoration: underline;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// معالجة البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$status_filter = cleanInput($_GET['status'] ?? '');
$type_filter = cleanInput($_GET['type'] ?? '');
$employee_filter = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;

// بناء الاستعلام
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "l.leave_type = ?";
    $params[] = $type_filter;
}

if ($employee_filter > 0) {
    $where_conditions[] = "l.employee_id = ?";
    $params[] = $employee_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// الحصول على العدد الإجمالي
try {
    $count_sql = "SELECT COUNT(*) as total 
                  FROM employee_leaves l 
                  JOIN employees e ON l.employee_id = e.id 
                  $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $total_records = 0;
        $error_message = "جداول نظام الإجازات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_leaves_tables.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    } else {
        throw $e;
    }
}

// الترقيم
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ITEMS_PER_PAGE;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// الحصول على الإجازات
try {
    $sql = "SELECT l.*, 
                   e.first_name, e.last_name, e.employee_code, e.position,
                   d.name as department_name,
                   u.username as approved_by_name,
                   se.first_name as substitute_first_name, se.last_name as substitute_last_name
            FROM employee_leaves l 
            JOIN employees e ON l.employee_id = e.id 
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users u ON l.approved_by = u.id
            LEFT JOIN employees se ON l.substitute_employee_id = se.id
            $where_clause
            ORDER BY l.created_at DESC 
            LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $leaves = $stmt->fetchAll();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $leaves = [];
        if (!isset($error_message)) {
            $error_message = "جداول نظام الإجازات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_leaves_tables.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
        }
    } else {
        throw $e;
    }
}

// الحصول على جميع الموظفين للفلتر
$stmt = $db->query("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY first_name");
$all_employees = $stmt->fetchAll();

$page_title = 'إدارة الإجازات';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';

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
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">إدارة الإجازات</h3>
            <a href="<?php echo SITE_URL; ?>/admin/leaves/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة إجازة
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="margin: 20px 0; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #856404; margin-left: 10px;"></i>
                <div style="display: inline-block;">
                    <strong style="color: #856404; font-size: 16px;">تحذير مهم:</strong>
                    <p style="color: #856404; margin: 10px 0 0 0; font-size: 14px; line-height: 1.6;">
                        <?php echo $error_message; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- البحث والفلترة -->
        <div class="search-filter">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="بحث بالاسم أو الرمز الوظيفي..." 
                               value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    </div>
                    <div class="filter-group">
                        <select name="status" class="form-control">
                            <option value="">جميع الحالات</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>موافق عليها</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>مرفوضة</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ملغاة</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="type" class="form-control">
                            <option value="">جميع الأنواع</option>
                            <?php foreach ($leave_types as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="employee" class="form-control">
                            <option value="0">جميع الموظفين</option>
                            <?php foreach ($all_employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $employee_filter === $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/leaves/index.php" class="btn btn-outline">
                            <i class="fas fa-redo"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- جدول الإجازات -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>نوع الإجازة</th>
                        <th>من تاريخ</th>
                        <th>إلى تاريخ</th>
                        <th>عدد الأيام</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($leaves) > 0): ?>
                        <?php foreach ($leaves as $index => $leave): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <div class="employee-info">
                                        <strong><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($leave['employee_code']); ?></small>
                                        <?php if ($leave['department_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($leave['department_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
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
                                <td>
                                    <strong><?php echo $leave['days']; ?></strong> يوم
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status_colors[$leave['status']]; ?>">
                                        <?php echo $status_labels[$leave['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/admin/leaves/view.php?id=<?php echo $leave['id']; ?>" 
                                           class="btn btn-sm btn-info" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/leaves/approve.php?id=<?php echo $leave['id']; ?>&action=approve" 
                                               class="btn btn-sm btn-success" title="موافقة">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/admin/leaves/approve.php?id=<?php echo $leave['id']; ?>&action=reject" 
                                               class="btn btn-sm btn-danger" title="رفض">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <p class="empty-state">لا توجد إجازات</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- الترقيم -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&employee=<?php echo $employee_filter; ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i> السابق
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&employee=<?php echo $employee_filter; ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&employee=<?php echo $employee_filter; ?>" class="page-link">
                        التالي <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
