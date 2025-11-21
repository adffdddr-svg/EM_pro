-- Employee Management System
-- تحديث الحقول الوظيفية للموظفين حسب المتطلبات الجديدة
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- إزالة الحقول القديمة غير المطلوبة (إذا كانت موجودة)
-- ALTER TABLE employees DROP COLUMN IF EXISTS score_1;
-- ALTER TABLE employees DROP COLUMN IF EXISTS score_2;
-- ALTER TABLE employees DROP COLUMN IF EXISTS appointment_status;
-- ALTER TABLE employees DROP COLUMN IF EXISTS seniority_grant_months;
-- ALTER TABLE employees DROP COLUMN IF EXISTS seniority_grant_date;

-- إضافة الحقول الجديدة المطلوبة
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS certificate VARCHAR(200) NULL COMMENT 'الشهادة',
ADD COLUMN IF NOT EXISTS certificate_date DATE NULL COMMENT 'تاريخ الحصول على الشهادة',
ADD COLUMN IF NOT EXISTS title VARCHAR(200) NULL COMMENT 'اللقب',
ADD COLUMN IF NOT EXISTS title_date DATE NULL COMMENT 'تاريخ الحصول على اللقب',
ADD COLUMN IF NOT EXISTS current_salary DECIMAL(10, 2) NULL COMMENT 'الراتب الحالي',
ADD COLUMN IF NOT EXISTS new_salary DECIMAL(10, 2) NULL COMMENT 'الراتب الجديد',
ADD COLUMN IF NOT EXISTS last_raise_date DATE NULL COMMENT 'تاريخ آخر زيادة',
ADD COLUMN IF NOT EXISTS entitlement_date DATE NULL COMMENT 'تاريخ الاستحقاق',
ADD COLUMN IF NOT EXISTS grade_entry_date DATE NULL COMMENT 'تاريخ الدخول بدرجة',
ADD COLUMN IF NOT EXISTS last_promotion_date DATE NULL COMMENT 'تاريخ آخر ترفيع',
ADD COLUMN IF NOT EXISTS last_promotion_number VARCHAR(50) NULL COMMENT 'رقم آخر ترفيع',
ADD COLUMN IF NOT EXISTS job_notes TEXT NULL COMMENT 'ملاحظات وظيفية';

-- إنشاء فهرس للحقول الجديدة
CREATE INDEX IF NOT EXISTS idx_certificate_date ON employees(certificate_date);
CREATE INDEX IF NOT EXISTS idx_title_date ON employees(title_date);
CREATE INDEX IF NOT EXISTS idx_last_raise_date ON employees(last_raise_date);
CREATE INDEX IF NOT EXISTS idx_entitlement_date ON employees(entitlement_date);
CREATE INDEX IF NOT EXISTS idx_grade_entry_date ON employees(grade_entry_date);
CREATE INDEX IF NOT EXISTS idx_last_promotion_date ON employees(last_promotion_date);

-- عرض النتائج
SELECT 'تم تحديث الحقول الوظيفية بنجاح!' AS message;

