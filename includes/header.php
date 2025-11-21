<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo SITE_URL; ?>/assets/images/icon-72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="<?php echo SITE_URL; ?>/assets/images/icon-96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="<?php echo SITE_URL; ?>/assets/images/icon-128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo SITE_URL; ?>/assets/images/icon-144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo SITE_URL; ?>/assets/images/icon-152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo SITE_URL; ?>/assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="384x384" href="<?php echo SITE_URL; ?>/assets/images/icon-384.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?php echo SITE_URL; ?>/assets/images/icon-512.png">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL . '/assets/css/' . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php 
    // التحقق من Remember Token قبل التحقق من تسجيل الدخول
    if (function_exists('checkRememberToken')) {
        checkRememberToken();
    }
    ?>
    
    <?php if (isLoggedIn()): ?>
    <!-- قائمة الموبايل -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    
    <div class="wrapper">
        <!-- القائمة الجانبية -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <!-- شريط التنقل العلوي -->
            <header class="top-header">
                <div class="header-content">
                    <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'لوحة التحكم'; ?></h1>
                    <div class="user-menu">
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="<?php echo SITE_URL; ?>/admin/profile.php" class="btn-icon" title="الملف الشخصي">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn-icon" title="تسجيل الخروج">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- المحتوى -->
            <main class="content">
    <?php else: ?>
    <div class="auth-wrapper">
    <?php endif; ?>

