<?php
/**
 * Employee Management System
 * إضافة إجازة جديدة
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$db = getDB();
$error = '';
$success = '';
$tables_missing = false;

// التحقق من وجود الجداول
try {
    $db->query("SELECT 1 FROM employee_leaves LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
        $tables_missing = true;
        $error = "جداول نظام الإجازات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_leaves_tables.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
    }
}

// الحصول على جميع الموظفين
try {
    $stmt = $db->query("SELECT id, first_name, last_name, employee_code, position, department_id FROM employees WHERE status = 'active' ORDER BY first_name");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $employees = [];
    if (!$tables_missing) {
        $error = 'حدث خطأ في جلب بيانات الموظفين: ' . $e->getMessage();
    }
}

$leave_types = getLeaveTypes();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $leave_type = cleanInput($_POST['leave_type'] ?? '');
    $start_date = cleanInput($_POST['start_date'] ?? '');
    $end_date = cleanInput($_POST['end_date'] ?? '');
    $start_time = cleanInput($_POST['start_time'] ?? '');
    $end_time = cleanInput($_POST['end_time'] ?? '');
    $purpose = cleanInput($_POST['purpose'] ?? '');
    $substitute_employee_id = isset($_POST['substitute_employee_id']) ? (int)$_POST['substitute_employee_id'] : 0;
    
    // التحقق من المدخلات
    if ($employee_id <= 0) {
        $error = 'يرجى اختيار الموظف';
    } elseif (empty($leave_type) || !array_key_exists($leave_type, $leave_types)) {
        $error = 'نوع الإجازة غير صحيح';
    } elseif (empty($start_date) || empty($end_date)) {
        $error = 'تاريخ البداية والنهاية مطلوبان';
    } elseif (strtotime($start_date) === false || strtotime($end_date) === false) {
        $error = 'التواريخ غير صحيحة';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
    } else {
        // حساب عدد الأيام
        $days = calculateLeaveDays($start_date, $end_date, $start_time ?: null, $end_time ?: null);
        
        // التحقق من التعارض
        if (hasLeaveConflict($employee_id, $start_date, $end_date)) {
            $error = 'يوجد تعارض مع إجازة أخرى في نفس الفترة';
        } else {
            // التحقق من الرصيد (للإجازات الاعتيادية فقط)
            if ($leave_type === 'ordinary') {
                $balance = getLeaveBalance($employee_id);
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
                    
                    $success = 'تم إضافة الإجازة بنجاح';
                    header('Location: ' . SITE_URL . '/admin/leaves/view.php?id=' . $leave_id);
                    exit();
                } catch (PDOException $e) {
                    error_log("Error adding leave: " . $e->getMessage());
                    
                    // إذا كان الخطأ بسبب عدم وجود الجدول
                    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
                        $error = "جداول نظام الإجازات غير موجودة. يرجى <a href='" . SITE_URL . "/database/create_leaves_tables.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>النقر هنا</a> لإنشاء الجداول تلقائياً.";
                    } else {
                        $error = 'حدث خطأ أثناء إضافة الإجازة: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$page_title = 'إضافة إجازة جديدة';
$additional_css = ['forms.css'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">إضافة إجازة جديدة</h3>
            <a href="<?php echo SITE_URL; ?>/admin/leaves/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة للقائمة
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin: 20px 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #856404; margin-left: 10px;"></i>
                <div style="display: inline-block;">
                    <strong style="color: #856404; font-size: 16px;">تحذير مهم:</strong>
                    <p style="color: #856404; margin: 10px 0 0 0; font-size: 14px; line-height: 1.6;">
                        <?php echo $error; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($tables_missing): ?>
            <div style="padding: 30px; text-align: center; background: #f8f9fa; border-radius: 8px; margin: 20px 0;">
                <i class="fas fa-database" style="font-size: 64px; color: #667eea; margin-bottom: 20px;"></i>
                <h3 style="color: #2c3e50; margin-bottom: 15px;">جداول قاعدة البيانات غير موجودة</h3>
                <p style="color: #666; margin-bottom: 25px; font-size: 16px;">
                    يجب إنشاء جداول نظام الإجازات أولاً قبل إضافة إجازات جديدة.
                </p>
                <a href="<?php echo SITE_URL; ?>/database/create_leaves_tables.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 16px;">
                    <i class="fas fa-magic"></i> إنشاء الجداول تلقائياً
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form" <?php echo $tables_missing ? 'style="display: none;"' : ''; ?>>
            <div class="form-group">
                <label for="employee_id">الموظف <span class="required">*</span></label>
                <select name="employee_id" id="employee_id" class="form-control" required>
                    <option value="">-- اختر الموظف --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

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
                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group" id="start_time_group" style="display: none;">
                    <label for="start_time">من الساعة</label>
                    <input type="time" name="start_time" id="start_time" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">إلى تاريخ <span class="required">*</span></label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group" id="end_time_group" style="display: none;">
                    <label for="end_time">إلى الساعة</label>
                    <input type="time" name="end_time" id="end_time" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="purpose">الغرض من الإجازة</label>
                <textarea name="purpose" id="purpose" class="form-control" rows="3" 
                          placeholder="اذكر الغرض من الإجازة..."><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
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
                <div id="balance_info" style="display: none; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-top: 10px;">
                    <strong>معلومات الرصيد:</strong>
                    <div id="balance_content"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ الإجازة
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/leaves/index.php" class="btn btn-secondary">
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
    } else {
        document.getElementById('start_time').required = false;
    }
});

// عرض معلومات الرصيد عند اختيار موظف
document.getElementById('employee_id').addEventListener('change', function() {
    const employeeId = this.value;
    const balanceInfo = document.getElementById('balance_info');
    const balanceContent = document.getElementById('balance_content');
    
    if (employeeId) {
        // جلب معلومات الرصيد عبر AJAX
        fetch('<?php echo SITE_URL; ?>/admin/leaves/get_balance.php?employee_id=' + employeeId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    balanceContent.innerHTML = `
                        <p>الرصيد الكلي: <strong>${data.balance.total_balance}</strong> يوم</p>
                        <p>الرصيد الشهري: <strong>${data.balance.monthly_balance}</strong> يوم</p>
                        <p>الرصيد المتبقي: <strong>${data.balance.remaining_balance}</strong> يوم</p>
                        <p>المستخدم هذا العام: <strong>${data.balance.used_this_year}</strong> يوم</p>
                    `;
                    balanceInfo.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    } else {
        balanceInfo.style.display = 'none';
    }
});

// حساب عدد الأيام تلقائياً
function calculateDays() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startDate && endDate) {
        // يمكن إضافة حساب تلقائي هنا
    }
}

document.getElementById('start_date').addEventListener('change', calculateDays);
document.getElementById('end_date').addEventListener('change', calculateDays);
document.getElementById('start_time').addEventListener('change', calculateDays);
document.getElementById('end_time').addEventListener('change', calculateDays);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
