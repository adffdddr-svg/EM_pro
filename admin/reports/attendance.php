<?php
/**
 * Employee Management System
 * تقارير الحضور والانصراف
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
$start_date = isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : date('Y-m-t');
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// الحصول على البيانات
$attendance_stats = getAttendanceStats($start_date, $end_date, $department_id);
$top_employees = getAttendanceByEmployee($start_date, $end_date, 10);
$departments = getAllDepartments();

// تحضير البيانات للرسوم البيانية
$dates = [];
$attendance_counts = [];
foreach ($attendance_stats as $stat) {
    $dates[] = date('Y-m-d', strtotime($stat['date']));
    $attendance_counts[] = (int)$stat['total_attendance'];
}

// إحصائيات إضافية
$total_records = array_sum($attendance_counts);
$avg_daily = count($attendance_counts) > 0 ? round($total_records / count($attendance_counts), 2) : 0;

$page_title = 'تقارير الحضور والانصراف';
$additional_css = ['reports.css'];
$additional_js = ['reports.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="report-page">
    <div class="report-page-header">
        <h1>
            <span>⏰</span>
            تقارير الحضور والانصراف
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
                <label>من تاريخ:</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="form-group">
                <label>إلى تاريخ:</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
            </div>
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
                <button type="submit" class="btn btn-primary">تصفية</button>
            </div>
        </form>
    </div>

    <!-- الإحصائيات السريعة -->
    <div class="report-stats-grid">
        <div class="report-stat-card">
            <h4>إجمالي سجلات الحضور</h4>
            <div class="stat-value"><?php echo formatNumber($total_records); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>متوسط الحضور اليومي</h4>
            <div class="stat-value"><?php echo formatNumber($avg_daily); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>عدد الأيام</h4>
            <div class="stat-value"><?php echo formatNumber(count($attendance_counts)); ?></div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>اتجاه الحضور اليومي</h3>
            <canvas id="attendanceTrendChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>أكثر الموظفين حضوراً</h3>
            <canvas id="topEmployeesChart"></canvas>
        </div>
    </div>

    <!-- جدول الموظفين الأكثر حضوراً -->
    <div class="report-table-container">
        <h3>أكثر الموظفين حضوراً</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>الترتيب</th>
                    <th>كود الموظف</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th>عدد أيام الحضور</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_employees)): ?>
                    <tr>
                        <td colspan="5" class="text-center">لا توجد بيانات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($top_employees as $index => $emp): ?>
                        <tr>
                            <td><strong>#<?php echo $index + 1; ?></strong></td>
                            <td><?php echo htmlspecialchars($emp['employee_code']); ?></td>
                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></td>
                            <td><strong><?php echo formatNumber($emp['attendance_count']); ?></strong></td>
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

// رسم بياني خطي لاتجاه الحضور
const ctx1 = document.getElementById('attendanceTrendChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'عدد سجلات الحضور',
            data: <?php echo json_encode($attendance_counts); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
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
                        return 'سجلات الحضور: ' + context.parsed.y;
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

// رسم بياني عمودي لأكثر الموظفين حضوراً
const topEmpNames = <?php echo json_encode(array_map(function($e) { return $e['first_name'] . ' ' . $e['last_name']; }, $top_employees), JSON_UNESCAPED_UNICODE); ?>;
const topEmpCounts = <?php echo json_encode(array_column($top_employees, 'attendance_count')); ?>;

const ctx2 = document.getElementById('topEmployeesChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: topEmpNames,
        datasets: [{
            label: 'أيام الحضور',
            data: topEmpCounts,
            backgroundColor: 'rgba(118, 75, 162, 0.8)',
            borderColor: '#764ba2',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                rtl: true,
                callbacks: {
                    label: function(context) {
                        return 'أيام الحضور: ' + context.parsed.x;
                    }
                }
            }
        },
        scales: {
            x: {
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

