<?php
/**
 * Employee Management System
 * تسجيل الخروج
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// تسجيل الخروج
logout();

// إعادة التوجيه إلى صفحة تسجيل الدخول
redirect(SITE_URL . '/auth/login.php');

