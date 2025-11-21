<?php
/**
 * Employee Management System
 * دوال التقارير والإحصائيات
 */

// منع الوصول المباشر
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not allowed');
}

/**
 * الحصول على إحصائيات الموظفين حسب القسم
 */
function getEmployeesByDepartment($department_id = null) {
    $db = getDB();
    
    $sql = "SELECT d.id, d.name, 
            COUNT(e.id) as employee_count,
            COUNT(CASE WHEN e.status = 'active' THEN 1 END) as active_count,
            COUNT(CASE WHEN e.status = 'inactive' THEN 1 END) as inactive_count
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id";
    
    if ($department_id) {
        $sql .= " WHERE d.id = :dept_id";
    }
    
    $sql .= " GROUP BY d.id, d.name ORDER BY employee_count DESC";
    
    $stmt = $db->prepare($sql);
    if ($department_id) {
        $stmt->bindParam(':dept_id', $department_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الموظفين الجدد حسب الشهر
 */
function getNewEmployeesByMonth($year = null) {
    $db = getDB();
    
    if (!$year) {
        $year = date('Y');
    }
    
    $stmt = $db->prepare("SELECT 
                         MONTH(created_at) as month,
                         COUNT(*) as count
                         FROM employees
                         WHERE YEAR(created_at) = :year
                         GROUP BY MONTH(created_at)
                         ORDER BY month ASC");
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الحضور حسب التاريخ
 */
function getAttendanceStats($start_date = null, $end_date = null, $department_id = null) {
    $db = getDB();
    
    if (!$start_date) {
        $start_date = date('Y-m-01'); // أول يوم من الشهر الحالي
    }
    if (!$end_date) {
        $end_date = date('Y-m-t'); // آخر يوم من الشهر الحالي
    }
    
    $sql = "SELECT 
            DATE(attendance_date) as date,
            COUNT(*) as total_attendance,
            COUNT(DISTINCT employee_id) as unique_employees
            FROM attendance
            WHERE attendance_date BETWEEN :start_date AND :end_date";
    
    if ($department_id) {
        $sql .= " AND employee_id IN (
                    SELECT id FROM employees WHERE department_id = :dept_id
                 )";
    }
    
    $sql .= " GROUP BY DATE(attendance_date) ORDER BY date ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    if ($department_id) {
        $stmt->bindParam(':dept_id', $department_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الحضور حسب الموظف
 */
function getAttendanceByEmployee($start_date = null, $end_date = null, $limit = 10) {
    $db = getDB();
    
    if (!$start_date) {
        $start_date = date('Y-m-01');
    }
    if (!$end_date) {
        $end_date = date('Y-m-t');
    }
    
    $stmt = $db->prepare("SELECT 
                          e.id,
                          e.first_name,
                          e.last_name,
                          e.employee_code,
                          d.name as department_name,
                          COUNT(a.id) as attendance_count
                          FROM employees e
                          LEFT JOIN attendance a ON e.id = a.employee_id 
                              AND a.attendance_date BETWEEN :start_date AND :end_date
                          LEFT JOIN departments d ON e.department_id = d.id
                          WHERE e.status = 'active'
                          GROUP BY e.id, e.first_name, e.last_name, e.employee_code, d.name
                          ORDER BY attendance_count DESC
                          LIMIT :limit");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الإجازات حسب النوع
 */
function getLeavesByType($start_date = null, $end_date = null) {
    $db = getDB();
    
    if (!$start_date) {
        $start_date = date('Y-01-01'); // أول يوم من السنة
    }
    if (!$end_date) {
        $end_date = date('Y-12-31'); // آخر يوم من السنة
    }
    
    $stmt = $db->prepare("SELECT 
                         leave_type,
                         COUNT(*) as total_count,
                         SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                         SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                         FROM employee_leaves
                         WHERE start_date BETWEEN :start_date AND :end_date
                         GROUP BY leave_type
                         ORDER BY total_count DESC");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الإجازات حسب الشهر
 */
function getLeavesByMonth($year = null) {
    $db = getDB();
    
    if (!$year) {
        $year = date('Y');
    }
    
    $stmt = $db->prepare("SELECT 
                         MONTH(start_date) as month,
                         COUNT(*) as total_count,
                         SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
                         FROM employee_leaves
                         WHERE YEAR(start_date) = :year
                         GROUP BY MONTH(start_date)
                         ORDER BY month ASC");
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات الرواتب حسب القسم
 */
function getSalaryStatsByDepartment($department_id = null) {
    $db = getDB();
    
    $sql = "SELECT 
            d.id,
            d.name,
            COUNT(e.id) as employee_count,
            SUM(e.salary) as total_salary,
            AVG(e.salary) as avg_salary,
            MAX(e.salary) as max_salary,
            MIN(e.salary) as min_salary
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'";
    
    if ($department_id) {
        $sql .= " WHERE d.id = :dept_id";
    }
    
    $sql .= " GROUP BY d.id, d.name ORDER BY total_salary DESC";
    
    $stmt = $db->prepare($sql);
    if ($department_id) {
        $stmt->bindParam(':dept_id', $department_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على إحصائيات تغييرات الرواتب
 */
function getSalaryChangesStats($year = null) {
    $db = getDB();
    
    if (!$year) {
        $year = date('Y');
    }
    
    try {
        $stmt = $db->prepare("SELECT 
                             MONTH(effective_date) as month,
                             COUNT(*) as change_count,
                             SUM(CASE WHEN change_type = 'increase' THEN 1 ELSE 0 END) as increases,
                             SUM(CASE WHEN change_type = 'decrease' THEN 1 ELSE 0 END) as decreases,
                             AVG(change_amount) as avg_change
                             FROM salary_history
                             WHERE YEAR(effective_date) = :year
                             GROUP BY MONTH(effective_date)
                             ORDER BY month ASC");
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * الحصول على إحصائيات شاملة للأقسام
 */
function getDepartmentComprehensiveStats($department_id = null) {
    $db = getDB();
    
    $sql = "SELECT 
            d.id,
            d.name,
            COUNT(DISTINCT e.id) as total_employees,
            COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) as active_employees,
            COALESCE(SUM(e.salary), 0) as total_salary,
            COALESCE(AVG(e.salary), 0) as avg_salary,
            (SELECT COUNT(*) FROM attendance a 
             INNER JOIN employees emp ON a.employee_id = emp.id 
             WHERE emp.department_id = d.id 
             AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as attendance_count_30d,
            (SELECT COUNT(*) FROM employee_leaves el 
             INNER JOIN employees emp ON el.employee_id = emp.id 
             WHERE emp.department_id = d.id 
             AND el.status = 'pending') as pending_leaves
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id";
    
    if ($department_id) {
        $sql .= " WHERE d.id = :dept_id";
    }
    
    $sql .= " GROUP BY d.id, d.name ORDER BY total_employees DESC";
    
    $stmt = $db->prepare($sql);
    if ($department_id) {
        $stmt->bindParam(':dept_id', $department_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * الحصول على أسماء الأشهر بالعربية
 */
function getArabicMonthNames() {
    return [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
}

/**
 * تنسيق الرقم مع فواصل
 */
if (!function_exists('formatNumber')) {
    function formatNumber($number) {
        return number_format($number, 0, '.', ',');
    }
}

/**
 * تنسيق المبلغ المالي
 * ملاحظة: الدالة موجودة في functions.php، نستخدمها من هناك
 */
// formatCurrency موجودة في functions.php
