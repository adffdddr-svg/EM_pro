# نظام الأدوار والصلاحيات - دليل الاستخدام
# Roles & Permissions System - Usage Guide

## نظرة عامة / Overview

تم إضافة نظام كامل للأدوار والصلاحيات إلى نظام إدارة الموظفين. يدعم النظام دورين:
- **admin** (مدير)
- **employee** (موظف)

A complete roles and permissions system has been added to the Employee Management System. The system supports two roles:
- **admin** (Administrator)
- **employee** (Employee)

---

## الملفات المعدلة / Modified Files

### 1. `includes/auth.php`
تمت إضافة الدوال التالية:
- `isAdmin()` - التحقق من أن المستخدم مدير
- `isEmployee()` - التحقق من أن المستخدم موظف
- `requireAdmin()` - حماية الصفحات التي تتطلب صلاحيات المدير

The following functions were added:
- `isAdmin()` - Check if user is admin
- `isEmployee()` - Check if user is employee
- `requireAdmin()` - Protect pages that require admin permissions

### 2. `auth/no_access.php`
صفحة جديدة تعرض رسالة "لا يوجد صلاحية" عند محاولة الوصول إلى صفحة محمية.

New page that displays "No Access" message when trying to access protected pages.

### 3. `admin/dashboard.php`
تمت إضافة أمثلة على:
- أزرار تظهر فقط للمديرين
- قسم خاص بالموظفين
- قسم خاص بالمديرين

Added examples of:
- Buttons visible only to admins
- Employee-only section
- Admin-only section

### 4. صفحات الإدارة / Admin Pages
تم تحديث جميع صفحات إدارة الموظفين لاستخدام `requireAdmin()`:
- `admin/employees/add.php`
- `admin/employees/edit.php`
- `admin/employees/delete.php`
- `admin/employees/archive.php`
- `admin/employees/index.php`

All employee management pages were updated to use `requireAdmin()`:
- `admin/employees/add.php`
- `admin/employees/edit.php`
- `admin/employees/delete.php`
- `admin/employees/archive.php`
- `admin/employees/index.php`

---

## كيفية الاستخدام / How to Use

### 1. التحقق من الدور في PHP / Check Role in PHP

```php
<?php
// التحقق من أن المستخدم مدير
if (isAdmin()) {
    // كود خاص بالمديرين
}

// التحقق من أن المستخدم موظف
if (isEmployee()) {
    // كود خاص بالموظفين
}
?>
```

### 2. حماية الصفحات / Protect Pages

```php
<?php
// في بداية الصفحة
requireAdmin(); // يتطلب صلاحيات المدير

// إذا حاول موظف الوصول، سيتم توجيهه تلقائياً إلى no_access.php
?>
```

### 3. إظهار/إخفاء العناصر في HTML / Show/Hide Elements in HTML

```php
<!-- زر يظهر فقط للمديرين -->
<?php if (isAdmin()): ?>
    <a href="add_employee.php" class="btn">إضافة موظف</a>
<?php endif; ?>

<!-- قسم خاص بالموظفين -->
<?php if (isEmployee()): ?>
    <div class="employee-section">
        <h3>مرحباً بك كموظف!</h3>
    </div>
<?php endif; ?>
```

### 4. التحقق من الدور في JavaScript (اختياري) / Check Role in JavaScript (Optional)

```javascript
// يمكنك تمرير الدور من PHP إلى JavaScript
const userRole = '<?php echo $_SESSION['role'] ?? ''; ?>';

if (userRole === 'admin') {
    // كود JavaScript للمديرين
}
```

---

## تحديث قاعدة البيانات / Database Update

تم إنشاء ملف SQL لتحديث قاعدة البيانات:
`database/update_roles.sql`

A SQL file was created to update the database:
`database/update_roles.sql`

**لتطبيق التحديثات / To apply updates:**

```sql
-- تشغيل الملف
SOURCE database/update_roles.sql;

-- أو نسخ المحتوى وتنفيذه مباشرة
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee') DEFAULT 'admin';
UPDATE users SET role = 'employee' WHERE role = 'hr';
```

---

## أمثلة عملية / Practical Examples

### مثال 1: زر إضافة موظف (للمديرين فقط)
### Example 1: Add Employee Button (Admin Only)

```php
<?php if (isAdmin()): ?>
    <a href="<?php echo SITE_URL; ?>/admin/employees/add.php" class="btn btn-success">
        <i class="fas fa-user-plus"></i> إضافة موظف جديد
    </a>
<?php endif; ?>
```

### مثال 2: قسم معلومات الموظف (للموظفين فقط)
### Example 2: Employee Info Section (Employee Only)

```php
<?php if (isEmployee()): ?>
    <div class="card">
        <h3>معلوماتك الشخصية</h3>
        <p>مرحباً <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
<?php endif; ?>
```

### مثال 3: صفحة محمية للمديرين فقط
### Example 3: Admin-Only Protected Page

```php
<?php
define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin(); // حماية الصفحة - يتطلب صلاحيات المدير

// باقي كود الصفحة...
?>
```

---

## الأمان / Security

1. **التحقق من الدور في Session**: يتم التحقق من الدور المخزن في `$_SESSION['role']`
2. **حماية الصفحات**: استخدام `requireAdmin()` في بداية الصفحات المحمية
3. **إخفاء العناصر**: استخدام `isAdmin()` و `isEmployee()` لإخفاء العناصر حسب الدور

1. **Role Check in Session**: Role is checked from `$_SESSION['role']`
2. **Page Protection**: Use `requireAdmin()` at the start of protected pages
3. **Hide Elements**: Use `isAdmin()` and `isEmployee()` to hide elements based on role

---

## ملاحظات مهمة / Important Notes

1. **تأكد من تحديث قاعدة البيانات**: قم بتشغيل `database/update_roles.sql` لتحديث جدول `users`
2. **الدور الافتراضي**: الدور الافتراضي للمستخدمين الجدد هو `admin`
3. **تسجيل الدخول**: يتم حفظ الدور تلقائياً في Session عند تسجيل الدخول

1. **Update Database**: Run `database/update_roles.sql` to update the `users` table
2. **Default Role**: Default role for new users is `admin`
3. **Login**: Role is automatically saved in Session on login

---

## الدوال المتاحة / Available Functions

| Function | Description | Usage |
|----------|-------------|-------|
| `isAdmin()` | Returns true if user is admin | `if (isAdmin()) { ... }` |
| `isEmployee()` | Returns true if user is employee | `if (isEmployee()) { ... }` |
| `requireAdmin()` | Redirects to no_access.php if not admin | `requireAdmin();` |
| `hasRole($role)` | Check if user has specific role | `if (hasRole('admin')) { ... }` |

---

## الدعم / Support

إذا واجهت أي مشاكل أو لديك أسئلة، يرجى مراجعة:
- ملف `includes/auth.php` للدوال
- ملف `auth/no_access.php` لصفحة عدم الصلاحية
- ملف `admin/dashboard.php` للأمثلة

If you encounter any issues or have questions, please refer to:
- `includes/auth.php` for functions
- `auth/no_access.php` for no access page
- `admin/dashboard.php` for examples

