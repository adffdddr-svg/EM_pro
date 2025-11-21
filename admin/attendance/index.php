<?php
/**
 * Employee Management System
 * قائمة الحضور والانصراف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();

// التحقق من وجود الجداول
try {
    $db->query("SELECT 1 FROM attendance LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error_message = "جداول نظام الحضور غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_attendance_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// معالجة البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$employee_filter = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;
$date_filter = cleanInput($_GET['date'] ?? '');

// بناء الاستعلام
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($employee_filter > 0) {
    $where_conditions[] = "a.employee_id = ?";
    $params[] = $employee_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "a.attendance_date = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// الحصول على العدد الإجمالي
try {
    $count_sql = "SELECT COUNT(*) as total 
                  FROM attendance a 
                  JOIN employees e ON a.employee_id = e.id 
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

// الحصول على سجلات الحضور
$attendance_records = [];
try {
    $sql = "SELECT a.*, 
                   e.first_name, e.last_name, e.employee_code, e.position,
                   d.name as department_name,
                   s.schedule_name
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.id 
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN schedules s ON a.schedule_id = s.id
            $where_clause
            ORDER BY a.attendance_date DESC, a.time_in DESC
            LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error_message = "جداول نظام الحضور غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_attendance_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// الحصول على قائمة الموظفين للفلتر
$employees_stmt = $db->query("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'الحضور والانصراف';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">📋 الحضور والانصراف</h1>
        <div class="page-actions">
            <a href="<?php echo SITE_URL; ?>/admin/attendance/add.php" class="btn btn-primary">
                ➕ إضافة سجل حضور
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
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="اسم الموظف أو الرمز الوظيفي">
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
                    <label>التاريخ</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 بحث</button>
                    <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="btn btn-secondary">🔄 إعادة تعيين</a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الحضور -->
    <div class="card">
        <div class="card-header">
            <h3>📊 سجلات الحضور (<?php echo $total_records; ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (count($attendance_records) > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الموظف</th>
                                <th>القسم</th>
                                <th>نوع اليوم</th>
                                <th>الجدول</th>
                                <th>وقت الحضور</th>
                                <th>وقت الانصراف</th>
                                <th>الوقت الإضافي</th>
                                <th>التأخير</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo formatDate($record['attendance_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($record['employee_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['department_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $record['day_type'] == 'work_day' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $record['day_type'] == 'work_day' ? 'يوم عمل' : 'يوم عطلة'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['schedule_name'] ?? '-'); ?></td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                    <td><?php echo $record['overtime_hours'] > 0 ? $record['overtime_hours'] . ' ساعة' : '-'; ?></td>
                                    <td>
                                        <?php if ($record['late_arrival_minutes'] > 0): ?>
                                            <span class="text-danger"><?php echo $record['late_arrival_minutes']; ?> دقيقة</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/attendance/report.php?employee_id=<?php echo $record['employee_id']; ?>&week_start=<?php echo date('Y-m-d', strtotime('monday this week', strtotime($record['attendance_date']))); ?>" 
                                           class="btn btn-sm btn-info" title="عرض التقرير">
                                            📄 تقرير
                                        </a>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&date=<?php echo urlencode($date_filter); ?>">« السابق</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&date=<?php echo urlencode($date_filter); ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&employee=<?php echo $employee_filter; ?>&date=<?php echo urlencode($date_filter); ?>">التالي »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>لا توجد سجلات حضور</p>
                    <a href="<?php echo SITE_URL; ?>/admin/attendance/add.php" class="btn btn-primary">➕ إضافة سجل حضور</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

