<?php
/**
 * Employee Management System
 * الدوال المساعدة
 */

// منع الوصول المباشر
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

/**
 * تنظيف المدخلات من XSS
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * توليد رمز موظف تلقائي
 */
function generateEmployeeCode() {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM employees");
    $result = $stmt->fetch();
    $next_id = ($result['max_id'] ?? 0) + 1;
    return 'EMP' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
}

/**
 * رفع صورة آمنة
 */
function uploadImage($file, $old_image = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'فشل رفع الملف'];
    }

    // التحقق من الحجم
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }

    // التحقق من النوع
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح'];
    }

    // إنشاء اسم فريد
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('emp_', true) . '.' . $extension;
    $target_path = UPLOAD_DIR . $filename;

    // رفع الملف
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => false, 'message' => 'فشل حفظ الملف'];
    }

    // حذف الصورة القديمة إن وجدت
    if ($old_image && file_exists(UPLOAD_DIR . $old_image)) {
        unlink(UPLOAD_DIR . $old_image);
    }

    return ['success' => true, 'filename' => $filename];
}

/**
 * حذف صورة
 */
function deleteImage($filename) {
    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
        return unlink(UPLOAD_DIR . $filename);
    }
    return false;
}

/**
 * تنسيق التاريخ
 */
function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * تنسيق المبلغ
 */
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',') . ' د.ع';
}

/**
 * عرض رسالة
 */
function showMessage($message, $type = 'info') {
    $types = [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'warning' => 'تحذير',
        'info' => 'معلومة'
    ];
    
    $class = $type;
    $title = $types[$type] ?? 'معلومة';
    
    return "<div class='alert alert-{$class}'><strong>{$title}:</strong> {$message}</div>";
}

/**
 * إعادة التوجيه
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * التحقق من صحة الهاتف
 */
function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]+$/', $phone);
}

/**
 * الحصول على اسم القسم
 */
function getDepartmentName($id) {
    if (!$id) return 'غير محدد';
    
    $db = getDB();
    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    return $result ? $result['name'] : 'غير محدد';
}

/**
 * الحصول على جميع الأقسام
 */
function getAllDepartments() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM departments ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * التحقق من وجود رمز موظف
 */
function employeeCodeExists($code, $exclude_id = null) {
    $db = getDB();
    if ($exclude_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = ? AND id != ?");
        $stmt->execute([$code, $exclude_id]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = ?");
        $stmt->execute([$code]);
    }
    return $stmt->fetchColumn() > 0;
}

/**
 * التحقق من وجود بريد إلكتروني
 */
function emailExists($email, $exclude_id = null) {
    $db = getDB();
    if ($exclude_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$email, $exclude_id]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = ?");
        $stmt->execute([$email]);
    }
    return $stmt->fetchColumn() > 0;
}

/**
 * الحصول على معلومات الموظف من user_id
 * @param int $user_id معرف المستخدم
 * @return array|null معلومات الموظف أو null إذا لم يوجد
 */
function getEmployeeByUserId($user_id) {
    if (empty($user_id)) {
        return null;
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT e.*, d.name as department_name 
                              FROM employees e 
                              LEFT JOIN departments d ON e.department_id = d.id 
                              WHERE e.user_id = ? AND e.status = 'active'");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getEmployeeByUserId error: " . $e->getMessage());
        return null;
    }
}

/**
 * الحصول على رصيد الإجازات للموظف
 * @param int $employee_id معرف الموظف
 * @return array|null معلومات الرصيد أو null
 */
function getLeaveBalance($employee_id) {
    if (empty($employee_id)) {
        return null;
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM leave_balance WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $balance = $stmt->fetch();
        
        // إذا لم يكن موجوداً، إنشاؤه بقيم افتراضية
        if (!$balance) {
            $stmt = $db->prepare("INSERT INTO leave_balance (employee_id, total_balance, monthly_balance, remaining_balance) VALUES (?, 104, 2, 104)");
            $stmt->execute([$employee_id]);
            
            $stmt = $db->prepare("SELECT * FROM leave_balance WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            $balance = $stmt->fetch();
        }
        
        return $balance;
    } catch (Exception $e) {
        error_log("getLeaveBalance error: " . $e->getMessage());
        return null;
    }
}

/**
 * تحديث رصيد الإجازات بعد الموافقة
 * @param int $employee_id معرف الموظف
 * @param float $days عدد الأيام
 * @return bool
 */
function updateLeaveBalance($employee_id, $days) {
    if (empty($employee_id) || $days <= 0) {
        return false;
    }
    
    $db = getDB();
    try {
        $stmt = $db->prepare("UPDATE leave_balance 
                              SET remaining_balance = remaining_balance - ?, 
                                  used_this_year = used_this_year + ? 
                              WHERE employee_id = ? AND remaining_balance >= ?");
        return $stmt->execute([$days, $days, $employee_id, $days]);
    } catch (Exception $e) {
        error_log("updateLeaveBalance error: " . $e->getMessage());
        return false;
    }
}

/**
 * حساب عدد الأيام بين تاريخين
 * @param string $start_date تاريخ البداية
 * @param string $end_date تاريخ النهاية
 * @param string|null $start_time وقت البداية (للإجازة الزمنية)
 * @param string|null $end_time وقت النهاية (للإجازة الزمنية)
 * @return float
 */
function calculateLeaveDays($start_date, $end_date, $start_time = null, $end_time = null) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    // إذا كانت نفس اليوم (إجازة زمنية)
    if ($start_date === $end_date && $start_time && $end_time) {
        $start_dt = new DateTime($start_date . ' ' . $start_time);
        $end_dt = new DateTime($end_date . ' ' . $end_time);
        $diff = $end_dt->diff($start_dt);
        $hours = $diff->h + ($diff->i / 60);
        return round($hours / 8, 2); // 8 ساعات = يوم واحد
    }
    
    // حساب الأيام
    $diff = $end->diff($start);
    $days = $diff->days + 1; // +1 لتضمين اليوم الأول
    
    return (float)$days;
}

/**
 * التحقق من وجود تعارض في الإجازات
 * @param int $employee_id معرف الموظف
 * @param string $start_date تاريخ البداية
 * @param string $end_date تاريخ النهاية
 * @param int|null $exclude_id استثناء إجازة معينة (للتعديل)
 * @return bool true إذا كان هناك تعارض
 */
function hasLeaveConflict($employee_id, $start_date, $end_date, $exclude_id = null) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) FROM employee_leaves 
            WHERE employee_id = ? 
            AND status IN ('pending', 'approved')
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    
    $params = [$employee_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("hasLeaveConflict error: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على أنواع الإجازات
 * @return array
 */
function getLeaveTypes() {
    return [
        'ordinary' => 'إجازة اعتيادية',
        'time' => 'إجازة زمنية',
        'medical' => 'فحص طبي',
        'emergency' => 'إجازة طارئة',
        'unpaid' => 'إجازة بدون راتب'
    ];
}

/**
 * الحصول على حالة الإجازة بالعربية
 * @param string $status حالة الإجازة
 * @return string
 */
function getLeaveStatusText($status) {
    $statuses = [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'rejected' => 'مرفوضة',
        'cancelled' => 'ملغاة'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * إضافة سجل راتب جديد
 */
function addSalaryHistory($employee_id, $old_salary, $new_salary, $effective_date, $reason = '', $notes = '', $created_by = null) {
    $db = getDB();
    
    // حساب نوع التغيير والمبلغ
    $change_type = 'adjustment';
    $change_amount = 0;
    $change_percentage = 0;
    
    if ($old_salary === null || $old_salary == 0) {
        $change_type = 'initial';
    } elseif ($new_salary > $old_salary) {
        $change_type = 'increase';
        $change_amount = $new_salary - $old_salary;
        $change_percentage = ($old_salary > 0) ? (($change_amount / $old_salary) * 100) : 0;
    } elseif ($new_salary < $old_salary) {
        $change_type = 'decrease';
        $change_amount = $old_salary - $new_salary;
        $change_percentage = ($old_salary > 0) ? (($change_amount / $old_salary) * 100) : 0;
    }
    
    $stmt = $db->prepare("INSERT INTO salary_history 
                         (employee_id, old_salary, new_salary, change_type, change_amount, change_percentage, effective_date, reason, notes, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $employee_id,
        $old_salary,
        $new_salary,
        $change_type,
        $change_amount,
        $change_percentage,
        $effective_date,
        $reason,
        $notes,
        $created_by
    ]);
}

/**
 * الحصول على سجل الرواتب لموظف
 */
function getSalaryHistory($employee_id, $limit = null) {
    $db = getDB();
    
    $sql = "SELECT sh.*, u.username as created_by_name 
            FROM salary_history sh
            LEFT JOIN users u ON sh.created_by = u.id
            WHERE sh.employee_id = ?
            ORDER BY sh.effective_date DESC, sh.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$employee_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على آخر راتب لموظف
 */
function getLastSalary($employee_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM salary_history 
                         WHERE employee_id = ? 
                         ORDER BY effective_date DESC, created_at DESC 
                         LIMIT 1");
    $stmt->execute([$employee_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث راتب موظف مع تسجيل في السجل
 */
function updateEmployeeSalary($employee_id, $new_salary, $effective_date, $reason = '', $notes = '', $created_by = null) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // الحصول على الراتب الحالي
        $stmt = $db->prepare("SELECT salary FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        
        if (!$employee) {
            throw new Exception('الموظف غير موجود');
        }
        
        $old_salary = (float)$employee['salary'];
        
        // تحديث الراتب في جدول الموظفين
        $stmt = $db->prepare("UPDATE employees SET salary = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_salary, $employee_id]);
        
        // إضافة سجل في salary_history
        addSalaryHistory($employee_id, $old_salary, $new_salary, $effective_date, $reason, $notes, $created_by);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * الحصول على إحصائيات الرواتب
 */
function getSalaryStatistics($department_id = null) {
    $db = getDB();
    
    $where = "WHERE e.status = 'active'";
    $params = [];
    
    if ($department_id) {
        $where .= " AND e.department_id = ?";
        $params[] = $department_id;
    }
    
    $sql = "SELECT 
            COUNT(*) as total_employees,
            SUM(e.salary) as total_salary,
            AVG(e.salary) as avg_salary,
            MAX(e.salary) as max_salary,
            MIN(e.salary) as min_salary
            FROM employees e
            $where";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على نص نوع التغيير
 */
function getSalaryChangeTypeText($type) {
    $types = [
        'increase' => 'زيادة',
        'decrease' => 'تخفيض',
        'initial' => 'راتب ابتدائي',
        'adjustment' => 'تعديل'
    ];
    
    return $types[$type] ?? $type;
}

/* ============================================
   دوال الحضور والانصراف
   ============================================ */

/**
 * تسجيل الحضور والانصراف
 */
function recordAttendance($employee_id, $attendance_date, $time_in = null, $time_out = null, $schedule_id = null, $day_type = 'work_day', $leave_taken = null, $notes = null, $created_by = null) {
    $db = getDB();
    
    // حساب الوقت الإضافي والتأخير والخروج المبكر
    $overtime_hours = 0;
    $work_hours_difference = 0;
    $late_arrival_minutes = 0;
    $early_departure_minutes = 0;
    
    if ($day_type === 'work_day' && $time_in && $time_out && $schedule_id) {
        // الحصول على الجدول
        $schedule = getSchedule($schedule_id);
        if ($schedule) {
            $schedule_start = strtotime($schedule['start_time']);
            $schedule_end = strtotime($schedule['end_time']);
            $time_in_ts = strtotime($time_in);
            $time_out_ts = strtotime($time_out);
            
            // حساب التأخير
            if ($time_in_ts > $schedule_start) {
                $late_arrival_minutes = round(($time_in_ts - $schedule_start) / 60);
            }
            
            // حساب الخروج المبكر
            if ($time_out_ts < $schedule_end) {
                $early_departure_minutes = round(($schedule_end - $time_out_ts) / 60);
            }
            
            // حساب ساعات العمل الفعلية
            $actual_hours = ($time_out_ts - $time_in_ts) / 3600;
            $scheduled_hours = $schedule['work_hours'];
            
            // حساب الوقت الإضافي
            if ($time_out_ts > $schedule_end) {
                $overtime_hours = round(($time_out_ts - $schedule_end) / 3600, 2);
            }
            
            // حساب فارق ساعات العمل
            $work_hours_difference = round($actual_hours - $scheduled_hours, 2);
        }
    }
    
    // إدراج أو تحديث السجل
    $stmt = $db->prepare("INSERT INTO attendance 
                         (employee_id, attendance_date, day_type, schedule_id, time_in, time_out, 
                          overtime_hours, work_hours_difference, late_arrival_minutes, early_departure_minutes, 
                          leave_taken, notes, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         day_type = VALUES(day_type),
                         schedule_id = VALUES(schedule_id),
                         time_in = VALUES(time_in),
                         time_out = VALUES(time_out),
                         overtime_hours = VALUES(overtime_hours),
                         work_hours_difference = VALUES(work_hours_difference),
                         late_arrival_minutes = VALUES(late_arrival_minutes),
                         early_departure_minutes = VALUES(early_departure_minutes),
                         leave_taken = VALUES(leave_taken),
                         notes = VALUES(notes),
                         updated_at = CURRENT_TIMESTAMP");
    
    return $stmt->execute([
        $employee_id, $attendance_date, $day_type, $schedule_id, $time_in, $time_out,
        $overtime_hours, $work_hours_difference, $late_arrival_minutes, $early_departure_minutes,
        $leave_taken, $notes, $created_by
    ]);
}

/**
 * الحصول على سجل الحضور لموظف
 */
function getAttendance($employee_id, $start_date = null, $end_date = null) {
    $db = getDB();
    
    $sql = "SELECT a.*, s.schedule_name, s.start_time as schedule_start, s.end_time as schedule_end
            FROM attendance a
            LEFT JOIN schedules s ON a.schedule_id = s.id
            WHERE a.employee_id = ?";
    
    $params = [$employee_id];
    
    if ($start_date) {
        $sql .= " AND a.attendance_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date) {
        $sql .= " AND a.attendance_date <= ?";
        $params[] = $end_date;
    }
    
    $sql .= " ORDER BY a.attendance_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على الحضور الأسبوعي
 */
function getWeeklyAttendance($employee_id, $week_start_date) {
    $db = getDB();
    
    // حساب تاريخ نهاية الأسبوع (7 أيام)
    $week_end_date = date('Y-m-d', strtotime($week_start_date . ' +6 days'));
    
    $sql = "SELECT a.*, s.schedule_name, s.start_time as schedule_start, s.end_time as schedule_end
            FROM attendance a
            LEFT JOIN schedules s ON a.schedule_id = s.id
            WHERE a.employee_id = ? 
            AND a.attendance_date >= ? 
            AND a.attendance_date <= ?
            ORDER BY a.attendance_date ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$employee_id, $week_start_date, $week_end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جدول زمني
 */
function getSchedule($schedule_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM schedules WHERE id = ? AND is_active = 1");
    $stmt->execute([$schedule_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * الحصول على جميع الجداول النشطة
 */
function getActiveSchedules() {
    $db = getDB();
    
    // التحقق من وجود جدول schedules
    try {
        $db->query("SELECT 1 FROM schedules LIMIT 1");
    } catch (PDOException $e) {
        // إذا كان الجدول غير موجود، إرجاع مصفوفة فارغة
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
            return [];
        }
        throw $e;
    }
    
    $stmt = $db->query("SELECT * FROM schedules WHERE is_active = 1 ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * تحديد نوع اليوم (يوم عمل أو عطلة)
 */
function getDayType($date) {
    $day_of_week = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
    
    // الجمعة والسبت والأحد = عطلة (في العراق)
    if (in_array($day_of_week, [5, 6, 0])) {
        return 'holiday';
    }
    
    return 'work_day';
}

/**
 * الحصول على اسم اليوم بالعربية
 */
function getDayNameArabic($date) {
    $days = [
        'Sunday' => 'الأحد',
        'Monday' => 'الإثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Saturday' => 'السبت'
    ];
    
    $day_name = date('l', strtotime($date));
    return $days[$day_name] ?? $day_name;
}

/* ============================================
   دوال السجلات
   ============================================ */

/**
 * إضافة سجل جديد
 */
function addRecord($employee_id, $record_type, $title, $description, $record_date, $document_file = null, $document_path = null, $created_by = null) {
    $db = getDB();
    
    $stmt = $db->prepare("INSERT INTO employee_records 
                         (employee_id, record_type, title, description, record_date, document_file, document_path, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $employee_id, $record_type, $title, $description, $record_date, 
        $document_file, $document_path, $created_by
    ]) ? $db->lastInsertId() : false;
}

/**
 * الحصول على سجلات موظف
 */
function getEmployeeRecords($employee_id, $record_type = null, $status = 'active') {
    $db = getDB();
    
    $sql = "SELECT r.*, u.username as created_by_name 
            FROM employee_records r
            LEFT JOIN users u ON r.created_by = u.id
            WHERE r.employee_id = ? AND r.status = ?";
    
    $params = [$employee_id, $status];
    
    if ($record_type) {
        $sql .= " AND r.record_type = ?";
        $params[] = $record_type;
    }
    
    $sql .= " ORDER BY r.record_date DESC, r.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على سجل واحد
 */
function getRecord($record_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT r.*, u.username as created_by_name, e.first_name, e.last_name, e.employee_code
                          FROM employee_records r
                          LEFT JOIN users u ON r.created_by = u.id
                          LEFT JOIN employees e ON r.employee_id = e.id
                          WHERE r.id = ?");
    $stmt->execute([$record_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث سجل
 */
function updateRecord($record_id, $title, $description, $record_date, $record_type = null) {
    $db = getDB();
    
    $sql = "UPDATE employee_records SET title = ?, description = ?, record_date = ?";
    $params = [$title, $description, $record_date];
    
    if ($record_type) {
        $sql .= ", record_type = ?";
        $params[] = $record_type;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $record_id;
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * حذف سجل (soft delete)
 */
function deleteRecord($record_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE employee_records SET status = 'deleted' WHERE id = ?");
    return $stmt->execute([$record_id]);
}

/**
 * أرشفة سجل
 */
function archiveRecord($record_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE employee_records SET status = 'archived' WHERE id = ?");
    return $stmt->execute([$record_id]);
}

/**
 * الحصول على أنواع السجلات
 */
function getRecordTypes() {
    return [
        'personal' => 'سجل شخصي',
        'employment' => 'سجل توظيف',
        'attendance' => 'سجل حضور',
        'leave' => 'سجل إجازة',
        'salary' => 'سجل راتب',
        'evaluation' => 'سجل تقييم',
        'promotion' => 'سجل ترقية',
        'disciplinary' => 'سجل تأديبي',
        'training' => 'سجل تدريبي',
        'certificate' => 'شهادة',
        'other' => 'أخرى'
    ];
}

/**
 * الحصول على نص نوع السجل
 */
function getRecordTypeText($type) {
    $types = getRecordTypes();
    return $types[$type] ?? $type;
}


