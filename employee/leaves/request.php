<?php
/**
 * Employee Management System
 * طلب إجازة جديدة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

// توجيه المدير
if (isAdmin()) {
    redirect(SITE_URL . '/admin/leaves/add.php');
}

// الحصول على معلومات الموظف
$employee = getEmployeeByUserId($_SESSION['user_id']);

if (!$employee) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$db = getDB();
$employee_id = $employee['id'];
$error = '';
$success = '';

// الحصول على جميع الموظفين (لاختيار الموظف البديل)
$stmt = $db->query("SELECT id, first_name, last_name, employee_code, position FROM employees WHERE status = 'active' AND id != ? ORDER BY first_name");
$stmt->execute([$employee_id]);
$employees = $stmt->fetchAll();

// الحصول على رصيد الإجازات
$balance = getLeaveBalance($employee_id);

$leave_types = getLeaveTypes();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = cleanInput($_POST['leave_type'] ?? '');
    $start_date = cleanInput($_POST['start_date'] ?? '');
    $end_date = cleanInput($_POST['end_date'] ?? '');
    $start_time = cleanInput($_POST['start_time'] ?? '');
    $end_time = cleanInput($_POST['end_time'] ?? '');
    $purpose = cleanInput($_POST['purpose'] ?? '');
    $substitute_employee_id = isset($_POST['substitute_employee_id']) ? (int)$_POST['substitute_employee_id'] : 0;
    
    // التحقق من المدخلات
    if (empty($leave_type) || !array_key_exists($leave_type, $leave_types)) {
        $error = 'نوع الإجازة غير صحيح';
    } elseif (empty($start_date) || empty($end_date)) {
        $error = 'تاريخ البداية والنهاية مطلوبان';
    } elseif (strtotime($start_date) === false || strtotime($end_date) === false) {
        $error = 'التواريخ غير صحيحة';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
    } elseif (strtotime($start_date) < strtotime('today')) {
        $error = 'لا يمكن طلب إجازة بتاريخ ماضي';
    } else {
        // حساب عدد الأيام
        $days = calculateLeaveDays($start_date, $end_date, $start_time ?: null, $end_time ?: null);
        
        // التحقق من التعارض
        if (hasLeaveConflict($employee_id, $start_date, $end_date)) {
            $error = 'يوجد تعارض مع إجازة أخرى في نفس الفترة';
        } else {
            // التحقق من الرصيد (للإجازات الاعتيادية فقط)
            if ($leave_type === 'ordinary') {
                if ($balance && $balance['remaining_balance'] < $days) {
                    $error = 'الرصيد المتبقي غير كافي. الرصيد المتبقي: ' . $balance['remaining_balance'] . ' يوم';
                }
            }
            
            if (empty($error)) {
                try {
                    $stmt = $db->prepare("INSERT INTO employee_leaves 
                                         (employee_id, leave_type, start_date, end_date, start_time, end_time, days, purpose, substitute_employee_id, status) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $employee_id,
                        $leave_type,
                        $start_date,
                        $end_date,
                        $start_time ?: null,
                        $end_time ?: null,
                        $days,
                        $purpose ?: null,
                        $substitute_employee_id > 0 ? $substitute_employee_id : null
                    ]);
                    
                    $leave_id = $db->lastInsertId();
                    
                    // إضافة الموافقات الأولية
                    $approvers = [
                        ['type' => 'leave_unit', 'name' => '', 'position' => 'مسؤول وحدة الإجازات'],
                        ['type' => 'direct_supervisor', 'name' => '', 'position' => 'المسؤول المباشر'],
                        ['type' => 'assistant_dean', 'name' => '', 'position' => 'معاون العميد الإداري']
                    ];
                    
                    foreach ($approvers as $approver) {
                        $stmt = $db->prepare("INSERT INTO leave_approvals (leave_id, approver_type, approver_name, approver_position, status) VALUES (?, ?, ?, ?, 'pending')");
                        $stmt->execute([$leave_id, $approver['type'], $approver['name'], $approver['position']]);
                    }
                    
                    $success = 'تم إرسال طلب الإجازة بنجاح. سيتم مراجعته من قبل المدير.';
                    header('Location: ' . SITE_URL . '/employee/leaves/my_leaves.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Error requesting leave: " . $e->getMessage());
                    $error = 'حدث خطأ أثناء إرسال طلب الإجازة: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'طلب إجازة جديدة';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">طلب إجازة جديدة</h3>
            <a href="<?php echo SITE_URL; ?>/employee/leaves/my_leaves.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- رصيد الإجازات -->
        <?php if ($balance): ?>
            <div class="balance-info">
                <h4>رصيد الإجازات</h4>
                <div class="balance-summary">
                    <div class="balance-item">
                        <span class="label">الرصيد المتبقي:</span>
                        <span class="value"><?php echo $balance['remaining_balance']; ?> يوم</span>
                    </div>
                    <div class="balance-item">
                        <span class="label">الرصيد الشهري:</span>
                        <span class="value"><?php echo $balance['monthly_balance']; ?> يوم</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form">
            <div class="form-group">
                <label for="leave_type">نوع الإجازة <span class="required">*</span></label>
                <select name="leave_type" id="leave_type" class="form-control" required>
                    <option value="">-- اختر نوع الإجازة --</option>
                    <?php foreach ($leave_types as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (isset($_POST['leave_type']) && $_POST['leave_type'] == $key) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">من تاريخ <span class="required">*</span></label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" id="start_time_group" style="display: none;">
                    <label for="start_time">من الساعة</label>
                    <input type="time" name="start_time" id="start_time" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">إلى تاريخ <span class="required">*</span></label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" id="end_time_group" style="display: none;">
                    <label for="end_time">إلى الساعة</label>
                    <input type="time" name="end_time" id="end_time" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="purpose">الغرض من الإجازة <span class="required">*</span></label>
                <textarea name="purpose" id="purpose" class="form-control" rows="3" 
                          placeholder="اذكر الغرض من الإجازة..." required><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="substitute_employee_id">الموظف البديل (اختياري)</label>
                <select name="substitute_employee_id" id="substitute_employee_id" class="form-control">
                    <option value="0">-- لا يوجد --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['substitute_employee_id']) && $_POST['substitute_employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <div id="days_calculator" style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin-top: 10px; display: none;">
                    <strong>عدد الأيام المحسوبة:</strong> <span id="calculated_days">0</span> يوم
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> إرسال الطلب
                </button>
                <a href="<?php echo SITE_URL; ?>/employee/leaves/my_leaves.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// إظهار/إخفاء حقول الوقت للإجازة الزمنية
document.getElementById('leave_type').addEventListener('change', function() {
    const isTimeLeave = this.value === 'time';
    document.getElementById('start_time_group').style.display = isTimeLeave ? 'block' : 'none';
    document.getElementById('end_time_group').style.display = isTimeLeave ? 'none' : 'none';
    
    if (isTimeLeave) {
        document.getElementById('start_time').required = true;
        document.getElementById('end_time').required = true;
    } else {
        document.getElementById('start_time').required = false;
        document.getElementById('end_time').required = false;
    }
    
    calculateDays();
});

// حساب عدد الأيام تلقائياً
function calculateDays() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const leaveType = document.getElementById('leave_type').value;
    const calculator = document.getElementById('days_calculator');
    const calculatedDays = document.getElementById('calculated_days');
    
    if (startDate && endDate) {
        calculator.style.display = 'block';
        
        if (leaveType === 'time' && startTime && endTime && startDate === endDate) {
            // حساب الساعات للإجازة الزمنية
            const start = new Date(startDate + 'T' + startTime);
            const end = new Date(endDate + 'T' + endTime);
            const diffMs = end - start;
            const diffHours = diffMs / (1000 * 60 * 60);
            const days = (diffHours / 8).toFixed(2);
            calculatedDays.textContent = days;
        } else {
            // حساب الأيام العادية
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            calculatedDays.textContent = diffDays;
        }
    } else {
        calculator.style.display = 'none';
    }
}

document.getElementById('start_date').addEventListener('change', function() {
    const endDate = document.getElementById('end_date');
    if (endDate.value && endDate.value < this.value) {
        endDate.value = this.value;
    }
    calculateDays();
});

document.getElementById('end_date').addEventListener('change', function() {
    const startDate = document.getElementById('start_date');
    if (startDate.value && this.value < startDate.value) {
        this.value = startDate.value;
    }
    calculateDays();
});

document.getElementById('start_time').addEventListener('change', calculateDays);
document.getElementById('end_time').addEventListener('change', calculateDays);
</script>

<style>
.balance-info {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
}

.balance-info h4 {
    margin: 0 0 15px 0;
    color: white;
}

.balance-summary {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.balance-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.balance-item .label {
    font-size: 14px;
    opacity: 0.9;
}

.balance-item .value {
    font-size: 24px;
    font-weight: 700;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

