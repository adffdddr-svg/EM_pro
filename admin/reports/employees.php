<?php
/**
 * Employee Management System
 * تقارير الموظفين
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
$dept_stats = getEmployeesByDepartment($department_id);
$monthly_stats = getNewEmployeesByMonth($year);
$departments = getAllDepartments();

// تحضير البيانات للرسوم البيانية
$dept_names = array_column($dept_stats, 'name');
$dept_counts = array_column($dept_stats, 'employee_count');
$dept_active = array_column($dept_stats, 'active_count');

// بيانات الموظفين الجدد حسب الشهر
$months = [];
$new_employees_data = [];
$arabic_months = getArabicMonthNames();

for ($i = 1; $i <= 12; $i++) {
    $months[] = $arabic_months[$i];
    $found = false;
    foreach ($monthly_stats as $stat) {
        if ($stat['month'] == $i) {
            $new_employees_data[] = (int)$stat['count'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $new_employees_data[] = 0;
    }
}

// إحصائيات إضافية
$stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
$total_active = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'inactive'");
$total_inactive = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM employees_archive");
$total_archived = $stmt->fetch()['total'];

$page_title = 'تقارير الموظفين';
$additional_css = ['reports.css'];
$additional_js = ['reports.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="report-page">
    <div class="report-page-header">
        <h1>
            <span>👥</span>
            تقارير الموظفين
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
            <h4>الموظفين النشطين</h4>
            <div class="stat-value"><?php echo formatNumber($total_active); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>الموظفين غير النشطين</h4>
            <div class="stat-value"><?php echo formatNumber($total_inactive); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>الموظفين في الأرشيف</h4>
            <div class="stat-value"><?php echo formatNumber($total_archived); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>إجمالي الموظفين</h4>
            <div class="stat-value"><?php echo formatNumber($total_active + $total_inactive + $total_archived); ?></div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>توزيع الموظفين حسب القسم</h3>
            <canvas id="employeesByDeptChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>الموظفين النشطين حسب القسم</h3>
            <canvas id="activeEmployeesChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>الموظفين الجدد حسب الشهر (<?php echo $year; ?>)</h3>
            <canvas id="newEmployeesChart"></canvas>
        </div>
    </div>

    <!-- جدول التفاصيل -->
    <div class="report-table-container">
        <h3>تفاصيل الموظفين حسب القسم</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>إجمالي الموظفين</th>
                    <th>نشطين</th>
                    <th>غير نشطين</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dept_stats)): ?>
                    <tr>
                        <td colspan="4" class="text-center">لا توجد بيانات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dept_stats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                            <td><?php echo formatNumber($stat['employee_count']); ?></td>
                            <td><?php echo formatNumber($stat['active_count']); ?></td>
                            <td><?php echo formatNumber($stat['inactive_count']); ?></td>
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
// إعدادات Chart.js للعربية
Chart.defaults.font.family = 'Arial, Tahoma, sans-serif';
Chart.defaults.layout.padding = 20;

// رسم بياني دائري لتوزيع الموظفين
const ctx1 = document.getElementById('employeesByDeptChart').getContext('2d');
new Chart(ctx1, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'عدد الموظفين',
            data: <?php echo json_encode($dept_counts); ?>,
            backgroundColor: [
                '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe',
                '#43e97b', '#fa709a', '#fee140', '#30cfd0', '#a8edea'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                rtl: true,
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        weight: '500'
                    }
                }
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' موظف';
                    }
                }
            }
        }
    }
});

// رسم بياني عمودي للموظفين النشطين
const ctx2 = document.getElementById('activeEmployeesChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'الموظفين النشطين',
            data: <?php echo json_encode($dept_active); ?>,
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
                        return 'الموظفين النشطين: ' + context.parsed.y;
                    }
                }
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

// رسم بياني خطي للموظفين الجدد
const ctx3 = document.getElementById('newEmployeesChart').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'الموظفين الجدد',
            data: <?php echo json_encode($new_employees_data); ?>,
            borderColor: '#764ba2',
            backgroundColor: 'rgba(118, 75, 162, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: '#764ba2',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
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
                        return 'الموظفين الجدد: ' + context.parsed.y;
                    }
                }
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
