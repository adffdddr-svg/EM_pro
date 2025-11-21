# إصلاح مشكلة الأدوار والصلاحيات
# Fix Roles & Permissions Issue

## المشكلة / Problem
"لم يظهر شيئ" - العناصر المشروطة لا تظهر

## الحلول الممكنة / Possible Solutions

### 1. تأكد من تسجيل الدخول مرة أخرى
**بعد تحديث الكود، يجب تسجيل الخروج ثم تسجيل الدخول مرة أخرى**

After updating the code, you must logout and login again

**الخطوات:**
1. اذهب إلى: `http://localhost/EM_pro/auth/logout.php`
2. ثم سجل دخول مرة أخرى: `http://localhost/EM_pro/auth/login.php`

### 2. تحديث قاعدة البيانات
**قم بتشغيل ملف SQL لتحديث الأدوار:**

Run the SQL file to update roles:

```sql
-- في phpMyAdmin أو MySQL
USE employee_management;
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee') DEFAULT 'admin';
UPDATE users SET role = 'employee' WHERE role = 'hr';
```

أو استخدم الملف: `database/update_roles.sql`

### 3. اختبار النظام
**افتح صفحة الاختبار:**

Open test page:
`http://localhost/EM_pro/test_roles.php`

هذه الصفحة ستعرض:
- حالة تسجيل الدخول
- الدور في الجلسة
- نتائج اختبار الدوال
- معلومات من قاعدة البيانات

### 4. التحقق من الكود
**تأكد من أن `includes/auth.php` يحتوي على:**

Make sure `includes/auth.php` contains:

```php
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}
```

### 5. التحقق من Session
**تأكد من أن Session يعمل:**

Make sure Session is working:

في `config/config.php` يجب أن يكون:
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### 6. مثال على الاستخدام الصحيح

```php
<?php
// في بداية الصفحة
define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin(); // أو requireAdmin() للصفحات المحمية
?>

<!-- في HTML -->
<?php if (isAdmin()): ?>
    <p>هذا يظهر فقط للمديرين</p>
<?php endif; ?>

<?php if (isEmployee()): ?>
    <p>هذا يظهر فقط للموظفين</p>
<?php endif; ?>
```

## خطوات التشخيص / Diagnostic Steps

1. **افتح صفحة الاختبار:**
   `http://localhost/EM_pro/test_roles.php`

2. **تحقق من:**
   - هل تم تسجيل الدخول؟
   - ما هو الدور في الجلسة؟
   - ما هو الدور في قاعدة البيانات؟
   - هل الدوال تعمل بشكل صحيح؟

3. **إذا كان الدور غير موجود:**
   - سجل خروج
   - سجل دخول مرة أخرى
   - الدور سيتم حفظه تلقائياً في الجلسة

4. **إذا كان الدور مختلفاً:**
   - حدث قاعدة البيانات
   - سجل خروج
   - سجل دخول مرة أخرى

## ملاحظات مهمة / Important Notes

- **يجب تسجيل الخروج والدخول مرة أخرى** بعد تحديث الكود
- **الدور يتم حفظه تلقائياً** عند تسجيل الدخول في `includes/auth.php` السطر 35
- **الدور الافتراضي** في قاعدة البيانات هو `admin`
- **صفحة الاختبار** `test_roles.php` تساعدك في تشخيص المشكلة

