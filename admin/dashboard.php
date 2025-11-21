<?php
/**
 * Employee Management System
 * لوحة التحكم الرئيسية
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// توجيه الموظف إلى صفحة الملف الشخصي
if (isEmployee()) {
    redirect(SITE_URL . '/employee/profile.php');
}

// باقي الكود للمدير فقط
requireAdmin();

$db = getDB();

// الحصول على الإحصائيات
$stats = [];

// إجمالي الموظفين النشطين
$stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
$stats['total_employees'] = $stmt->fetch()['total'];

// الموظفين في الأرشيف
$stmt = $db->query("SELECT COUNT(*) as total FROM employees_archive");
$stats['archived_employees'] = $stmt->fetch()['total'];

// الموظفين الجدد هذا الشهر
$stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['new_this_month'] = $stmt->fetch()['total'];

// إجمالي الأقسام
$stmt = $db->query("SELECT COUNT(*) as total FROM departments");
$stats['total_departments'] = $stmt->fetch()['total'];

// إحصائيات إضافية
try {
    // إجمالي الإجازات المعلقة
    $stmt = $db->query("SELECT COUNT(*) as total FROM employee_leaves WHERE status = 'pending'");
    $stats['pending_leaves'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $stats['pending_leaves'] = 0;
}

try {
    // إجمالي سجلات الحضور اليوم
    $stmt = $db->query("SELECT COUNT(*) as total FROM attendance WHERE attendance_date = CURDATE()");
    $stats['today_attendance'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $stats['today_attendance'] = 0;
}

try {
    // إجمالي السجلات
    $stmt = $db->query("SELECT COUNT(*) as total FROM employee_records WHERE status = 'active'");
    $stats['total_records'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $stats['total_records'] = 0;
}

// آخر الموظفين المضافين
$stmt = $db->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.created_at DESC LIMIT 5");
$recent_employees = $stmt->fetchAll();

// الموظفين حسب القسم
$stmt = $db->query("SELECT d.name, COUNT(e.id) as count FROM departments d LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active' GROUP BY d.id, d.name ORDER BY count DESC");
$employees_by_department = $stmt->fetchAll();

// حساب الاتجاهات (Trends) - مقارنة الشهر الحالي بالشهر الماضي
try {
    // الموظفين الجدد الشهر الماضي
    $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
    $last_month_new = $stmt->fetch()['total'];
    $stats['new_trend'] = $last_month_new > 0 ? round((($stats['new_this_month'] - $last_month_new) / $last_month_new) * 100, 1) : 0;
} catch (PDOException $e) {
    $stats['new_trend'] = 0;
}

// النشاطات الأخيرة
$recent_activities = [];
try {
    // آخر الموظفين المضافين (كنشاطات)
    $stmt = $db->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.created_at DESC LIMIT 5");
    $recent_employees_activities = $stmt->fetchAll();
    foreach ($recent_employees_activities as $emp) {
        $recent_activities[] = [
            'type' => 'employee_added',
            'icon' => '➕',
            'message' => 'تم إضافة موظف جديد: ' . htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']),
            'time' => $emp['created_at'],
            'link' => SITE_URL . '/admin/employees/view.php?id=' . $emp['id']
        ];
    }
    
    // آخر الإجازات المطلوبة
    $stmt = $db->query("SELECT el.*, e.first_name, e.last_name FROM employee_leaves el LEFT JOIN employees e ON el.employee_id = e.id WHERE el.status = 'pending' ORDER BY el.created_at DESC LIMIT 3");
    $recent_leaves = $stmt->fetchAll();
    foreach ($recent_leaves as $leave) {
        $recent_activities[] = [
            'type' => 'leave_requested',
            'icon' => '📅',
            'message' => 'طلب إجازة من: ' . htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']),
            'time' => $leave['created_at'],
            'link' => SITE_URL . '/admin/leaves/view.php?id=' . $leave['id']
        ];
    }
    
    // ترتيب النشاطات حسب الوقت
    usort($recent_activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $recent_activities = array_slice($recent_activities, 0, 8);
} catch (PDOException $e) {
    // تجاهل الأخطاء
}

$page_title = 'لوحة التحكم';
$additional_css = ['dashboard.css'];
$additional_js = ['dashboard.js'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <script>
    // حذف فوري لأي بطاقة معلومات الشبكة قبل عرض الصفحة
    (function() {
        'use strict';
        // إضافة CSS فوري لإخفاء البطاقة
        const style = document.createElement('style');
        style.id = 'hide-network-info';
        style.textContent = `
            .network-info-card,
            [href*="network-info"],
            a[href*="network-info"],
            div:has(a[href*="network-info"]),
            *[class*="network-info"],
            *[id*="network-info"],
            a[href*="network-info.php"],
            div:has-text("معلومات الشبكة والوصول"),
            div:has-text("معلومات الشبكة") {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                height: 0 !important;
                width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                pointer-events: none !important;
                z-index: -9999 !important;
            }
        `;
        if (document.head) {
            document.head.appendChild(style);
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                document.head.appendChild(style);
            });
        }
        
        // دالة حذف البطاقة
        function removeNetworkCard() {
            const selectors = [
                '.network-info-card',
                '[href*="network-info"]',
                'a[href*="network-info"]',
                'div:has(a[href*="network-info"])',
                '*[class*="network-info"]',
                '*[id*="network-info"]'
            ];
            
            selectors.forEach(selector => {
                try {
                    document.querySelectorAll(selector).forEach(el => {
                        const text = el.textContent || el.innerText || '';
                        if (text.includes('معلومات الشبكة') || text.includes('network-info') || 
                            (el.href && el.href.includes('network-info'))) {
                            el.remove();
                        }
                    });
                } catch(e) {}
            });
            
            // البحث عن أي عنصر يحتوي على النص
            document.querySelectorAll('*').forEach(el => {
                const text = el.textContent || el.innerText || '';
                if ((text.includes('معلومات الشبكة والوصول') || text.includes('معلومات الشبكة')) &&
                    (el.querySelector('a[href*="network-info"]') || 
                     el.classList.contains('network-info-card') ||
                     (el.href && el.href.includes('network-info')))) {
                    el.remove();
                }
            });
        }
        
        // حذف فوري
        removeNetworkCard();
        
        // حذف عند تحميل DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeNetworkCard);
        } else {
            removeNetworkCard();
        }
        
        // حذف متكرر
        setTimeout(removeNetworkCard, 10);
        setTimeout(removeNetworkCard, 50);
        setTimeout(removeNetworkCard, 100);
        setTimeout(removeNetworkCard, 300);
        setTimeout(removeNetworkCard, 500);
        
        // مراقبة DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            const text = node.textContent || node.innerText || '';
                            if (text.includes('معلومات الشبكة') || text.includes('network-info')) {
                                const hasNetworkLink = (node.querySelector && node.querySelector('a[href*="network-info"]')) ||
                                                       (node.classList && node.classList.contains('network-info-card')) ||
                                                       (node.href && node.href.includes('network-info'));
                                if (hasNetworkLink) {
                                    node.remove();
                                }
                            }
                        }
                    });
                }
            });
            removeNetworkCard();
        });
        
        observer.observe(document.body || document.documentElement, {
            childList: true,
            subtree: true
        });
    })();
    </script>
    <style>
    /* إخفاء بطاقة معلومات الشبكة بشكل دائم */
    .network-info-card,
    [href*="network-info"],
    a[href*="network-info"],
    div:has(a[href*="network-info"]),
    *[class*="network-info"],
    *[id*="network-info"],
    a[href*="network-info.php"] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
    </style>
    <!-- الإحصائيات المحسّنة -->
    <div class="stats-grid">
        <div class="stat-card enhanced">
            <div class="stat-header">
                <div class="stat-icon">👥</div>
                <div class="stat-badge">نشط</div>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total_employees']; ?></div>
                <div class="stat-label">الموظفين النشطين</div>
                <div class="stat-footer">
                    <div class="stat-info">
                        <span class="stat-info-icon">📊</span>
                        <span>إجمالي الموظفين</span>
                    </div>
                </div>
            </div>
            <div class="stat-chart">
                <div class="mini-chart">
                    <div class="chart-bar" style="height: 85%"></div>
                    <div class="chart-bar" style="height: 92%"></div>
                    <div class="chart-bar" style="height: 78%"></div>
                    <div class="chart-bar" style="height: 100%"></div>
                    <div class="chart-bar" style="height: 88%"></div>
                </div>
            </div>
        </div>
        
        <div class="stat-card success enhanced">
            <div class="stat-header">
                <div class="stat-icon">✨</div>
                <div class="stat-trend <?php echo $stats['new_trend'] >= 0 ? 'up' : 'down'; ?>">
                    <span class="trend-icon"><?php echo $stats['new_trend'] >= 0 ? '↑' : '↓'; ?></span>
                    <span class="trend-value"><?php echo abs($stats['new_trend']); ?>%</span>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['new_this_month']; ?></div>
                <div class="stat-label">موظفين جدد هذا الشهر</div>
                <div class="stat-footer">
                    <div class="stat-info">
                        <span class="stat-info-icon">📈</span>
                        <span>مقارنة بالشهر الماضي</span>
                    </div>
                </div>
            </div>
            <div class="stat-chart">
                <div class="mini-chart line">
                    <svg viewBox="0 0 100 40" class="chart-line">
                        <polyline points="0,30 20,25 40,20 60,15 80,10 100,5" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="stat-card warning enhanced">
            <div class="stat-header">
                <div class="stat-icon">📦</div>
                <div class="stat-badge warning">أرشيف</div>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['archived_employees']; ?></div>
                <div class="stat-label">في الأرشيف</div>
                <div class="stat-footer">
                    <div class="stat-info">
                        <span class="stat-info-icon">🗄️</span>
                        <span>موظفين مؤرشفين</span>
                    </div>
                </div>
            </div>
            <div class="stat-chart">
                <div class="mini-chart pie">
                    <div class="pie-chart" style="--percentage: <?php echo $stats['total_employees'] > 0 ? round(($stats['archived_employees'] / ($stats['total_employees'] + $stats['archived_employees'])) * 100) : 0; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="stat-card danger enhanced">
            <div class="stat-header">
                <div class="stat-icon">🏢</div>
                <div class="stat-badge info">أقسام</div>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total_departments']; ?></div>
                <div class="stat-label">إجمالي الأقسام</div>
                <div class="stat-footer">
                    <div class="stat-info">
                        <span class="stat-info-icon">📋</span>
                        <span>أقسام نشطة</span>
                    </div>
                </div>
            </div>
            <div class="stat-chart">
                <div class="mini-chart">
                    <div class="chart-dots">
                        <span></span><span></span><span></span>
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- إحصائيات إضافية -->
    <?php if (isset($stats['pending_leaves']) || isset($stats['today_attendance'])): ?>
    <div class="stats-grid secondary">
        <?php if (isset($stats['pending_leaves'])): ?>
        <div class="stat-card mini info">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['pending_leaves']; ?></div>
                <div class="stat-label">إجازات معلقة</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($stats['today_attendance'])): ?>
        <div class="stat-card mini primary">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['today_attendance']; ?></div>
                <div class="stat-label">حضور اليوم</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($stats['total_records'])): ?>
        <div class="stat-card mini secondary">
            <div class="stat-icon">📁</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total_records']; ?></div>
                <div class="stat-label">السجلات النشطة</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- النشاطات الأخيرة -->
    <?php if (!empty($recent_activities)): ?>
    <div class="card activity-feed-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="card-title-icon">⚡</span>
                النشاطات الأخيرة
            </h3>
            <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-sm btn-link">عرض الكل</a>
        </div>
        <div class="activity-feed">
            <?php foreach ($recent_activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon"><?php echo $activity['icon']; ?></div>
                <div class="activity-content">
                    <p class="activity-message"><?php echo $activity['message']; ?></p>
                    <span class="activity-time"><?php echo formatDate($activity['time'], 'Y-m-d H:i'); ?></span>
                </div>
                <?php if (isset($activity['link'])): ?>
                <a href="<?php echo $activity['link']; ?>" class="activity-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- آخر الموظفين -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">آخر الموظفين المضافين</h3>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-sm btn-primary">عرض الكل</a>
            </div>
            
            <?php if (count($recent_employees) > 0): ?>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_employees as $employee): ?>
                                <tr>
                                    <td>
                                        <?php if ($employee['photo']): ?>
                                            <img src="<?php echo UPLOAD_URL . $employee['photo']; ?>" alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #999;">بدون</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['department_name'] ?? 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td><?php echo formatDate($employee['hire_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>لا توجد موظفين بعد</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- الموظفين حسب القسم -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 الموظفين حسب القسم</h3>
            </div>
            
            <?php if (count($employees_by_department) > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>القسم</th>
                                <th>عدد الموظفين</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees_by_department as $dept): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                    <td><?php echo $dept['count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>لا توجد أقسام</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- روابط سريعة -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">روابط سريعة</h3>
        </div>
        <div class="quick-links">
            <?php if (isAdmin()): ?>
                <a href="<?php echo SITE_URL; ?>/admin/employees/add.php" class="quick-link-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="link-icon">➕</div>
                    <div class="link-text">إضافة موظف جديد</div>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="quick-link-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="link-icon">👥</div>
                    <div class="link-text">عرض جميع الموظفين</div>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php" class="quick-link-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="link-icon">📦</div>
                    <div class="link-text">الأرشيف</div>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="quick-link-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="link-icon">⏰</div>
                    <div class="link-text">الحضور والانصراف</div>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="quick-link-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="link-icon">📁</div>
                    <div class="link-text">السجلات</div>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- مثال: قسم خاص بالموظفين فقط -->
    <?php if (isEmployee()): ?>
    <div class="card" style="border: 2px solid #3498db; background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(155, 89, 182, 0.1) 100%);">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-circle"></i> قسم الموظفين
            </h3>
        </div>
        <div style="padding: 20px;">
            <p style="font-size: 16px; color: #2c3e50; margin-bottom: 15px;">
                <strong>مرحباً بك كموظف!</strong>
            </p>
            <p style="color: #666; line-height: 1.8;">
                هذا القسم مرئي فقط للموظفين. يمكنك هنا:
            </p>
            <ul style="color: #666; line-height: 2; margin-top: 10px; padding-right: 25px;">
                <li>عرض معلوماتك الشخصية</li>
                <li>الاستفسار عن راتبك</li>
                <li>التحقق من إجازاتك</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- مثال: قسم خاص بالمديرين فقط -->
    <?php if (isAdmin()): ?>
    <div class="card" style="border: 2px solid #e74c3c; background: linear-gradient(135deg, rgba(231, 76, 60, 0.05) 0%, rgba(192, 57, 43, 0.05) 100%); border-radius: 20px; overflow: hidden;">
        <div class="card-header" style="background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);">
            <h3 class="card-title">
                🛡️ لوحة التحكم الإدارية
            </h3>
        </div>
        <div style="padding: 25px;">
            <p style="font-size: 18px; color: #2c3e50; margin-bottom: 15px; font-weight: 700;">
                👋 مرحباً بك كمدير!
            </p>
            <p style="color: #666; line-height: 1.8; margin-bottom: 15px;">
                هذا القسم مرئي فقط للمديرين. يمكنك هنا:
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
                <div style="padding: 15px; background: rgba(102, 126, 234, 0.05); border-radius: 10px; border-right: 3px solid #667eea;">
                    <strong style="color: #667eea;">👥</strong> إدارة جميع الموظفين
                </div>
                <div style="padding: 15px; background: rgba(17, 153, 142, 0.05); border-radius: 10px; border-right: 3px solid #11998e;">
                    <strong style="color: #11998e;">➕</strong> إضافة وتعديل وحذف الموظفين
                </div>
                <div style="padding: 15px; background: rgba(240, 147, 251, 0.05); border-radius: 10px; border-right: 3px solid #f093fb;">
                    <strong style="color: #f093fb;">📦</strong> عرض الأرشيف
                </div>
                <div style="padding: 15px; background: rgba(250, 112, 154, 0.05); border-radius: 10px; border-right: 3px solid #fa709a;">
                    <strong style="color: #fa709a;">🏢</strong> إدارة الأقسام
                </div>
                <div style="padding: 15px; background: rgba(79, 172, 254, 0.05); border-radius: 10px; border-right: 3px solid #4facfe;">
                    <strong style="color: #4facfe;">📊</strong> عرض جميع الإحصائيات
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

