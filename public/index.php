<?php
/**
 * Employee Management System
 * إعادة توجيه من /public إلى المسار الصحيح
 */

// تحديد المسار الصحيح بناءً على الـ host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// كشف Cloudflare Tunnel domain أو empro.local
$is_cloudflare = (strpos($host, 'trycloudflare.com') !== false || 
                  strpos($host, 'cloudflare') !== false);
$is_empro_local = ($host === 'empro.local');

// تحديد المسار الصحيح
if ($is_cloudflare || $is_empro_local) {
    $base_url = $protocol . '://' . $host;
} elseif ($host === 'localhost' || $host === '127.0.0.1') {
    $base_url = $protocol . '://' . $host . '/EM_pro';
} else {
    $base_url = $protocol . '://' . $host;
}

// إعادة توجيه إلى صفحة تسجيل الدخول الصحيحة
header('Location: ' . $base_url . '/auth/login.php');
exit();

