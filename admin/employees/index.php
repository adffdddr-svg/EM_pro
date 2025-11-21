<?php
/**
 * Employee Management System
 * قائمة الموظفين
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin(); // يتطلب صلاحيات المدير

$db = getDB();

// معالجة البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$status_filter = cleanInput($_GET['status'] ?? '');

// بناء الاستعلام
$where_conditions = ["e.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ? OR e.position LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($department_filter > 0) {
    $where_conditions[] = "e.department_id = ?";
    $params[] = $department_filter;
}

if (!empty($status_filter)) {
    $where_conditions[0] = "e.status = ?";
    $params = array_merge([$status_filter], $params);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// الحصول على العدد الإجمالي
$count_sql = "SELECT COUNT(*) as total FROM employees e $where_clause";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// الترقيم
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ITEMS_PER_PAGE;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// الحصول على الموظفين
$sql = "SELECT e.*, d.name as department_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        $where_clause 
        ORDER BY e.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// الحصول على الأقسام للفلترة
$departments = getAllDepartments();

$page_title = 'قائمة الموظفين';
include __DIR__ . '/../../includes/header.php';
?>

<div class="employees-list">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">قائمة الموظفين</h2>
        </div>

        <!-- البحث والفلترة -->
        <div class="search-filter">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search">بحث</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="ابحث بالاسم، الرمز الوظيفي، البريد، أو المسمى الوظيفي" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">القسم</label>
                    <select id="department" name="department" class="form-control">
                        <option value="0">جميع الأقسام</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">الحالة</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">جميع الحالات</option>
                        <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- الجدول -->
        <?php if (count($employees) > 0): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الاسم</th>
                            <th>الرمز الوظيفي</th>
                            <th>القسم</th>
                            <th>المسمى الوظيفي</th>
                            <th>الراتب</th>
                            <th>تاريخ التوظيف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td>
                                    <?php if ($employee['photo']): ?>
                                        <img src="<?php echo UPLOAD_URL . $employee['photo']; ?>" 
                                             alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">بدون</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td><?php echo formatCurrency($employee['salary']); ?></td>
                                <td><?php echo formatDate($employee['hire_date']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="<?php echo SITE_URL; ?>/admin/employees/view.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="عرض">عرض</a>
                                        <a href="<?php echo SITE_URL; ?>/admin/employees/edit.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-success" title="تعديل">تعديل</a>
                                        <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php?archive_id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="أرشفة" 
                                           onclick="return confirm('هل أنت متأكد من أرشفة هذا الموظف؟');">أرشفة</a>
                                        <a href="<?php echo SITE_URL; ?>/admin/employees/delete.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="حذف" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذا الموظف؟ هذا الإجراء لا يمكن التراجع عنه.');">حذف</a>
                                    </div>
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>">السابق</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>&status=<?php echo urlencode($status_filter); ?>">التالي</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>لا توجد موظفين</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

