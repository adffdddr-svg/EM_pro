-- Employee Management System
-- إدراج بيانات افتراضية عراقية واقعية
-- جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات

USE employee_management;

-- ============================================
-- 1. إدراج أقسام إضافية (إذا لم تكن موجودة)
-- ============================================
INSERT INTO departments (name, description) VALUES 
('قسم تقنية المعلومات', 'إدارة أنظمة المعلومات والشبكات والحاسوب'),
('قسم الموارد البشرية', 'إدارة شؤون الموظفين والتوظيف والتدريب'),
('قسم المالية والمحاسبة', 'إدارة الشؤون المالية والمحاسبة والرواتب'),
('قسم المبيعات والتسويق', 'إدارة المبيعات والتسويق والعلاقات العامة'),
('قسم الإنتاج والتصنيع', 'إدارة عمليات الإنتاج والتصنيع والجودة'),
('قسم الصيانة', 'إدارة صيانة المعدات والمرافق'),
('قسم الأمن والسلامة', 'إدارة الأمن والسلامة المهنية'),
('قسم الجودة', 'إدارة الجودة والرقابة')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- 2. إدراج موظفين عراقيين (20 موظف)
-- استخدام SELECT للحصول على department_id من الاسم لتجنب مشاكل Foreign Key
-- ============================================
INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, status) VALUES 
-- قسم تقنية المعلومات
('EMP001', 'أحمد', 'محمد علي', 'ahmed.mohammed@company.iq', '07701234567', 'البصرة - حي الجمعية - شارع الكورنيش', (SELECT id FROM departments WHERE name = 'قسم تقنية المعلومات' LIMIT 1), 'مطور برمجيات', 2500000.00, '2023-01-15', 'active'),
('EMP002', 'علي', 'حسن كاظم', 'ali.hassan@company.iq', '07701234568', 'البصرة - حي العشار - شارع الكويت', (SELECT id FROM departments WHERE name = 'قسم تقنية المعلومات' LIMIT 1), 'مدير تقنية المعلومات', 3500000.00, '2022-06-10', 'active'),
('EMP003', 'زينب', 'عبدالله محمود', 'zainab.abdullah@company.iq', '07701234569', 'البصرة - حي الأندلس - شارع الجامعة', (SELECT id FROM departments WHERE name = 'قسم تقنية المعلومات' LIMIT 1), 'أخصائي شبكات', 2200000.00, '2023-03-20', 'active'),
('EMP004', 'حسين', 'مهدي صالح', 'hussain.mahdi@company.iq', '07701234570', 'البصرة - حي الجمهورية - شارع الخليج', (SELECT id FROM departments WHERE name = 'قسم تقنية المعلومات' LIMIT 1), 'مطور تطبيقات', 2400000.00, '2023-05-12', 'active'),

-- قسم الموارد البشرية
('EMP005', 'فاطمة', 'علي إبراهيم', 'fatima.ali@company.iq', '07701234571', 'البصرة - حي القبلة - شارع السعدون', (SELECT id FROM departments WHERE name = 'قسم الموارد البشرية' LIMIT 1), 'أخصائي موارد بشرية', 2000000.00, '2023-02-20', 'active'),
('EMP006', 'مريم', 'حسين أحمد', 'mariam.hussain@company.iq', '07701234572', 'البصرة - حي الكرامة - شارع البصرة', (SELECT id FROM departments WHERE name = 'قسم الموارد البشرية' LIMIT 1), 'مدير الموارد البشرية', 3200000.00, '2022-08-15', 'active'),
('EMP007', 'سارة', 'محمد كريم', 'sara.mohammed@company.iq', '07701234573', 'البصرة - حي الجمعية - شارع الكورنيش', (SELECT id FROM departments WHERE name = 'قسم الموارد البشرية' LIMIT 1), 'أخصائي توظيف', 1900000.00, '2023-07-01', 'active'),

-- قسم المالية والمحاسبة
('EMP008', 'محمد', 'حسن عبدالله', 'mohammed.hassan@company.iq', '07701234574', 'البصرة - حي العشار - شارع الكويت', (SELECT id FROM departments WHERE name = 'قسم المالية والمحاسبة' LIMIT 1), 'محاسب', 2100000.00, '2023-03-10', 'active'),
('EMP009', 'عبدالله', 'صالح محمود', 'abdullah.saleh@company.iq', '07701234575', 'البصرة - حي الأندلس - شارع الجامعة', (SELECT id FROM departments WHERE name = 'قسم المالية والمحاسبة' LIMIT 1), 'مدير مالي', 3400000.00, '2022-05-20', 'active'),
('EMP010', 'ليلى', 'أحمد علي', 'layla.ahmed@company.iq', '07701234576', 'البصرة - حي الجمهورية - شارع الخليج', (SELECT id FROM departments WHERE name = 'قسم المالية والمحاسبة' LIMIT 1), 'محاسب أول', 2300000.00, '2023-04-05', 'active'),

-- قسم المبيعات والتسويق
('EMP011', 'كريم', 'علي حسن', 'karim.ali@company.iq', '07701234577', 'البصرة - حي القبلة - شارع السعدون', (SELECT id FROM departments WHERE name = 'قسم المبيعات والتسويق' LIMIT 1), 'مندوب مبيعات', 1800000.00, '2023-06-15', 'active'),
('EMP012', 'نور', 'محمد صالح', 'noor.mohammed@company.iq', '07701234578', 'البصرة - حي الكرامة - شارع البصرة', (SELECT id FROM departments WHERE name = 'قسم المبيعات والتسويق' LIMIT 1), 'مدير المبيعات', 3000000.00, '2022-09-10', 'active'),
('EMP013', 'رعد', 'حسين كاظم', 'raad.hussain@company.iq', '07701234579', 'البصرة - حي الجمعية - شارع الكورنيش', (SELECT id FROM departments WHERE name = 'قسم المبيعات والتسويق' LIMIT 1), 'أخصائي تسويق', 2000000.00, '2023-08-20', 'active'),

-- قسم الإنتاج والتصنيع
('EMP014', 'عمر', 'أحمد محمود', 'omar.ahmed@company.iq', '07701234580', 'البصرة - حي العشار - شارع الكويت', (SELECT id FROM departments WHERE name = 'قسم الإنتاج والتصنيع' LIMIT 1), 'مهندس إنتاج', 2600000.00, '2023-01-25', 'active'),
('EMP015', 'يوسف', 'علي إبراهيم', 'youssef.ali@company.iq', '07701234581', 'البصرة - حي الأندلس - شارع الجامعة', (SELECT id FROM departments WHERE name = 'قسم الإنتاج والتصنيع' LIMIT 1), 'مدير الإنتاج', 3300000.00, '2022-07-05', 'active'),
('EMP016', 'هدى', 'حسن عبدالله', 'huda.hassan@company.iq', '07701234582', 'البصرة - حي الجمهورية - شارع الخليج', (SELECT id FROM departments WHERE name = 'قسم الإنتاج والتصنيع' LIMIT 1), 'أخصائي جودة', 2200000.00, '2023-05-30', 'active'),

-- قسم الصيانة
('EMP017', 'طارق', 'محمد صالح', 'tariq.mohammed@company.iq', '07701234583', 'البصرة - حي القبلة - شارع السعدون', (SELECT id FROM departments WHERE name = 'قسم الصيانة' LIMIT 1), 'فني صيانة', 1700000.00, '2023-09-10', 'active'),
('EMP018', 'باسم', 'حسين كريم', 'basem.hussain@company.iq', '07701234584', 'البصرة - حي الكرامة - شارع البصرة', (SELECT id FROM departments WHERE name = 'قسم الصيانة' LIMIT 1), 'مهندس صيانة', 2400000.00, '2023-02-15', 'active'),

-- قسم الأمن والسلامة
('EMP019', 'مصطفى', 'أحمد علي', 'mustafa.ahmed@company.iq', '07701234585', 'البصرة - حي الجمعية - شارع الكورنيش', (SELECT id FROM departments WHERE name = 'قسم الأمن والسلامة' LIMIT 1), 'أخصائي أمن', 1900000.00, '2023-10-01', 'active'),

-- قسم الجودة
('EMP020', 'سعد', 'علي محمود', 'saad.ali@company.iq', '07701234586', 'البصرة - حي العشار - شارع الكويت', (SELECT id FROM departments WHERE name = 'قسم الجودة' LIMIT 1), 'مدير الجودة', 3100000.00, '2022-11-20', 'active')
ON DUPLICATE KEY UPDATE employee_code = VALUES(employee_code);

-- ============================================
-- 3. إدراج بيانات الحضور والانصراف (آخر 30 يوم)
-- ============================================
-- ملاحظة: سيتم إدراج بيانات الحضور لآخر 30 يوم لجميع الموظفين النشطين

-- إنشاء إجراء مؤقت لإدراج بيانات الحضور
DELIMITER $$

DROP PROCEDURE IF EXISTS InsertAttendanceData$$

CREATE PROCEDURE InsertAttendanceData()
BEGIN
    -- جميع المتغيرات يجب أن تكون أولاً
    DECLARE done INT DEFAULT FALSE;
    DECLARE emp_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE att_date DATE;
    DECLARE time_in_val TIME;
    DECLARE time_out_val TIME;
    DECLARE is_holiday INT;
    
    -- ثم CURSOR
    DECLARE emp_cursor CURSOR FOR SELECT id FROM employees WHERE status = 'active';
    
    -- ثم HANDLER
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN emp_cursor;
    
    read_loop: LOOP
        FETCH emp_cursor INTO emp_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET i = 0;
        WHILE i < 30 DO
            SET att_date = DATE_SUB(CURDATE(), INTERVAL i DAY);
            
            -- تحديد إذا كان يوم عطلة (الجمعة)
            SET is_holiday = IF(DAYOFWEEK(att_date) = 6, 1, 0);
            
            IF is_holiday = 0 THEN
                -- يوم عمل عادي
                SET time_in_val = ADDTIME('08:00:00', SEC_TO_TIME(FLOOR(RAND() * 1800))); -- بين 8:00 و 8:30
                SET time_out_val = ADDTIME('16:00:00', SEC_TO_TIME(FLOOR(RAND() * 1800))); -- بين 16:00 و 16:30
                
                INSERT INTO attendance (employee_id, attendance_date, day_type, schedule_id, time_in, time_out, overtime_hours, work_hours_difference, late_arrival_minutes, early_departure_minutes, created_by)
                VALUES (emp_id, att_date, 'work_day', 1, time_in_val, time_out_val, 
                        IF(RAND() > 0.7, ROUND(RAND() * 2, 2), 0), -- 30% فرصة لوقت إضافي
                        ROUND(RAND() * 0.5 - 0.25, 2), -- فارق بسيط في ساعات العمل
                        IF(RAND() > 0.8, FLOOR(RAND() * 30), 0), -- 20% فرصة لتأخير
                        IF(RAND() > 0.9, FLOOR(RAND() * 20), 0), -- 10% فرصة لخروج مبكر
                        1)
                ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out);
            ELSE
                -- يوم عطلة
                INSERT INTO attendance (employee_id, attendance_date, day_type, schedule_id, created_by)
                VALUES (emp_id, att_date, 'holiday', NULL, 1)
                ON DUPLICATE KEY UPDATE day_type = 'holiday';
            END IF;
            
            SET i = i + 1;
        END WHILE;
    END LOOP;
    
    CLOSE emp_cursor;
END$$

DELIMITER ;

-- تشغيل الإجراء
CALL InsertAttendanceData();

-- حذف الإجراء بعد الاستخدام
DROP PROCEDURE IF EXISTS InsertAttendanceData;

-- ============================================
-- 4. إدراج رصيد الإجازات للموظفين الجدد
-- ============================================
INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance, used_this_year)
SELECT id, 104, 2, 
       CASE 
           WHEN RAND() > 0.5 THEN 104 - FLOOR(RAND() * 20) -- بعض الموظفين استخدموا إجازات
           ELSE 104
       END,
       CASE 
           WHEN RAND() > 0.5 THEN FLOOR(RAND() * 20)
           ELSE 0
       END
FROM employees
WHERE id NOT IN (SELECT employee_id FROM leave_balance WHERE employee_id IS NOT NULL)
ON DUPLICATE KEY UPDATE total_balance = total_balance;

-- ============================================
-- 5. إدراج إجازات للموظفين (أمثلة واقعية)
-- ============================================
INSERT INTO employee_leaves (employee_id, leave_type, start_date, end_date, days, purpose, status, approved_by, approved_at) VALUES
-- إجازات عادية
((SELECT id FROM employees WHERE employee_code = 'EMP001' LIMIT 1), 'ordinary', '2024-01-10', '2024-01-12', 3, 'إجازة عادية', 'approved', 1, '2024-01-05 10:00:00'),
((SELECT id FROM employees WHERE employee_code = 'EMP005' LIMIT 1), 'ordinary', '2024-02-15', '2024-02-17', 3, 'إجازة عادية', 'approved', 1, '2024-02-10 09:30:00'),
((SELECT id FROM employees WHERE employee_code = 'EMP008' LIMIT 1), 'ordinary', '2024-03-20', '2024-03-22', 3, 'إجازة عادية', 'approved', 1, '2024-03-15 11:00:00'),

-- إجازات طبية
((SELECT id FROM employees WHERE employee_code = 'EMP003' LIMIT 1), 'medical', '2024-04-05', '2024-04-07', 3, 'إجازة طبية', 'approved', 1, '2024-04-01 14:00:00'),
((SELECT id FROM employees WHERE employee_code = 'EMP010' LIMIT 1), 'medical', '2024-05-12', '2024-05-14', 3, 'إجازة طبية', 'approved', 1, '2024-05-08 10:30:00'),

-- إجازات طارئة
((SELECT id FROM employees WHERE employee_code = 'EMP011' LIMIT 1), 'emergency', '2024-06-01', '2024-06-01', 1, 'ظرف طارئ', 'approved', 1, '2024-05-30 16:00:00'),
((SELECT id FROM employees WHERE employee_code = 'EMP014' LIMIT 1), 'emergency', '2024-07-10', '2024-07-10', 1, 'ظرف طارئ', 'approved', 1, '2024-07-08 09:00:00'),

-- إجازات معلقة
((SELECT id FROM employees WHERE employee_code = 'EMP007' LIMIT 1), 'ordinary', '2024-08-15', '2024-08-20', 6, 'إجازة عادية', 'pending', NULL, NULL),
((SELECT id FROM employees WHERE employee_code = 'EMP013' LIMIT 1), 'ordinary', '2024-09-01', '2024-09-05', 5, 'إجازة عادية', 'pending', NULL, NULL),

-- إجازات مرفوضة
((SELECT id FROM employees WHERE employee_code = 'EMP016' LIMIT 1), 'ordinary', '2024-10-10', '2024-10-15', 6, 'إجازة عادية', 'rejected', 1, '2024-10-05 11:00:00')
ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id);

-- ============================================
-- 6. إدراج سجلات الموظفين (أمثلة واقعية)
-- ============================================
INSERT INTO employee_records (employee_id, record_type, title, description, record_date, status, created_by) VALUES
-- سجلات شخصية
((SELECT id FROM employees WHERE employee_code = 'EMP001' LIMIT 1), 'personal', 'تحديث العنوان', 'تم تحديث عنوان السكن إلى البصرة - حي الجمعية', '2024-01-20', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP002' LIMIT 1), 'personal', 'تحديث رقم الهاتف', 'تم تحديث رقم الهاتف', '2024-02-15', 'active', 1),

-- سجلات وظيفية
((SELECT id FROM employees WHERE employee_code = 'EMP003' LIMIT 1), 'employment', 'تعيين جديد', 'تم تعيين الموظف في قسم تقنية المعلومات', '2023-03-20', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP005' LIMIT 1), 'employment', 'ترقية', 'تمت ترقية الموظف إلى أخصائي موارد بشرية', '2023-08-10', 'active', 1),

-- سجلات تقييم
((SELECT id FROM employees WHERE employee_code = 'EMP001' LIMIT 1), 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء ممتاز', '2024-01-10', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP002' LIMIT 1), 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء جيد جداً', '2024-01-10', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP006' LIMIT 1), 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء ممتاز', '2024-01-10', 'active', 1),

-- سجلات تدريب
((SELECT id FROM employees WHERE employee_code = 'EMP001' LIMIT 1), 'training', 'دورة تطوير البرمجيات', 'حضور دورة تطوير البرمجيات المتقدمة', '2024-03-15', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP004' LIMIT 1), 'training', 'دورة الشبكات', 'حضور دورة إدارة الشبكات', '2024-04-20', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP010' LIMIT 1), 'training', 'دورة المحاسبة', 'حضور دورة المحاسبة المتقدمة', '2024-05-10', 'active', 1),

-- شهادات
((SELECT id FROM employees WHERE employee_code = 'EMP001' LIMIT 1), 'certificate', 'شهادة مطور برمجيات', 'حصل على شهادة مطور برمجيات من Microsoft', '2024-06-01', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP002' LIMIT 1), 'certificate', 'شهادة إدارة المشاريع', 'حصل على شهادة PMP', '2024-07-15', 'active', 1),

-- ترقيات
((SELECT id FROM employees WHERE employee_code = 'EMP005' LIMIT 1), 'promotion', 'ترقية إلى أخصائي موارد بشرية', 'تمت ترقية الموظف بناءً على الأداء المتميز', '2023-08-10', 'active', 1),
((SELECT id FROM employees WHERE employee_code = 'EMP008' LIMIT 1), 'promotion', 'ترقية إلى محاسب أول', 'تمت ترقية الموظف بناءً على الخبرة والأداء', '2024-02-01', 'active', 1)
ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id);

-- ============================================
-- 7. عرض ملخص البيانات المدرجة
-- ============================================
SELECT '=== ملخص البيانات المدرجة ===' AS '';
SELECT CONCAT('عدد الأقسام: ', COUNT(*)) AS '' FROM departments;
SELECT CONCAT('عدد الموظفين: ', COUNT(*)) AS '' FROM employees WHERE status = 'active';
SELECT CONCAT('عدد سجلات الحضور: ', COUNT(*)) AS '' FROM attendance;
SELECT CONCAT('عدد الإجازات: ', COUNT(*)) AS '' FROM employee_leaves;
SELECT CONCAT('عدد السجلات: ', COUNT(*)) AS '' FROM employee_records;
SELECT CONCAT('عدد رصيد الإجازات: ', COUNT(*)) AS '' FROM leave_balance;

SELECT 'تم إدراج البيانات بنجاح!' AS '';

