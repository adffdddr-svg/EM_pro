<?php
/**
 * Employee Management System
 * الأرشيف - أرشفة واستعادة الموظفين
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin(); // يتطلب صلاحيات المدير

$db = getDB();
$error = '';
$success = '';

// معالجة الأرشفة
if (isset($_GET['archive_id'])) {
    $archive_id = (int)$_GET['archive_id'];
    
    // الحصول على بيانات الموظف
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$archive_id]);
    $employee = $stmt->fetch();
    
    if ($employee) {
        try {
            $db->beginTransaction();
            
            // نقل البيانات إلى الأرشيف
            $stmt = $db->prepare("INSERT INTO employees_archive (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, leave_date, photo, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)");
            $stmt->execute([
                $employee['employee_code'],
                $employee['first_name'],
                $employee['last_name'],
                $employee['email'],
                $employee['phone'],
                $employee['address'],
                $employee['department_id'],
                $employee['position'],
                $employee['salary'],
                $employee['hire_date'],
                $employee['photo'],
                $_SESSION['user_id'],
                'أرشفة يدوية'
            ]);
            
            // حذف من جدول الموظفين النشطين
            $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$archive_id]);
            
            $db->commit();
            $success = 'تم أرشفة الموظف بنجاح';
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error archiving employee: " . $e->getMessage());
            $error = 'حدث خطأ أثناء أرشفة الموظف';
        }
    } else {
        $error = 'الموظف غير موجود';
    }
}

// معالجة الاستعادة
if (isset($_GET['restore_id'])) {
    $restore_id = (int)$_GET['restore_id'];
    
    // الحصول على بيانات الموظف من الأرشيف
    $stmt = $db->prepare("SELECT * FROM employees_archive WHERE id = ?");
    $stmt->execute([$restore_id]);
    $archived_employee = $stmt->fetch();
    
    if ($archived_employee) {
        // التحقق من عدم وجود موظف بنفس الرمز الوظيفي
        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = ?");
        $stmt->execute([$archived_employee['employee_code']]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'يوجد موظف نشط بنفس الرمز الوظيفي';
        } else {
            try {
                $db->beginTransaction();
                
                // إعادة الموظف إلى جدول الموظفين النشطين
                $stmt = $db->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $archived_employee['employee_code'],
                    $archived_employee['first_name'],
                    $archived_employee['last_name'],
                    $archived_employee['email'],
                    $archived_employee['phone'],
                    $archived_employee['address'],
                    $archived_employee['department_id'],
                    $archived_employee['position'],
                    $archived_employee['salary'],
                    $archived_employee['hire_date'],
                    $archived_employee['photo']
                ]);
                
                // حذف من الأرشيف
                $stmt = $db->prepare("DELETE FROM employees_archive WHERE id = ?");
                $stmt->execute([$restore_id]);
                
                $db->commit();
                $success = 'تم استعادة الموظف بنجاح';
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("Error restoring employee: " . $e->getMessage());
                $error = 'حدث خطأ أثناء استعادة الموظف';
            }
        }
    } else {
        $error = 'السجل غير موجود في الأرشيف';
    }
}

// معالجة البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// بناء الاستعلام
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ? OR email LIKE ? OR position LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($department_filter > 0) {
    $where_conditions[] = "department_id = ?";
    $params[] = $department_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// الحصول على العدد الإجمالي
$count_sql = "SELECT COUNT(*) as total FROM employees_archive $where_clause";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

// الترقيم
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ITEMS_PER_PAGE;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// الحصول على الموظفين المؤرشفين
$sql = "SELECT ea.*, d.name as department_name, u.username as archived_by_name 
        FROM employees_archive ea 
        LEFT JOIN departments d ON ea.department_id = d.id 
        LEFT JOIN users u ON ea.archived_by = u.id 
        $where_clause 
        ORDER BY ea.archived_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$archived_employees = $stmt->fetchAll();

// الحصول على الأقسام للفلترة
$departments = getAllDepartments();

$page_title = 'أرشيف الموظفين';
include __DIR__ . '/../../includes/header.php';
?>

<div class="archive-list">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">أرشيف الموظفين</h2>
            <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

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
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php" class="btn btn-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- الجدول -->
        <?php if (count($archived_employees) > 0): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الاسم</th>
                            <th>الرمز الوظيفي</th>
                            <th>القسم</th>
                            <th>المسمى الوظيفي</th>
                            <th>تاريخ التوظيف</th>
                            <th>تاريخ المغادرة</th>
                            <th>تاريخ الأرشفة</th>
                            <th>أرشف بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_employees as $employee): ?>
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
                                <td><?php echo formatDate($employee['hire_date']); ?></td>
                                <td><?php echo formatDate($employee['leave_date']); ?></td>
                                <td><?php echo formatDate($employee['archived_at'], 'Y-m-d H:i:s'); ?></td>
                                <td><?php echo htmlspecialchars($employee['archived_by_name'] ?? 'غير معروف'); ?></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php?restore_id=<?php echo $employee['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       onclick="return confirm('هل أنت متأكد من استعادة هذا الموظف؟');" 
                                       title="استعادة">استعادة</a>
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>">السابق</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department_filter; ?>">التالي</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>لا توجد موظفين في الأرشيف</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

