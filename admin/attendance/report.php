<?php
/**
 * Employee Management System
 * تقرير الحضور الأسبوعي
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

// الحصول على معرف الموظف وتاريخ بداية الأسبوع
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$week_start = isset($_GET['week_start']) ? cleanInput($_GET['week_start']) : date('Y-m-d', strtotime('monday this week'));

// إذا كان موظف، استخدم معرفه فقط
if (isEmployee()) {
    $employee = getEmployeeByUserId($_SESSION['user_id']);
    if ($employee) {
        $employee_id = $employee['id'];
    } else {
        redirect(SITE_URL . '/employee/profile.php');
    }
}

if ($employee_id <= 0) {
    redirect(SITE_URL . '/admin/attendance/index.php');
}

// الحصول على بيانات الموظف
$stmt = $db->prepare("SELECT e.*, d.name as department_name
                      FROM employees e
                      LEFT JOIN departments d ON e.department_id = d.id
                      WHERE e.id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect(SITE_URL . '/admin/attendance/index.php');
}

// الحصول على الحضور الأسبوعي
$attendance_records = getWeeklyAttendance($employee_id, $week_start);

// إنشاء مصفوفة لجميع أيام الأسبوع
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($week_start . " +$i days"));
    $week_dates[$date] = [
        'date' => $date,
        'day_name' => getDayNameArabic($date),
        'day_type' => getDayType($date),
        'attendance' => null
    ];
}

// ملء بيانات الحضور
foreach ($attendance_records as $record) {
    if (isset($week_dates[$record['attendance_date']])) {
        $week_dates[$record['attendance_date']]['attendance'] = $record;
    }
}

// حساب إجمالي الصفحات (15 صفحة كما في الصورة)
$total_pages = 15;
$current_page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $total_pages)) : 1;

$page_title = 'تقرير الحضور الأسبوعي';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            direction: rtl;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .header-left {
            text-align: right;
        }
        
        .header-right {
            text-align: left;
        }
        
        .logo {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .page-info {
            font-size: 12px;
            color: #666;
        }
        
        .user-info {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .employee-info {
            margin: 15px 0;
            font-size: 14px;
        }
        
        .employee-info p {
            margin: 5px 0;
        }
        
        .created-by {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11px;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #333;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }
        
        .attendance-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }
        
        .attendance-table td {
            font-size: 10px;
        }
        
        .date-cell {
            text-align: right;
            padding-right: 8px;
        }
        
        .day-name {
            font-size: 9px;
            color: #666;
        }
        
        
        .report-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: left;
            font-size: 11px;
            color: #666;
        }
        
        .print-actions {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .print-actions {
                display: none;
            }
            
            .report-container {
                box-shadow: none;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- رأس التقرير -->
        <div class="report-header">
            <div class="header-left">
                <div class="logo">TCMSV3</div>
                <div class="page-info">الصفحة <?php echo $current_page; ?> / <?php echo $total_pages; ?></div>
            </div>
            <div class="header-right">
                <div class="user-info">هوية المستخدم : <?php echo $employee_id; ?></div>
            </div>
        </div>
        
        <!-- عنوان التقرير -->
        <div class="report-title">تقرير حضور أسبوعية</div>
        
        <!-- معلومات الموظف (خارج الجدول) -->
        <div class="employee-info">
            <p><strong>الاسم :</strong> <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></p>
            <p><strong>القسم :</strong> <?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></p>
        </div>
        
        <div class="created-by">إنشاء بواسطة <?php echo htmlspecialchars($_SESSION['username']); ?></div>
        
        <!-- جدول الحضور -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th rowspan="3">تاريخ</th>
                    <th rowspan="3">نوع اليوم</th>
                    <th rowspan="3">جدول</th>
                    <th rowspan="3">في</th>
                    <th rowspan="3">خارج</th>
                    <th colspan="2">العمل</th>
                    <th rowspan="3">الإجازة المأخوذة</th>
                    <th rowspan="3">ملاحظة</th>
                </tr>
                <tr>
                    <th>الوقت الإضافي</th>
                    <th>فارق ساعات العمل</th>
                </tr>
                <tr>
                    <th>وصول متأخر</th>
                    <th>خروج مبكر</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($week_dates as $date_data): 
                    $att = $date_data['attendance'];
                    $date = $date_data['date'];
                    $day_name = $date_data['day_name'];
                    $day_type = $date_data['day_type'];
                ?>
                    <tr>
                        <td class="date-cell">
                            <?php echo date('Y-m-d', strtotime($date)); ?><br>
                            <span class="day-name"><?php echo $day_name; ?></span>
                        </td>
                        <td><?php echo $day_type == 'work_day' ? 'يوم عمل' : 'يوم عطلة'; ?></td>
                        <td><?php echo $att && $att['schedule_id'] ? $att['schedule_id'] : ''; ?></td>
                        <td>
                            <?php if ($att && $att['time_in']): 
                                $time_in = strtotime($att['time_in']);
                                echo date('A', $time_in) . ' ' . date('h:i', $time_in);
                            else: 
                                echo '';
                            endif; ?>
                        </td>
                        <td>
                            <?php if ($att && $att['time_out']): 
                                $time_out = strtotime($att['time_out']);
                                echo date('A', $time_out) . ' ' . date('h:i', $time_out);
                            else: 
                                echo '';
                            endif; ?>
                        </td>
                        <td>
                            <?php 
                            $work_data = [];
                            if ($att && $att['overtime_hours'] > 0) {
                                $work_data[] = 'وقت إضافي: ' . $att['overtime_hours'];
                            }
                            if ($att && $att['late_arrival_minutes'] > 0) {
                                $work_data[] = 'تأخير: ' . $att['late_arrival_minutes'] . ' دقيقة';
                            }
                            echo !empty($work_data) ? implode('<br>', $work_data) : '';
                            ?>
                        </td>
                        <td>
                            <?php 
                            $work_diff_data = [];
                            if ($att && $att['work_hours_difference'] != 0) {
                                $work_diff_data[] = 'فارق: ' . $att['work_hours_difference'] . ' ساعة';
                            }
                            if ($att && $att['early_departure_minutes'] > 0) {
                                $work_diff_data[] = 'خروج مبكر: ' . $att['early_departure_minutes'] . ' دقيقة';
                            }
                            echo !empty($work_diff_data) ? implode('<br>', $work_diff_data) : '';
                            ?>
                        </td>
                        <td><?php echo $att && $att['leave_taken'] ? htmlspecialchars($att['leave_taken']) : ''; ?></td>
                        <td><?php echo $att && $att['notes'] ? htmlspecialchars($att['notes']) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- تذييل التقرير -->
        <div class="report-footer">
            <div>كلية علوم الحاسوب</div>
            <div>مطبوع <?php echo date('Y-m-d h:i:s A'); ?></div>
        </div>
    </div>
    
    <div class="print-actions">
        <button onclick="window.print()" class="btn">🖨️ طباعة</button>
        <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="btn">← رجوع</a>
    </div>
</body>
</html>

