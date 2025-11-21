<?php
/**
 * Employee Management System
 * إدراج بيانات افتراضية عراقية واقعية
 * جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات
 * 
 * كيفية الاستخدام:
 * 1. افتح المتصفح واكتب: http://localhost/EM_pro/insert_dummy_data.php
 * 2. انقر على زر "إدراج البيانات"
 * 3. انتظر حتى يكتمل التنفيذ
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'employee_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// بدء الجلسة
session_start();

// التحقق من أن المستخدم مسجل دخول (اختياري - يمكنك إزالة هذا الشرط)
// if (!isset($_SESSION['user_id'])) {
//     die('يجب تسجيل الدخول أولاً');
// }

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// معالجة الطلب
$message = '';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_data'])) {
    try {
        $pdo->beginTransaction();

        // ============================================
        // 1. إدراج أقسام إضافية
        // ============================================
        $departments = [
            ['قسم تقنية المعلومات', 'إدارة أنظمة المعلومات والشبكات والحاسوب'],
            ['قسم الموارد البشرية', 'إدارة شؤون الموظفين والتوظيف والتدريب'],
            ['قسم المالية والمحاسبة', 'إدارة الشؤون المالية والمحاسبة والرواتب'],
            ['قسم المبيعات والتسويق', 'إدارة المبيعات والتسويق والعلاقات العامة'],
            ['قسم الإنتاج والتصنيع', 'إدارة عمليات الإنتاج والتصنيع والجودة'],
            ['قسم الصيانة', 'إدارة صيانة المعدات والمرافق'],
            ['قسم الأمن والسلامة', 'إدارة الأمن والسلامة المهنية'],
            ['قسم الجودة', 'إدارة الجودة والرقابة']
        ];

        $stmt_dept = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        foreach ($departments as $dept) {
            $stmt_dept->execute($dept);
        }
        $message .= "✓ تم إدراج " . count($departments) . " أقسام<br>";

        // ============================================
        // 2. إدراج موظفين عراقيين (20 موظف)
        // ============================================
        $employees = [
            // قسم تقنية المعلومات
            ['EMP001', 'أحمد', 'محمد علي', 'ahmed.mohammed@company.iq', '07701234567', 'البصرة - حي الجمعية - شارع الكورنيش', 1, 'مطور برمجيات', 2500000.00, '2023-01-15', 'active'],
            ['EMP002', 'علي', 'حسن كاظم', 'ali.hassan@company.iq', '07701234568', 'البصرة - حي العشار - شارع الكويت', 1, 'مدير تقنية المعلومات', 3500000.00, '2022-06-10', 'active'],
            ['EMP003', 'زينب', 'عبدالله محمود', 'zainab.abdullah@company.iq', '07701234569', 'البصرة - حي الأندلس - شارع الجامعة', 1, 'أخصائي شبكات', 2200000.00, '2023-03-20', 'active'],
            ['EMP004', 'حسين', 'مهدي صالح', 'hussain.mahdi@company.iq', '07701234570', 'البصرة - حي الجمهورية - شارع الخليج', 1, 'مطور تطبيقات', 2400000.00, '2023-05-12', 'active'],
            // قسم الموارد البشرية
            ['EMP005', 'فاطمة', 'علي إبراهيم', 'fatima.ali@company.iq', '07701234571', 'البصرة - حي القبلة - شارع السعدون', 2, 'أخصائي موارد بشرية', 2000000.00, '2023-02-20', 'active'],
            ['EMP006', 'مريم', 'حسين أحمد', 'mariam.hussain@company.iq', '07701234572', 'البصرة - حي الكرامة - شارع البصرة', 2, 'مدير الموارد البشرية', 3200000.00, '2022-08-15', 'active'],
            ['EMP007', 'سارة', 'محمد كريم', 'sara.mohammed@company.iq', '07701234573', 'البصرة - حي الجمعية - شارع الكورنيش', 2, 'أخصائي توظيف', 1900000.00, '2023-07-01', 'active'],
            // قسم المالية والمحاسبة
            ['EMP008', 'محمد', 'حسن عبدالله', 'mohammed.hassan@company.iq', '07701234574', 'البصرة - حي العشار - شارع الكويت', 3, 'محاسب', 2100000.00, '2023-03-10', 'active'],
            ['EMP009', 'عبدالله', 'صالح محمود', 'abdullah.saleh@company.iq', '07701234575', 'البصرة - حي الأندلس - شارع الجامعة', 3, 'مدير مالي', 3400000.00, '2022-05-20', 'active'],
            ['EMP010', 'ليلى', 'أحمد علي', 'layla.ahmed@company.iq', '07701234576', 'البصرة - حي الجمهورية - شارع الخليج', 3, 'محاسب أول', 2300000.00, '2023-04-05', 'active'],
            // قسم المبيعات والتسويق
            ['EMP011', 'كريم', 'علي حسن', 'karim.ali@company.iq', '07701234577', 'البصرة - حي القبلة - شارع السعدون', 4, 'مندوب مبيعات', 1800000.00, '2023-06-15', 'active'],
            ['EMP012', 'نور', 'محمد صالح', 'noor.mohammed@company.iq', '07701234578', 'البصرة - حي الكرامة - شارع البصرة', 4, 'مدير المبيعات', 3000000.00, '2022-09-10', 'active'],
            ['EMP013', 'رعد', 'حسين كاظم', 'raad.hussain@company.iq', '07701234579', 'البصرة - حي الجمعية - شارع الكورنيش', 4, 'أخصائي تسويق', 2000000.00, '2023-08-20', 'active'],
            // قسم الإنتاج والتصنيع
            ['EMP014', 'عمر', 'أحمد محمود', 'omar.ahmed@company.iq', '07701234580', 'البصرة - حي العشار - شارع الكويت', 5, 'مهندس إنتاج', 2600000.00, '2023-01-25', 'active'],
            ['EMP015', 'يوسف', 'علي إبراهيم', 'youssef.ali@company.iq', '07701234581', 'البصرة - حي الأندلس - شارع الجامعة', 5, 'مدير الإنتاج', 3300000.00, '2022-07-05', 'active'],
            ['EMP016', 'هدى', 'حسن عبدالله', 'huda.hassan@company.iq', '07701234582', 'البصرة - حي الجمهورية - شارع الخليج', 5, 'أخصائي جودة', 2200000.00, '2023-05-30', 'active'],
            // قسم الصيانة
            ['EMP017', 'طارق', 'محمد صالح', 'tariq.mohammed@company.iq', '07701234583', 'البصرة - حي القبلة - شارع السعدون', 6, 'فني صيانة', 1700000.00, '2023-09-10', 'active'],
            ['EMP018', 'باسم', 'حسين كريم', 'basem.hussain@company.iq', '07701234584', 'البصرة - حي الكرامة - شارع البصرة', 6, 'مهندس صيانة', 2400000.00, '2023-02-15', 'active'],
            // قسم الأمن والسلامة
            ['EMP019', 'مصطفى', 'أحمد علي', 'mustafa.ahmed@company.iq', '07701234585', 'البصرة - حي الجمعية - شارع الكورنيش', 7, 'أخصائي أمن', 1900000.00, '2023-10-01', 'active'],
            // قسم الجودة
            ['EMP020', 'سعد', 'علي محمود', 'saad.ali@company.iq', '07701234586', 'البصرة - حي العشار - شارع الكويت', 8, 'مدير الجودة', 3100000.00, '2022-11-20', 'active']
        ];

        $stmt_emp = $pdo->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE employee_code = VALUES(employee_code)");
        foreach ($employees as $emp) {
            $stmt_emp->execute($emp);
        }
        $message .= "✓ تم إدراج " . count($employees) . " موظف<br>";

        // ============================================
        // 3. إدراج بيانات الحضور والانصراف (آخر 30 يوم)
        // ============================================
        $stmt_emp_ids = $pdo->query("SELECT id FROM employees WHERE status = 'active'");
        $employee_ids = $stmt_emp_ids->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt_att = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, day_type, schedule_id, time_in, time_out, overtime_hours, work_hours_difference, late_arrival_minutes, early_departure_minutes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out)");
        
        $attendance_count = 0;
        foreach ($employee_ids as $emp_id) {
            for ($i = 0; $i < 30; $i++) {
                $att_date = date('Y-m-d', strtotime("-$i days"));
                $day_of_week = date('w', strtotime($att_date));
                
                // الجمعة = عطلة
                if ($day_of_week == 5) {
                    $stmt_att->execute([$emp_id, $att_date, 'holiday', null, null, null, 0, 0, 0, 0, 1]);
                } else {
                    // يوم عمل عادي
                    $time_in = date('H:i:s', strtotime('08:00:00') + rand(0, 1800)); // بين 8:00 و 8:30
                    $time_out = date('H:i:s', strtotime('16:00:00') + rand(0, 1800)); // بين 16:00 و 16:30
                    $overtime = (rand(0, 100) > 70) ? round(rand(0, 200) / 100, 2) : 0;
                    $work_diff = round((rand(-25, 25)) / 100, 2);
                    $late = (rand(0, 100) > 80) ? rand(1, 30) : 0;
                    $early = (rand(0, 100) > 90) ? rand(1, 20) : 0;
                    
                    $stmt_att->execute([$emp_id, $att_date, 'work_day', 1, $time_in, $time_out, $overtime, $work_diff, $late, $early, 1]);
                    $attendance_count++;
                }
            }
        }
        $message .= "✓ تم إدراج سجلات حضور لآخر 30 يوم<br>";

        // ============================================
        // 4. إدراج رصيد الإجازات
        // ============================================
        $stmt_balance = $pdo->prepare("INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance, used_this_year) VALUES (?, 104, 2, ?, ?) ON DUPLICATE KEY UPDATE total_balance = total_balance");
        foreach ($employee_ids as $emp_id) {
            $remaining = 104 - rand(0, 20);
            $used = rand(0, 20);
            $stmt_balance->execute([$emp_id, $remaining, $used]);
        }
        $message .= "✓ تم إدراج رصيد الإجازات<br>";

        // ============================================
        // 5. إدراج إجازات
        // ============================================
        $leaves = [
            ['EMP001', 'ordinary', '2024-01-10', '2024-01-12', 3, 'إجازة عادية', 'approved', 1, '2024-01-05 10:00:00'],
            ['EMP005', 'ordinary', '2024-02-15', '2024-02-17', 3, 'إجازة عادية', 'approved', 1, '2024-02-10 09:30:00'],
            ['EMP008', 'ordinary', '2024-03-20', '2024-03-22', 3, 'إجازة عادية', 'approved', 1, '2024-03-15 11:00:00'],
            ['EMP003', 'medical', '2024-04-05', '2024-04-07', 3, 'إجازة طبية', 'approved', 1, '2024-04-01 14:00:00'],
            ['EMP010', 'medical', '2024-05-12', '2024-05-14', 3, 'إجازة طبية', 'approved', 1, '2024-05-08 10:30:00'],
            ['EMP011', 'emergency', '2024-06-01', '2024-06-01', 1, 'ظرف طارئ', 'approved', 1, '2024-05-30 16:00:00'],
            ['EMP014', 'emergency', '2024-07-10', '2024-07-10', 1, 'ظرف طارئ', 'approved', 1, '2024-07-08 09:00:00'],
            ['EMP007', 'ordinary', '2024-08-15', '2024-08-20', 6, 'إجازة عادية', 'pending', null, null],
            ['EMP013', 'ordinary', '2024-09-01', '2024-09-05', 5, 'إجازة عادية', 'pending', null, null],
            ['EMP016', 'ordinary', '2024-10-10', '2024-10-15', 6, 'إجازة عادية', 'rejected', 1, '2024-10-05 11:00:00']
        ];

        $stmt_leave = $pdo->prepare("INSERT INTO employee_leaves (employee_id, leave_type, start_date, end_date, days, purpose, status, approved_by, approved_at) VALUES ((SELECT id FROM employees WHERE employee_code = ?), ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id)");
        foreach ($leaves as $leave) {
            $stmt_leave->execute($leave);
        }
        $message .= "✓ تم إدراج " . count($leaves) . " إجازة<br>";

        // ============================================
        // 6. إدراج سجلات الموظفين
        // ============================================
        $records = [
            ['EMP001', 'personal', 'تحديث العنوان', 'تم تحديث عنوان السكن إلى البصرة - حي الجمعية', '2024-01-20'],
            ['EMP002', 'personal', 'تحديث رقم الهاتف', 'تم تحديث رقم الهاتف', '2024-02-15'],
            ['EMP003', 'employment', 'تعيين جديد', 'تم تعيين الموظف في قسم تقنية المعلومات', '2023-03-20'],
            ['EMP005', 'employment', 'ترقية', 'تمت ترقية الموظف إلى أخصائي موارد بشرية', '2023-08-10'],
            ['EMP001', 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء ممتاز', '2024-01-10'],
            ['EMP002', 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء جيد جداً', '2024-01-10'],
            ['EMP006', 'evaluation', 'تقييم الأداء السنوي', 'تقييم الأداء للعام 2023 - أداء ممتاز', '2024-01-10'],
            ['EMP001', 'training', 'دورة تطوير البرمجيات', 'حضور دورة تطوير البرمجيات المتقدمة', '2024-03-15'],
            ['EMP004', 'training', 'دورة الشبكات', 'حضور دورة إدارة الشبكات', '2024-04-20'],
            ['EMP010', 'training', 'دورة المحاسبة', 'حضور دورة المحاسبة المتقدمة', '2024-05-10'],
            ['EMP001', 'certificate', 'شهادة مطور برمجيات', 'حصل على شهادة مطور برمجيات من Microsoft', '2024-06-01'],
            ['EMP002', 'certificate', 'شهادة إدارة المشاريع', 'حصل على شهادة PMP', '2024-07-15'],
            ['EMP005', 'promotion', 'ترقية إلى أخصائي موارد بشرية', 'تمت ترقية الموظف بناءً على الأداء المتميز', '2023-08-10'],
            ['EMP008', 'promotion', 'ترقية إلى محاسب أول', 'تمت ترقية الموظف بناءً على الخبرة والأداء', '2024-02-01']
        ];

        $stmt_record = $pdo->prepare("INSERT INTO employee_records (employee_id, record_type, title, description, record_date, status, created_by) VALUES ((SELECT id FROM employees WHERE employee_code = ?), ?, ?, ?, ?, 'active', 1) ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id)");
        foreach ($records as $record) {
            $stmt_record->execute($record);
        }
        $message .= "✓ تم إدراج " . count($records) . " سجل<br>";

        $pdo->commit();
        $success = true;
        $message = "<div style='color: green; font-weight: bold;'>✅ تم إدراج جميع البيانات بنجاح!</div><br>" . $message;

    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        $message = "<div style='color: red; font-weight: bold;'>❌ حدث خطأ: " . $e->getMessage() . "</div>";
        $errors[] = $e->getMessage();
    }
}

// الحصول على إحصائيات
$stats = [];
try {
    $stats['departments'] = $pdo->query("SELECT COUNT(*) as count FROM departments")->fetch()['count'];
    $stats['employees'] = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch()['count'];
    $stats['attendance'] = $pdo->query("SELECT COUNT(*) as count FROM attendance")->fetch()['count'];
    $stats['leaves'] = $pdo->query("SELECT COUNT(*) as count FROM employee_leaves")->fetch()['count'];
    $stats['records'] = $pdo->query("SELECT COUNT(*) as count FROM employee_records")->fetch()['count'];
    $stats['leave_balance'] = $pdo->query("SELECT COUNT(*) as count FROM leave_balance")->fetch()['count'];
} catch (PDOException $e) {
    // تجاهل الأخطاء في الإحصائيات
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدراج بيانات افتراضية - نظام إدارة الموظفين</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .form-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .form-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            font-weight: bold;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-right: 4px solid #ffc107;
        }
        .warning strong {
            display: block;
            margin-bottom: 10px;
        }
        .info-list {
            list-style: none;
            padding: 0;
        }
        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-list li:before {
            content: "✓ ";
            color: #667eea;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 إدراج بيانات افتراضية عراقية</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>الأقسام</h3>
                <div class="value"><?php echo $stats['departments'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>الموظفون</h3>
                <div class="value"><?php echo $stats['employees'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>سجلات الحضور</h3>
                <div class="value"><?php echo $stats['attendance'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>الإجازات</h3>
                <div class="value"><?php echo $stats['leaves'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>السجلات</h3>
                <div class="value"><?php echo $stats['records'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>رصيد الإجازات</h3>
                <div class="value"><?php echo $stats['leave_balance'] ?? 0; ?></div>
            </div>
        </div>

        <div class="form-section">
            <h2>⚠️ تنبيه مهم</h2>
            <div class="warning">
                <strong>قبل إدراج البيانات:</strong>
                <ul class="info-list">
                    <li>تأكد من أن جميع الجداول موجودة في قاعدة البيانات</li>
                    <li>إذا كانت البيانات موجودة مسبقاً، سيتم تحديثها فقط</li>
                    <li>لن يتم حذف البيانات الموجودة</li>
                    <li>البيانات المدرجة هي بيانات عراقية واقعية</li>
                </ul>
            </div>
        </div>

        <div class="form-section">
            <h2>📋 البيانات التي سيتم إدراجها:</h2>
            <ul class="info-list">
                <li>8 أقسام (تقنية المعلومات، الموارد البشرية، المالية، إلخ)</li>
                <li>20 موظف بأسماء عراقية وعناوين في البصرة</li>
                <li>سجلات حضور لآخر 30 يوم لجميع الموظفين</li>
                <li>إجازات متنوعة (عادية، طبية، طارئة، معلقة، مرفوضة)</li>
                <li>سجلات موظفين (تقييمات، تدريب، ترقيات، شهادات)</li>
                <li>رصيد إجازات لكل موظف</li>
            </ul>
        </div>

        <form method="POST">
            <button type="submit" name="insert_data" class="btn">
                🚀 إدراج البيانات الآن
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
            <p>جامعة البصرة - كلية علوم الحاسوب وتكنولوجيا المعلومات</p>
            <p style="margin-top: 10px;">
                <a href="admin/dashboard.php" style="color: #667eea; text-decoration: none;">← العودة إلى لوحة التحكم</a>
            </p>
        </div>
    </div>
</body>
</html>

