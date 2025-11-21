# نظام الإجازات - دليل الاستخدام
## Leaves Management System Guide

---

## ✅ ما تم تنفيذه

### 1. قاعدة البيانات
- ✅ جدول `employee_leaves` - تخزين الإجازات
- ✅ جدول `leave_approvals` - سجل الموافقات
- ✅ جدول `leave_balance` - رصيد الإجازات

### 2. أنواع الإجازات
- ✅ إجازة اعتيادية (Ordinary Leave)
- ✅ إجازة زمنية (Time Leave)
- ✅ فحص طبي (Medical Examination)
- ✅ إجازة طارئة (Emergency)
- ✅ إجازة بدون راتب (Unpaid)

### 3. صفحات المدير
- ✅ `admin/leaves/index.php` - قائمة جميع الإجازات
- ✅ `admin/leaves/add.php` - إضافة إجازة جديدة
- ✅ `admin/leaves/view.php` - عرض تفاصيل الإجازة
- ✅ `admin/leaves/approve.php` - موافقة/رفض الإجازة
- ✅ `admin/leaves/get_balance.php` - API للحصول على الرصيد

### 4. صفحات الموظف
- ✅ `employee/leaves/my_leaves.php` - إجازاتي
- ✅ `employee/leaves/request.php` - طلب إجازة جديدة
- ✅ `employee/leaves/cancel.php` - إلغاء الإجازة

### 5. الدوال المساعدة
- ✅ `getLeaveBalance()` - الحصول على رصيد الإجازات
- ✅ `updateLeaveBalance()` - تحديث الرصيد
- ✅ `getLeaveTypes()` - أنواع الإجازات
- ✅ `calculateLeaveDays()` - حساب عدد الأيام
- ✅ `hasLeaveConflict()` - التحقق من التعارض

### 6. التكامل
- ✅ تحديث `includes/sidebar.php` - إضافة روابط الإجازات
- ✅ تحديث `bot/api/leaves.php` - استخدام الجداول الجديدة
- ✅ تحديث `includes/functions.php` - إضافة دوال الإجازات

---

## 📋 خطوات التثبيت

### الخطوة 1: تحديث قاعدة البيانات

قم بتشغيل ملف SQL التالي في phpMyAdmin:

```sql
-- ملف: database/leaves_schema.sql
```

أو قم بتشغيل الأوامر التالية يدوياً:

```sql
USE employee_management;

-- 1. إنشاء جدول الإجازات
CREATE TABLE IF NOT EXISTS employee_leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('ordinary', 'time', 'medical', 'emergency', 'unpaid') NOT NULL DEFAULT 'ordinary',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    days DECIMAL(5, 2) NOT NULL DEFAULT 0,
    purpose TEXT,
    substitute_employee_id INT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. إنشاء جدول الموافقات
CREATE TABLE IF NOT EXISTS leave_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_id INT NOT NULL,
    approver_type ENUM('leave_unit', 'direct_supervisor', 'assistant_dean') NOT NULL,
    approver_id INT NULL,
    approver_name VARCHAR(100) NOT NULL,
    approver_position VARCHAR(100) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_id) REFERENCES employee_leaves(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. إنشاء جدول رصيد الإجازات
CREATE TABLE IF NOT EXISTS leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL UNIQUE,
    total_balance INT NOT NULL DEFAULT 0,
    monthly_balance INT NOT NULL DEFAULT 2,
    remaining_balance INT NOT NULL DEFAULT 0,
    used_this_year INT NOT NULL DEFAULT 0,
    last_reset_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. إدراج رصيد افتراضي للموظفين الموجودين
INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance)
SELECT id, 104, 2, 104 FROM employees
WHERE id NOT IN (SELECT employee_id FROM leave_balance)
ON DUPLICATE KEY UPDATE total_balance = total_balance;
```

### الخطوة 2: اختبار النظام

1. **اختبار إضافة إجازة (مدير):**
   - افتح: `http://localhost/EM_pro/admin/leaves/add.php`
   - اختر موظف ونوع الإجازة
   - أدخل التواريخ
   - احفظ الإجازة

2. **اختبار طلب إجازة (موظف):**
   - سجل دخول كموظف
   - افتح: `http://localhost/EM_pro/employee/leaves/request.php`
   - املأ النموذج
   - أرسل الطلب

3. **اختبار الموافقة:**
   - سجل دخول كمدير
   - افتح قائمة الإجازات
   - اضغط على "موافقة" أو "رفض"

---

## 📝 استخدام النظام

### للمدير:
1. **إضافة إجازة:**
   - اذهب إلى: `admin/leaves/add.php`
   - اختر الموظف ونوع الإجازة
   - أدخل التواريخ والغرض
   - احفظ

2. **الموافقة على الإجازات:**
   - اذهب إلى: `admin/leaves/index.php`
   - اضغط على "موافقة" أو "رفض"
   - أدخل سبب الرفض (إذا كان رفض)

3. **عرض تفاصيل الإجازة:**
   - اضغط على أيقونة "عرض" في القائمة
   - شاهد جميع التفاصيل والموافقات

### للموظف:
1. **طلب إجازة:**
   - اذهب إلى: `employee/leaves/request.php`
   - اختر نوع الإجازة
   - أدخل التواريخ والغرض
   - أرسل الطلب

2. **عرض إجازاتي:**
   - اذهب إلى: `employee/leaves/my_leaves.php`
   - شاهد رصيد الإجازات
   - شاهد سجل الإجازات

3. **إلغاء إجازة:**
   - من صفحة "إجازاتي"
   - اضغط على "إلغاء" بجانب الإجازة المعلقة

---

## 🔧 الملفات المضافة

### ملفات SQL:
- `database/leaves_schema.sql` - إنشاء الجداول

### صفحات PHP:
- `admin/leaves/index.php` - قائمة الإجازات
- `admin/leaves/add.php` - إضافة إجازة
- `admin/leaves/view.php` - عرض تفاصيل
- `admin/leaves/approve.php` - موافقة/رفض
- `admin/leaves/get_balance.php` - API للرصيد
- `employee/leaves/my_leaves.php` - إجازاتي
- `employee/leaves/request.php` - طلب إجازة
- `employee/leaves/cancel.php` - إلغاء إجازة

### ملفات محدثة:
- `includes/functions.php` - دوال الإجازات
- `includes/sidebar.php` - روابط الإجازات
- `bot/api/leaves.php` - استخدام الجداول الجديدة

---

## 📊 أنواع الإجازات

| النوع | الوصف | الرصيد |
|------|------|--------|
| إجازة اعتيادية | إجازة سنوية | يخصم من الرصيد |
| إجازة زمنية | إجازة لساعات محددة | يخصم حسب الساعات |
| فحص طبي | للفحوصات الطبية | لا يخصم |
| إجازة طارئة | للطوارئ | يخصم من الرصيد |
| إجازة بدون راتب | إجازة غير مدفوعة | لا يخصم |

---

## ⚠️ ملاحظات مهمة

1. **الرصيد الافتراضي:** كل موظف جديد يحصل على 104 يوم رصيد كلي و 2 يوم شهري.

2. **الإجازة الزمنية:** تستخدم الساعات (8 ساعات = يوم واحد).

3. **التعارض:** النظام يمنع وجود إجازتين متداخلتين لنفس الموظف.

4. **الموافقات:** كل إجازة تحتاج موافقة من 3 مستويات:
   - مسؤول وحدة الإجازات
   - المسؤول المباشر
   - معاون العميد الإداري

5. **تحديث الرصيد:** يتم تحديث الرصيد تلقائياً عند الموافقة على الإجازة الاعتيادية.

---

## 🐛 حل المشاكل

### المشكلة: جدول الإجازات غير موجود
**الحل:** قم بتشغيل ملف `database/leaves_schema.sql` في phpMyAdmin.

### المشكلة: الرصيد لا يظهر
**الحل:** تأكد من إنشاء جدول `leave_balance` وإدراج رصيد للموظفين.

### المشكلة: لا يمكن إضافة إجازة
**الحل:** 
1. تحقق من وجود الموظف في جدول `employees`
2. تحقق من عدم وجود تعارض مع إجازات أخرى
3. تحقق من الرصيد المتبقي

---

## 📞 الدعم

إذا واجهت أي مشكلة:
1. تحقق من ملف `error_log` في السيرفر
2. تحقق من Console في المتصفح (F12)
3. تأكد من تشغيل ملف SQL
4. تأكد من وجود الموظفين في قاعدة البيانات

---

**تم التحديث:** <?php echo date('Y-m-d H:i:s'); ?>

