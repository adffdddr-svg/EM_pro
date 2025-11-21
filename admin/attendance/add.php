<?php
/**
 * Employee Management System
 * إضافة سجل حضور وانصراف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$error = '';
$success = '';

// التحقق من وجود الجداول
try {
    $db->query("SELECT 1 FROM attendance LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error = "جداول نظام الحضور غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_attendance_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// الحصول على الموظفين والجداول
$employees_stmt = $db->query("SELECT id, first_name, last_name, employee_code FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

// التحقق من وجود جدول schedules قبل جلب الجداول
$schedules = [];
try {
    $db->query("SELECT 1 FROM schedules LIMIT 1");
    $schedules = getActiveSchedules();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $error = "جداول نظام الحضور غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_attendance_table.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $attendance_date = cleanInput($_POST['attendance_date'] ?? '');
    $day_type = cleanInput($_POST['day_type'] ?? 'work_day');
    $schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : null;
    $time_in = cleanInput($_POST['time_in'] ?? '');
    $time_out = cleanInput($_POST['time_out'] ?? '');
    $leave_taken = cleanInput($_POST['leave_taken'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    
    // التحقق من المدخلات
    if ($employee_id <= 0) {
        $error = 'يرجى اختيار الموظف';
    } elseif (empty($attendance_date)) {
        $error = 'التاريخ مطلوب';
    } elseif ($day_type === 'work_day' && empty($schedule_id)) {
        $error = 'يرجى اختيار الجدول ليوم العمل';
    } else {
        // إذا كان يوم عطلة، لا نحتاج وقت الحضور والانصراف
        if ($day_type === 'holiday') {
            $time_in = null;
            $time_out = null;
            $schedule_id = null;
        }
        
        try {
            if (recordAttendance($employee_id, $attendance_date, $time_in ?: null, $time_out ?: null, $schedule_id, $day_type, $leave_taken ?: null, $notes ?: null, $_SESSION['user_id'])) {
                $success = 'تم تسجيل الحضور بنجاح';
                // إعادة تعيين النموذج
                $_POST = [];
            } else {
                $error = 'حدث خطأ أثناء تسجيل الحضور';
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

$page_title = 'إضافة سجل حضور';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">➕ إضافة سجل حضور وانصراف</h1>
        <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="btn btn-secondary">← رجوع</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>📝 بيانات الحضور</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="form">
                <div class="form-group">
                    <label>الموظف <span class="required">*</span></label>
                    <select name="employee_id" required>
                        <option value="">اختر الموظف</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>التاريخ <span class="required">*</span></label>
                    <input type="date" name="attendance_date" value="<?php echo isset($_POST['attendance_date']) ? htmlspecialchars($_POST['attendance_date']) : date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>نوع اليوم <span class="required">*</span></label>
                    <select name="day_type" id="day_type" required onchange="toggleWorkDayFields()">
                        <option value="work_day" <?php echo (!isset($_POST['day_type']) || $_POST['day_type'] == 'work_day') ? 'selected' : ''; ?>>يوم عمل</option>
                        <option value="holiday" <?php echo (isset($_POST['day_type']) && $_POST['day_type'] == 'holiday') ? 'selected' : ''; ?>>يوم عطلة</option>
                    </select>
                </div>

                <div id="work_day_fields">
                    <div class="form-group">
                        <label>الجدول <span class="required">*</span></label>
                        <select name="schedule_id" id="schedule_id">
                            <option value="">اختر الجدول</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>" <?php echo (isset($_POST['schedule_id']) && $_POST['schedule_id'] == $schedule['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($schedule['schedule_name'] . ' (' . date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>وقت الحضور</label>
                        <input type="time" name="time_in" value="<?php echo isset($_POST['time_in']) ? htmlspecialchars($_POST['time_in']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>وقت الانصراف</label>
                        <input type="time" name="time_out" value="<?php echo isset($_POST['time_out']) ? htmlspecialchars($_POST['time_out']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>الإجازة المأخوذة</label>
                    <input type="text" name="leave_taken" value="<?php echo isset($_POST['leave_taken']) ? htmlspecialchars($_POST['leave_taken']) : ''; ?>" placeholder="مثل: غائب، إجازة، إلخ">
                </div>

                <div class="form-group">
                    <label>الملاحظات</label>
                    <textarea name="notes" rows="3" placeholder="أي ملاحظات إضافية"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 حفظ</button>
                    <a href="<?php echo SITE_URL; ?>/admin/attendance/index.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleWorkDayFields() {
    const dayType = document.getElementById('day_type').value;
    const workDayFields = document.getElementById('work_day_fields');
    const scheduleSelect = document.getElementById('schedule_id');
    
    if (dayType === 'holiday') {
        workDayFields.style.display = 'none';
        scheduleSelect.removeAttribute('required');
    } else {
        workDayFields.style.display = 'block';
        scheduleSelect.setAttribute('required', 'required');
    }
}

// تشغيل عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    toggleWorkDayFields();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

