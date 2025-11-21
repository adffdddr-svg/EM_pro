<?php
/**
 * Employee Management System
 * التحقق من المصادقة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// التحقق من تسجيل الدخول
requireLogin();

