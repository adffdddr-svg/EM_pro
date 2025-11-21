<?php
/**
 * Employee Management System
 * حذف سجل
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/records/index.php');
}

if (deleteRecord($id)) {
    $_SESSION['success'] = 'تم حذف السجل بنجاح';
} else {
    $_SESSION['error'] = 'حدث خطأ أثناء حذف السجل';
}

redirect(SITE_URL . '/admin/records/index.php');

