<?php
/**
 * Employee Management System
 * إحصائيات الرواتب
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'إحصائيات الرواتب';
$db = getDB();

// التحقق من وجود جدول salary_history وإنشاؤه إذا لم يكن موجوداً
try {
    $db->query("SELECT 1 FROM salary_history LIMIT 1");
} catch (PDOException $e) {
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
        // تجاهل الخطأ، سيتم التعامل معه لاحقاً
    }
}

// الفلترة
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// جلب الإحصائيات العامة
$stats = getSalaryStatistics($department_id ?: null);

// جلب الأقسام
$departments = getAllDepartments();

// إحصائيات حسب القسم
$stmt = $db->query("SELECT d.id, d.name, 
                   COUNT(e.id) as employee_count,
                   SUM(e.salary) as total_salary,
                   AVG(e.salary) as avg_salary,
                   MAX(e.salary) as max_salary,
                   MIN(e.salary) as min_salary
                   FROM departments d
                   LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                   GROUP BY d.id, d.name
                   ORDER BY total_salary DESC");
$dept_stats = $stmt->fetchAll();

// إحصائيات التغييرات حسب الشهر
$stmt = $db->prepare("SELECT 
                     YEAR(effective_date) as year,
                     MONTH(effective_date) as month,
                     COUNT(*) as change_count,
                     SUM(CASE WHEN change_type = 'increase' THEN 1 ELSE 0 END) as increases,
                     SUM(CASE WHEN change_type = 'decrease' THEN 1 ELSE 0 END) as decreases,
                     AVG(change_amount) as avg_change
                     FROM salary_history
                     WHERE YEAR(effective_date) = ?
                     GROUP BY YEAR(effective_date), MONTH(effective_date)
                     ORDER BY year DESC, month DESC
                     LIMIT 12");
$stmt->execute([$year]);
$monthly_changes = $stmt->fetchAll();

// إحصائيات التغييرات حسب النوع
$stmt = $db->query("SELECT 
                   change_type,
                   COUNT(*) as count,
                   AVG(change_amount) as avg_amount,
                   AVG(change_percentage) as avg_percentage
                   FROM salary_history
                   GROUP BY change_type");
$type_stats = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<style>
.stats-container {
    padding: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
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
    margin-bottom: 15px;
}

.stat-card .value {
    font-size: 32px;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.stat-card .sub-value {
    color: #999;
    font-size: 14px;
}

.chart-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.chart-section h2 {
    color: var(--primary-color);
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.filters-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .stats-container {
        padding: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="stats-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <h1>📊 إحصائيات الرواتب</h1>
        <a href="index.php" class="btn btn-secondary">العودة للقائمة</a>
    </div>
    
    <!-- الفلاتر -->
    <div class="filters-bar">
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label>القسم</label>
                <select name="department_id" class="form-control">
                    <option value="0">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" 
                                <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label>السنة</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 تطبيق</button>
            </div>
        </form>
    </div>
    
    <!-- الإحصائيات العامة -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>إجمالي الرواتب</h3>
            <div class="value"><?php echo number_format($stats['total_salary'] ?? 0, 2); ?></div>
            <div class="sub-value">دينار عراقي</div>
        </div>
        <div class="stat-card">
            <h3>متوسط الراتب</h3>
            <div class="value"><?php echo number_format($stats['avg_salary'] ?? 0, 2); ?></div>
            <div class="sub-value">دينار عراقي</div>
        </div>
        <div class="stat-card">
            <h3>أعلى راتب</h3>
            <div class="value"><?php echo number_format($stats['max_salary'] ?? 0, 2); ?></div>
            <div class="sub-value">دينار عراقي</div>
        </div>
        <div class="stat-card">
            <h3>أقل راتب</h3>
            <div class="value"><?php echo number_format($stats['min_salary'] ?? 0, 2); ?></div>
            <div class="sub-value">دينار عراقي</div>
        </div>
        <div class="stat-card">
            <h3>عدد الموظفين</h3>
            <div class="value"><?php echo $stats['total_employees'] ?? 0; ?></div>
            <div class="sub-value">موظف</div>
        </div>
    </div>
    
    <!-- إحصائيات حسب القسم -->
    <div class="chart-section">
        <h2>📈 إحصائيات حسب القسم</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>عدد الموظفين</th>
                        <th>إجمالي الرواتب</th>
                        <th>متوسط الراتب</th>
                        <th>أعلى راتب</th>
                        <th>أقل راتب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dept_stats as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                            <td><?php echo $dept['employee_count'] ?? 0; ?></td>
                            <td><?php echo number_format($dept['total_salary'] ?? 0, 2); ?> د.ع</td>
                            <td><?php echo number_format($dept['avg_salary'] ?? 0, 2); ?> د.ع</td>
                            <td><?php echo number_format($dept['max_salary'] ?? 0, 2); ?> د.ع</td>
                            <td><?php echo number_format($dept['min_salary'] ?? 0, 2); ?> د.ع</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- التغييرات الشهرية -->
    <?php if (!empty($monthly_changes)): ?>
        <div class="chart-section">
            <h2>📅 التغييرات الشهرية (<?php echo $year; ?>)</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الشهر</th>
                            <th>عدد التغييرات</th>
                            <th>الزيادات</th>
                            <th>التخفيضات</th>
                            <th>متوسط التغيير</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $months = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                                  'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                        foreach ($monthly_changes as $change): 
                        ?>
                            <tr>
                                <td><strong><?php echo $months[$change['month']] ?? $change['month']; ?></strong></td>
                                <td><?php echo $change['change_count']; ?></td>
                                <td style="color: #28a745;"><?php echo $change['increases']; ?></td>
                                <td style="color: #dc3545;"><?php echo $change['decreases']; ?></td>
                                <td><?php echo number_format($change['avg_change'] ?? 0, 2); ?> د.ع</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- إحصائيات حسب النوع -->
    <?php if (!empty($type_stats)): ?>
        <div class="chart-section">
            <h2>📊 إحصائيات حسب نوع التغيير</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>نوع التغيير</th>
                            <th>عدد المرات</th>
                            <th>متوسط المبلغ</th>
                            <th>متوسط النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($type_stats as $type): ?>
                            <tr>
                                <td><strong><?php echo getSalaryChangeTypeText($type['change_type']); ?></strong></td>
                                <td><?php echo $type['count']; ?></td>
                                <td><?php echo number_format($type['avg_amount'] ?? 0, 2); ?> د.ع</td>
                                <td><?php echo number_format($type['avg_percentage'] ?? 0, 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

