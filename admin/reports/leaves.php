<?php
/**
 * Employee Management System
 * تقارير الإجازات
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
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

// الحصول على البيانات
$leaves_by_type = getLeavesByType(date($year . '-01-01'), date($year . '-12-31'));
$leaves_by_month = getLeavesByMonth($year);
$departments = getAllDepartments();

// تحضير البيانات للرسوم البيانية
$leave_types = array_column($leaves_by_type, 'leave_type');
$leave_counts = array_column($leaves_by_type, 'total_count');
$approved_counts = array_column($leaves_by_type, 'approved_count');

// بيانات الإجازات حسب الشهر
$arabic_months = getArabicMonthNames();
$months = [];
$monthly_leaves = [];
$monthly_approved = [];

for ($i = 1; $i <= 12; $i++) {
    $months[] = $arabic_months[$i];
    $found = false;
    foreach ($leaves_by_month as $stat) {
        if ($stat['month'] == $i) {
            $monthly_leaves[] = (int)$stat['total_count'];
            $monthly_approved[] = (int)$stat['approved_count'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $monthly_leaves[] = 0;
        $monthly_approved[] = 0;
    }
}

// إحصائيات إضافية
$stmt = $db->prepare("SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                     FROM employee_leaves
                     WHERE YEAR(start_date) = ?");
$stmt->execute([$year]);
$overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'تقارير الإجازات';
$additional_css = ['reports.css'];
$additional_js = ['reports.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="report-page">
    <div class="report-page-header">
        <h1>
            <span>📅</span>
            تقارير الإجازات
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
            <h4>إجمالي طلبات الإجازة</h4>
            <div class="stat-value"><?php echo formatNumber($overall_stats['total'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>الموافق عليها</h4>
            <div class="stat-value" style="color: #27ae60;"><?php echo formatNumber($overall_stats['approved'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>المعلقة</h4>
            <div class="stat-value" style="color: #f39c12;"><?php echo formatNumber($overall_stats['pending'] ?? 0); ?></div>
        </div>
        <div class="report-stat-card">
            <h4>المرفوضة</h4>
            <div class="stat-value" style="color: #e74c3c;"><?php echo formatNumber($overall_stats['rejected'] ?? 0); ?></div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="charts-container">
        <div class="chart-card">
            <h3>توزيع الإجازات حسب النوع</h3>
            <canvas id="leavesByTypeChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>الإجازات حسب الشهر (<?php echo $year; ?>)</h3>
            <canvas id="leavesByMonthChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>الإجازات الموافق عليها حسب النوع</h3>
            <canvas id="approvedLeavesChart"></canvas>
        </div>
    </div>

    <!-- جدول التفاصيل -->
    <div class="report-table-container">
        <h3>تفاصيل الإجازات حسب النوع</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>نوع الإجازة</th>
                    <th>إجمالي الطلبات</th>
                    <th>موافق عليها</th>
                    <th>معلقة</th>
                    <th>مرفوضة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves_by_type)): ?>
                    <tr>
                        <td colspan="5" class="text-center">لا توجد بيانات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaves_by_type as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['leave_type']); ?></strong></td>
                            <td><?php echo formatNumber($stat['total_count']); ?></td>
                            <td style="color: #27ae60;"><strong><?php echo formatNumber($stat['approved_count']); ?></strong></td>
                            <td style="color: #f39c12;"><strong><?php echo formatNumber($stat['pending_count']); ?></strong></td>
                            <td style="color: #e74c3c;"><strong><?php echo formatNumber($stat['rejected_count']); ?></strong></td>
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

// رسم بياني دائري للإجازات حسب النوع
const ctx1 = document.getElementById('leavesByTypeChart').getContext('2d');
new Chart(ctx1, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($leave_types, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'عدد الإجازات',
            data: <?php echo json_encode($leave_counts); ?>,
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
                rtl: true
            }
        }
    }
});

// رسم بياني عمودي للإجازات حسب الشهر
const ctx2 = document.getElementById('leavesByMonthChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [
            {
                label: 'إجمالي الإجازات',
                data: <?php echo json_encode($monthly_leaves); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 2
            },
            {
                label: 'الموافق عليها',
                data: <?php echo json_encode($monthly_approved); ?>,
                backgroundColor: 'rgba(39, 174, 96, 0.8)',
                borderColor: '#27ae60',
                borderWidth: 2
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

// رسم بياني عمودي للإجازات الموافق عليها
const ctx3 = document.getElementById('approvedLeavesChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($leave_types, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'الموافق عليها',
            data: <?php echo json_encode($approved_counts); ?>,
            backgroundColor: 'rgba(39, 174, 96, 0.8)',
            borderColor: '#27ae60',
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

