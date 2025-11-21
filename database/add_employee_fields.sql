-- Employee Management System
-- إضافة الحقول المطلوبة لجدول الموظفين حسب الصورة
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- إضافة الحقول الجديدة لجدول employees
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS specialization VARCHAR(200) NULL COMMENT 'التخصص/النظام (مثل: الانظمة الطبية الذكية، الامن السيبراني)',
ADD COLUMN IF NOT EXISTS degree VARCHAR(100) NULL COMMENT 'الدرجة العلمية (مثل: ماجستير، دكتوراه)',
ADD COLUMN IF NOT EXISTS role_type ENUM('مدرس', 'مدرس مساعد', 'مساعد', 'أستاذ', 'أستاذ مساعد', 'محاضر', 'أخرى') NULL COMMENT 'نوع الدور/المنصب',
ADD COLUMN IF NOT EXISTS field_of_study VARCHAR(200) NULL COMMENT 'مجال الدراسة (مثل: علوم حاسوب والذكاء الاصطناعي)',
ADD COLUMN IF NOT EXISTS score_1 INT NULL COMMENT 'الدرجة/النقاط الأولى',
ADD COLUMN IF NOT EXISTS score_2 INT NULL COMMENT 'الدرجة/النقاط الثانية',
ADD COLUMN IF NOT EXISTS appointment_date DATE NULL COMMENT 'تاريخ التعيين',
ADD COLUMN IF NOT EXISTS appointment_status VARCHAR(100) NULL COMMENT 'حالة التعيين (مثل: تعيين جديد)',
ADD COLUMN IF NOT EXISTS seniority_grant_months INT NULL DEFAULT 0 COMMENT 'منح القدم بالأشهر (مثل: 9)',
ADD COLUMN IF NOT EXISTS seniority_grant_date DATE NULL COMMENT 'تاريخ منح القدم',
ADD COLUMN IF NOT EXISTS full_name VARCHAR(200) NULL COMMENT 'الاسم الكامل';

-- إنشاء فهرس للحقول الجديدة
CREATE INDEX IF NOT EXISTS idx_specialization ON employees(specialization);
CREATE INDEX IF NOT EXISTS idx_degree ON employees(degree);
CREATE INDEX IF NOT EXISTS idx_role_type ON employees(role_type);
CREATE INDEX IF NOT EXISTS idx_field_of_study ON employees(field_of_study);
CREATE INDEX IF NOT EXISTS idx_appointment_date ON employees(appointment_date);

-- تحديث full_name من first_name و last_name للموظفين الموجودين
UPDATE employees SET full_name = CONCAT(first_name, ' ', last_name) WHERE full_name IS NULL OR full_name = '';

-- عرض النتائج
SELECT 'تم إضافة الحقول الجديدة بنجاح!' AS message;
SELECT COUNT(*) AS total_employees FROM employees;

