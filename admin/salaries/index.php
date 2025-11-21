<?php
/**
 * Employee Management System
 * قائمة الرواتب
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'إدارة الرواتب';
$db = getDB();

// التحقق من وجود جدول salary_history وإنشاؤه إذا لم يكن موجوداً
try {
    $db->query("SELECT 1 FROM salary_history LIMIT 1");
} catch (PDOException $e) {
    // الجدول غير موجود، إنشاؤه
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS salary_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            old_salary DECIMAL(10, 2) NULL,
            new_salary DECIMAL(10, 2) NOT NULL,
            change_type ENUM('increase', 'decrease', 'initial', 'adjustment') DEFAULT 'adjustment',
            change_amount DECIMAL(10, 2) NULL,
            change_percentage DECIMAL(5, 2) NULL,
            effective_date DATE NOT NULL,
            reason TEXT,
            notes TEXT,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_employee_id (employee_id),
            INDEX idx_effective_date (effective_date),
            INDEX idx_change_type (change_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $create_error) {
        // إذا فشل الإنشاء، عرض رسالة خطأ
        $error_msg = "خطأ في إنشاء جدول سجل الرواتب. يرجى تشغيل: " . 
                     "<a href='" . SITE_URL . "/database/create_salary_table.php' style='color: #007bff; font-weight: bold;'>إنشاء الجدول تلقائياً</a>";
    }
}

// البحث والفلترة
$search = cleanInput($_GET['search'] ?? '');
$department_id = $_GET['department_id'] ?? '';
$sort = $_GET['sort'] ?? 'salary_desc';

$where = "WHERE e.status = 'active'";
$params = [];

if (!empty($search)) {
    $where .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($department_id)) {
    $where .= " AND e.department_id = ?";
    $params[] = $department_id;
}

$order_by = "ORDER BY e.salary DESC";
switch ($sort) {
    case 'salary_asc':
        $order_by = "ORDER BY e.salary ASC";
        break;
    case 'name_asc':
        $order_by = "ORDER BY e.first_name ASC, e.last_name ASC";
        break;
    case 'name_desc':
        $order_by = "ORDER BY e.first_name DESC, e.last_name DESC";
        break;
}

// جلب الموظفين
$sql = "SELECT e.*, d.name as department_name,
        (SELECT new_salary FROM salary_history WHERE employee_id = e.id ORDER BY effective_date DESC LIMIT 1) as last_salary_change,
        (SELECT change_type FROM salary_history WHERE employee_id = e.id ORDER BY effective_date DESC LIMIT 1) as last_change_type
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        $where
        $order_by";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// جلب الأقسام للفلتر
$departments = getAllDepartments();

// حساب الإحصائيات
$stats = getSalaryStatistics($department_id ?: null);

include __DIR__ . '/../../includes/header.php';
?>

<style>
.salary-container {
    padding: 30px;
}

.salary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.stat-card h3 {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.stat-card .value {
    font-size: 28px;
    font-weight: bold;
    color: var(--primary-color);
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.filters form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filters .form-group {
    flex: 1;
    min-width: 200px;
}

.salary-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.salary-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.salary-badge.increase {
    background: #d4edda;
    color: #155724;
}

.salary-badge.decrease {
    background: #f8d7da;
    color: #721c24;
}

.salary-badge.no-change {
    background: #e2e3e5;
    color: #383d41;
}

.salary-badge.initial {
    background: #d1ecf1;
    color: #0c5460;
}

@media (max-width: 768px) {
    .salary-container {
        padding: 15px;
    }
    
    .salary-stats {
        grid-template-columns: 1fr;
    }
    
    .filters form {
        flex-direction: column;
    }
    
    .filters .form-group {
        width: 100%;
    }
}
</style>

<div class="salary-container">
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error" style="margin-bottom: 30px;">
            <strong>⚠️ تحذير:</strong> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <h1>💰 إدارة الرواتب</h1>
        <div style="display: flex; gap: 10px;">
            <a href="statistics.php" class="btn btn-info">📊 الإحصائيات</a>
            <a href="add.php" class="btn btn-primary">➕ إضافة راتب جديد</a>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="salary-stats">
        <div class="stat-card">
            <h3>إجمالي الرواتب</h3>
            <div class="value"><?php echo number_format($stats['total_salary'] ?? 0, 2); ?> د.ع</div>
        </div>
        <div class="stat-card">
            <h3>متوسط الراتب</h3>
            <div class="value"><?php echo number_format($stats['avg_salary'] ?? 0, 2); ?> د.ع</div>
        </div>
        <div class="stat-card">
            <h3>أعلى راتب</h3>
            <div class="value"><?php echo number_format($stats['max_salary'] ?? 0, 2); ?> د.ع</div>
        </div>
        <div class="stat-card">
            <h3>أقل راتب</h3>
            <div class="value"><?php echo number_format($stats['min_salary'] ?? 0, 2); ?> د.ع</div>
        </div>
        <div class="stat-card">
            <h3>عدد الموظفين</h3>
            <div class="value"><?php echo $stats['total_employees'] ?? 0; ?></div>
        </div>
    </div>

    <!-- الفلاتر -->
    <div class="filters">
        <form method="GET">
            <div class="form-group">
                <label>البحث</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="اسم الموظف أو الرمز" class="form-control">
            </div>
            <div class="form-group">
                <label>القسم</label>
                <select name="department_id" class="form-control">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" 
                                <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>الترتيب</label>
                <select name="sort" class="form-control">
                    <option value="salary_desc" <?php echo $sort == 'salary_desc' ? 'selected' : ''; ?>>الراتب (أعلى)</option>
                    <option value="salary_asc" <?php echo $sort == 'salary_asc' ? 'selected' : ''; ?>>الراتب (أقل)</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>الاسم (أ-ي)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>الاسم (ي-أ)</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 بحث</button>
                <a href="index.php" class="btn btn-secondary">🔄 إعادة تعيين</a>
            </div>
        </form>
    </div>

    <!-- الجدول -->
    <div class="salary-table">
        <table class="table">
            <thead>
                <tr>
                    <th>الرمز</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th>الراتب الحالي</th>
                    <th>آخر تغيير</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 40px;">
                            <p style="color: #999; font-size: 18px;">لا توجد نتائج</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_code']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'غير محدد'); ?></td>
                            <td>
                                <strong style="color: var(--primary-color); font-size: 18px;">
                                    <?php echo number_format($emp['salary'], 2); ?> د.ع
                                </strong>
                            </td>
                            <td>
                                <?php if ($emp['last_salary_change']): ?>
                                    <?php
                                    $badge_class = 'no-change';
                                    if ($emp['last_change_type'] == 'increase') $badge_class = 'increase';
                                    elseif ($emp['last_change_type'] == 'decrease') $badge_class = 'decrease';
                                    elseif ($emp['last_change_type'] == 'initial') $badge_class = 'initial';
                                    ?>
                                    <span class="salary-badge <?php echo $badge_class; ?>">
                                        <?php echo getSalaryChangeTypeText($emp['last_change_type']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="salary-badge no-change">لا يوجد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info">عرض</a>
                                <a href="history.php?employee_id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-secondary">السجل</a>
                                <a href="add.php?employee_id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-success">تعديل</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

