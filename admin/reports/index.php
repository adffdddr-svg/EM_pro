<?php
/**
 * Employee Management System
 * صفحة التقارير الرئيسية
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'التقارير والإحصائيات';
$additional_css = ['reports.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="reports-dashboard">
    <div class="reports-header">
        <h1 class="page-title">
            <span class="title-icon">📊</span>
            التقارير والإحصائيات
        </h1>
        <p class="page-description">عرض وتحليل البيانات الشاملة لنظام إدارة الموظفين</p>
    </div>

    <div class="reports-grid">
        <a href="<?php echo SITE_URL; ?>/admin/reports/employees.php" class="report-card">
            <div class="report-icon">👥</div>
            <h3>تقارير الموظفين</h3>
            <p>إحصائيات وتوزيع الموظفين حسب القسم والمنصب</p>
            <div class="report-features">
                <span class="feature-badge">توزيع</span>
                <span class="feature-badge">إحصائيات</span>
                <span class="feature-badge">رسوم بيانية</span>
            </div>
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/reports/attendance.php" class="report-card">
            <div class="report-icon">⏰</div>
            <h3>تقارير الحضور</h3>
            <p>معدلات الحضور والانصراف والتحليلات التفصيلية</p>
            <div class="report-features">
                <span class="feature-badge">معدلات</span>
                <span class="feature-badge">اتجاهات</span>
                <span class="feature-badge">مقارنات</span>
            </div>
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/reports/leaves.php" class="report-card">
            <div class="report-icon">📅</div>
            <h3>تقارير الإجازات</h3>
            <p>إحصائيات الإجازات والرصيد والتوزيع الزمني</p>
            <div class="report-features">
                <span class="feature-badge">أنواع</span>
                <span class="feature-badge">رصيد</span>
                <span class="feature-badge">تحليل</span>
            </div>
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/reports/salaries.php" class="report-card">
            <div class="report-icon">💰</div>
            <h3>تقارير الرواتب</h3>
            <p>توزيع الرواتب والتغييرات والمقارنات</p>
            <div class="report-features">
                <span class="feature-badge">توزيع</span>
                <span class="feature-badge">متوسط</span>
                <span class="feature-badge">تغييرات</span>
            </div>
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/reports/departments.php" class="report-card">
            <div class="report-icon">🏢</div>
            <h3>تقارير الأقسام</h3>
            <p>إحصائيات شاملة للأقسام والمقارنات</p>
            <div class="report-features">
                <span class="feature-badge">شامل</span>
                <span class="feature-badge">مقارنة</span>
                <span class="feature-badge">تحليل</span>
            </div>
        </a>
    </div>

    <div class="quick-stats">
        <h2>إحصائيات سريعة</h2>
        <div class="stats-grid">
            <?php
            $db = getDB();
            
            // إجمالي الموظفين
            $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
            $total_employees = $stmt->fetch()['total'];
            
            // إجمالي الأقسام
            $stmt = $db->query("SELECT COUNT(*) as total FROM departments");
            $total_departments = $stmt->fetch()['total'];
            
            // إجمالي الحضور هذا الشهر
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE MONTH(attendance_date) = MONTH(CURRENT_DATE()) AND YEAR(attendance_date) = YEAR(CURRENT_DATE())");
                $total_attendance = $stmt->fetch()['total'];
            } catch (PDOException $e) {
                $total_attendance = 0;
            }
            
            // إجمالي الإجازات المعلقة
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM employee_leaves WHERE status = 'pending'");
                $pending_leaves = $stmt->fetch()['total'];
            } catch (PDOException $e) {
                $pending_leaves = 0;
            }
            ?>
            
            <div class="quick-stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_employees; ?></div>
                <div class="stat-label">الموظفين النشطين</div>
            </div>
            
            <div class="quick-stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-value"><?php echo $total_departments; ?></div>
                <div class="stat-label">الأقسام</div>
            </div>
            
            <div class="quick-stat-card">
                <div class="stat-icon">⏰</div>
                <div class="stat-value"><?php echo $total_attendance; ?></div>
                <div class="stat-label">سجلات الحضور (هذا الشهر)</div>
            </div>
            
            <div class="quick-stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo $pending_leaves; ?></div>
                <div class="stat-label">إجازات معلقة</div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
