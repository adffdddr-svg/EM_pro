<?php
/**
 * Employee Management System
 * تقارير الأقسام
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

// الحصول على البيانات
$dept_stats = getDepartmentComprehensiveStats($department_id);
$departments = getAllDepartments();

// تحضير البيانات للرسوم البيانية
$dept_names = array_column($dept_stats, 'name');
$total_employees = array_column($dept_stats, 'total_employees');
$total_salaries = array_map(function($s) { return (float)$s['total_salary']; }, $dept_stats);

$page_title = 'تقارير الأقسام';
$additional_css = ['reports.css'];
$additional_js = ['reports.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="report-page">
    <div class="report-page-header">
        <h1>
            <span>🏢</span>
            تقارير الأقسام
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
                <button type="submit" class="btn btn-primary">تصفية</button>
            </div>
        </form>
    </div>

    <!-- الرسوم البيانية -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>توزيع الموظفين حسب القسم</h3>
            <canvas id="employeesByDeptChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>إجمالي الرواتب حسب القسم</h3>
            <canvas id="salariesByDeptChart"></canvas>
        </div>
    </div>

    <!-- جدول التفاصيل الشاملة -->
    <div class="report-table-container">
        <h3>إحصائيات شاملة للأقسام</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>إجمالي الموظفين</th>
                    <th>الموظفين النشطين</th>
                    <th>إجمالي الرواتب</th>
                    <th>متوسط الراتب</th>
                    <th>سجلات الحضور (30 يوم)</th>
                    <th>إجازات معلقة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dept_stats)): ?>
                    <tr>
                        <td colspan="7" class="text-center">لا توجد بيانات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dept_stats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                            <td><?php echo formatNumber($stat['total_employees']); ?></td>
                            <td><strong><?php echo formatNumber($stat['active_employees']); ?></strong></td>
                            <td><strong><?php echo formatCurrency($stat['total_salary']); ?></strong></td>
                            <td><?php echo formatCurrency($stat['avg_salary']); ?></td>
                            <td><?php echo formatNumber($stat['attendance_count_30d']); ?></td>
                            <td><?php echo formatNumber($stat['pending_leaves']); ?></td>
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

// رسم بياني دائري لتوزيع الموظفين
const ctx1 = document.getElementById('employeesByDeptChart').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'عدد الموظفين',
            data: <?php echo json_encode($total_employees); ?>,
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
                rtl: true
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

// رسم بياني عمودي لإجمالي الرواتب
const ctx2 = document.getElementById('salariesByDeptChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($dept_names, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'إجمالي الرواتب',
            data: <?php echo json_encode($total_salaries); ?>,
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
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

