# حل مشكلة الحقول الوظيفية - خطوات مفصلة

## المشكلة:
عند فتح صفحة إضافة موظف جديد، تظهر رسالة تحذير تقول: "الحقول الوظيفية غير موجودة في قاعدة البيانات"

## الحل - خطوات مفصلة:

### الطريقة الأولى: إضافة الحقول عبر AJAX (الأسهل والأسرع)

1. **افتح صفحة إضافة الموظف:**
   - اذهب إلى: `http://localhost/EM_pro/admin/employees/add.php`
   - ستظهر رسالة تحذير صفراء في الأعلى

2. **اضغط على زر "إضافة الحقول تلقائياً":**
   - الزر موجود داخل رسالة التحذير
   - سيظهر مؤشر تحميل "جاري الإضافة..."
   - انتظر حتى تظهر رسالة النجاح

3. **انتظر التحديث التلقائي:**
   - بعد 1.5 ثانية، ستحدث الصفحة تلقائياً
   - ستختفي رسالة التحذير
   - يمكنك الآن إضافة موظف جديد

---

### الطريقة الثانية: إضافة الحقول عبر صفحة منفصلة

1. **افتح صفحة إضافة الحقول:**
   - اذهب إلى: `http://localhost/EM_pro/database/update_employee_job_fields.php`
   - أو اضغط على رابط "فتح صفحة الإعدادات" في رسالة التحذير

2. **انتظر حتى تكتمل العملية:**
   - ستظهر رسائل لكل حقل يتم إضافته
   - ستظهر رسالة نجاح في النهاية

3. **ارجع إلى صفحة إضافة الموظف:**
   - اضغط على زر "إضافة موظف جديد الآن"
   - أو اذهب إلى: `http://localhost/EM_pro/admin/employees/add.php`
   - ستختفي رسالة التحذير

---

### الطريقة الثالثة: إضافة الحقول يدوياً عبر phpMyAdmin

1. **افتح phpMyAdmin:**
   - اذهب إلى: `http://localhost/phpmyadmin`
   - اختر قاعدة البيانات: `employee_management`

2. **افتح جدول employees:**
   - اضغط على جدول `employees`
   - اضغط على تبويب "Structure"

3. **أضف الحقول يدوياً:**
   - اضغط على "Add" في نهاية القائمة
   - أضف كل حقل على حدة:

   ```
   certificate - VARCHAR(200) - NULL
   certificate_date - DATE - NULL
   title - VARCHAR(200) - NULL
   title_date - DATE - NULL
   current_salary - DECIMAL(10,2) - NULL
   new_salary - DECIMAL(10,2) - NULL
   last_raise_date - DATE - NULL
   entitlement_date - DATE - NULL
   grade_entry_date - DATE - NULL
   last_promotion_date - DATE - NULL
   last_promotion_number - VARCHAR(50) - NULL
   job_notes - TEXT - NULL
   ```

4. **احفظ التغييرات:**
   - اضغط "Save" بعد كل حقل
   - أو استخدم SQL مباشرة (انظر الطريقة الرابعة)

---

### الطريقة الرابعة: تشغيل ملف SQL مباشرة

1. **افتح phpMyAdmin:**
   - اذهب إلى: `http://localhost/phpmyadmin`
   - اختر قاعدة البيانات: `employee_management`

2. **افتح تبويب SQL:**
   - اضغط على تبويب "SQL" في الأعلى

3. **انسخ والصق الكود التالي:**

```sql
ALTER TABLE employees 
ADD COLUMN certificate VARCHAR(200) NULL COMMENT 'الشهادة',
ADD COLUMN certificate_date DATE NULL COMMENT 'تاريخ الحصول على الشهادة',
ADD COLUMN title VARCHAR(200) NULL COMMENT 'اللقب',
ADD COLUMN title_date DATE NULL COMMENT 'تاريخ الحصول على اللقب',
ADD COLUMN current_salary DECIMAL(10, 2) NULL COMMENT 'الراتب الحالي',
ADD COLUMN new_salary DECIMAL(10, 2) NULL COMMENT 'الراتب الجديد',
ADD COLUMN last_raise_date DATE NULL COMMENT 'تاريخ آخر زيادة',
ADD COLUMN entitlement_date DATE NULL COMMENT 'تاريخ الاستحقاق',
ADD COLUMN grade_entry_date DATE NULL COMMENT 'تاريخ الدخول بدرجة',
ADD COLUMN last_promotion_date DATE NULL COMMENT 'تاريخ آخر ترفيع',
ADD COLUMN last_promotion_number VARCHAR(50) NULL COMMENT 'رقم آخر ترفيع',
ADD COLUMN job_notes TEXT NULL COMMENT 'ملاحظات وظيفية';
```

4. **اضغط "Go" أو "تنفيذ":**
   - إذا ظهرت أخطاء عن "Duplicate column"، تجاهلها
   - هذا يعني أن الحقل موجود بالفعل

5. **تحقق من النتيجة:**
   - اذهب إلى صفحة إضافة الموظف
   - يجب أن تختفي رسالة التحذير

---

## التحقق من الحل:

بعد إضافة الحقول، افتح صفحة إضافة الموظف:
- `http://localhost/EM_pro/admin/employees/add.php`

**إذا اختفت رسالة التحذير:** ✅ المشكلة حُلت بنجاح!

**إذا لم تختف الرسالة:**
1. اضغط Ctrl+F5 لتحديث الصفحة بدون كاش
2. أو امسح كاش المتصفح
3. أو جرب متصفح آخر

---

## ملاحظات مهمة:

- الحقول الجديدة **اختيارية** (NULL) - لن تؤثر على البيانات الموجودة
- يمكنك إضافة موظفين جدد حتى بدون الحقول الجديدة (سيستخدم الحقول الأساسية فقط)
- بعد إضافة الحقول، ستكون متاحة في جميع صفحات الموظفين

---

## في حالة استمرار المشكلة:

1. تحقق من أن WAMP Server يعمل
2. تحقق من أن MySQL يعمل
3. تحقق من أن قاعدة البيانات `employee_management` موجودة
4. تحقق من أن جدول `employees` موجود
5. راجع ملف error_log في WAMP

---

## الحقول المطلوبة:

1. certificate - الشهادة
2. certificate_date - تاريخ الحصول على الشهادة
3. title - اللقب
4. title_date - تاريخ الحصول على اللقب
5. current_salary - الراتب الحالي
6. new_salary - الراتب الجديد
7. last_raise_date - تاريخ آخر زيادة
8. entitlement_date - تاريخ الاستحقاق
9. grade_entry_date - تاريخ الدخول بدرجة
10. last_promotion_date - تاريخ آخر ترفيع
11. last_promotion_number - رقم آخر ترفيع
12. job_notes - الملاحظات

