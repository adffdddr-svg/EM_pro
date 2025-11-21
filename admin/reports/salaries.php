<?php
/**
 * Employee Management System
 * تقارير الرواتب
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/report_functions.php';

requireLogin();
requireAdmin();

$db = getDB();

// الفلترة
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// الحصول على البيانات
$salary_stats = getSalaryStatsByDepartment($department_id);
$salary_changes = getSalaryChangesStats($year);
$departments = getAllDepartments();

// تحضير البيانات للرسوم البيانية
$dept_names = array_column($salary_stats, 'name');
$total_salaries = array_map(function($s) { return (float)$s['total_salary']; }, $salary_stats);
$avg_salaries = array_map(function($s) { return (float)$s['avg_salary']; }, $salary_stats);

// بيانات تغييرات الرواتب حسب الشهر
$arabic_months = getArabicMonthNames();
$months = [];
$change_counts = [];
$increases = [];
$decreases = [];

for ($i = 1; $i <= 12; $i++) {
    $months[] = $arabic_months[$i];
    $found = false;
    foreach ($salary_changes as $stat) {
        if ($stat['month'] == $i) {
            $change_counts[] = (int)$stat['change_count'];
            $increases[] = (int)$stat['increases'];
            $decreases[] = (int)$stat['decreases'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $change_counts[] = 0;
        $increases[] = 0;
        $decreases[] = 0;
    }
}

// إحصائيات إضافية
$stmt = $db->query("SELECT 
                   COUNT(*) as total_employees,
                   SUM(salary) as total_salary,
                   AVG(salary) as avg_salary,
                   MAX(salary) as max_salary,
                   MIN(salary) as min_salary
                   FROM employees
                   WHERE status = 'active'");
$overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'تقارير الرواتب';
$additional_css = ['reports.css'];
$additional_js = ['reports.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="report-page">
    <div class="report-page-header">
        <h1>
            <span>💰</span>
            تقارير الرواتب
        </h1>
        <div class="report-actions">
            <button onclick="window.print()" class="btn btn-primary">🖨️ طباعة</button>
            <a href="<?php echo SITE_URL; ?>/admin/reports/index.php" class="btn btn-secondary">← العودة</a>
        </div>
    </div>

    <!-- الفلترة -->
    <div class="report-filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label>القسم:</label>
                <select name="department_id" class="form-control">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>السنة:</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">تصفية</button>
            </div>
        </form>
    </div>

    <!-- الإحصائيات السريعة -->
    <div class="report-stats-grid">
        <div class="report-stat-card">
            <h4>إجمالي الرواتب</h4>
            <div class="stat-value"><?php echo formatCurrency($overall_stats['total_salary'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>متوسط الراتب</h4>
            <div class="stat-value"><?php echo formatCurrency($overall_stats['avg_salary'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>أعلى راتب</h4>
            <div class="stat-value" style="color: #27ae60;"><?php echo formatCurrency($overall_stats['max_salary'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>أقل راتب</h4>
            <div class="stat-value" style="color: #e74c3c;"><?php echo formatCurrency($overall_stats['min_salary'] ?? 0); ?></div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>إجمالي الرواتب حسب القسم</h3>
            <canvas id="totalSalariesChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>متوسط الرواتب حسب القسم</h3>
            <canvas id="avgSalariesChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>تغييرات الرواتب (<?php echo $year; ?>)</h3>
            <canvas id="salaryChangesChart"></canvas>
        </div>
    </div>

    <!-- جدول التفاصيل -->
    <div class="report-table-container">
        <h3>تفاصيل الرواتب حسب القسم</h3>
        <table class="report-table">
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
                <?php if (empty($salary_stats)): ?>
                    <tr>
                        <td colspan="6" class="text-center">لا توجد بيانات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($salary_stats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                            <td><?php echo formatNumber($stat['employee_count']); ?></td>
                            <td><strong><?php echo formatCurrency($stat['total_salary']); ?></strong></td>
                            <td><?php echo formatCurrency($stat['avg_salary']); ?></td>
                            <td style="color: #27ae60;"><?php echo formatCurrency($stat['max_salary']); ?></td>
                            <td style="color: #e74c3c;"><?php echo formatCurrency($stat['min_salary']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Arial, Tahoma, sans-serif';
Chart.defaults.layout.padding = 20;

// رسم بياني عمودي لإجمالي الرواتب
const ctx1 = document.getElementById('totalSalariesChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'إجمالي الرواتب',
            data: <?php echo json_encode($total_salaries); ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: '#667eea',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        return 'إجمالي الرواتب: ' + context.parsed.y.toLocaleString('ar-IQ') + ' د.ع';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('ar-IQ') + ' د.ع';
                    }
                }
            }
        }
    }
});

// رسم بياني عمودي لمتوسط الرواتب
const ctx2 = document.getElementById('avgSalariesChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'متوسط الراتب',
            data: <?php echo json_encode($avg_salaries); ?>,
            backgroundColor: 'rgba(118, 75, 162, 0.8)',
            borderColor: '#764ba2',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        return 'متوسط الراتب: ' + context.parsed.y.toLocaleString('ar-IQ') + ' د.ع';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('ar-IQ') + ' د.ع';
                    }
                }
            }
        }
    }
});

// رسم بياني خطي لتغييرات الرواتب
const ctx3 = document.getElementById('salaryChangesChart').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [
            {
                label: 'إجمالي التغييرات',
                data: <?php echo json_encode($change_counts); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            },
            {
                label: 'زيادات',
                data: <?php echo json_encode($increases); ?>,
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            },
            {
                label: 'نقصان',
                data: <?php echo json_encode($decreases); ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                rtl: true
            },
            tooltip: {
                rtl: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

