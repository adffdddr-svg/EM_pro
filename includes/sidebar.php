<?php
/**
 * Employee Management System
 * القائمة الجانبية
 */

// منع الوصول المباشر
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo SITE_NAME; ?></h2>
        <p class="subtitle">جامعة البصرة</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <?php if (isAdmin()): ?>
                <!-- روابط المدير -->
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>لوحة التحكم</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'employees') !== false && basename($_SERVER['PHP_SELF']) != 'archive.php') ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>الموظفين</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/employees/add.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'add.php') ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>إضافة موظف</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/leaves/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'leaves') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>الإجازات</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/salaries/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'salaries') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>الرواتب</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'attendance') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>الحضور والانصراف</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/records/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'records') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>السجلات</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/reports/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'reports') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>التقارير</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/employees/archive.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'archive.php') ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                            <rect x="1" y="3" width="22" height="5"></rect>
                            <line x1="10" y1="12" x2="14" y2="12"></line>
                        </svg>
                        <span>الأرشيف</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
                        </svg>
                        <span>الإعدادات</span>
                    </a>
                </li>
            <?php elseif (isEmployee()): ?>
                <!-- روابط الموظف -->
                <li>
                    <a href="<?php echo SITE_URL; ?>/employee/profile.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'profile.php') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>الملف الشخصي</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/employee/leaves/my_leaves.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'leaves') !== false) ? 'active' : ''; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>إجازاتي</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

