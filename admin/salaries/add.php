<?php
/**
 * Employee Management System
 * إضافة/تعديل راتب
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'إضافة راتب جديد';
$db = getDB();
$error = '';
$success = '';

// التحقق من وجود جدول salary_history وإنشاؤه إذا لم يكن موجوداً
try {
    $db->query("SELECT 1 FROM salary_history LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS salary_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            old_salary DECIMAL(10, 2) NULL,
            new_salary DECIMAL(10, 2) NOT NULL,
            change_type ENUM('increase', 'decrease', 'initial', 'adjustment') DEFAULT 'adjustment',
            change_amount DECIMAL(10, 2) NULL,
            change_percentage DECIMAL(5, 2) NULL,
            effective_date DATE NOT NULL,
            reason TEXT,
            notes TEXT,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_employee_id (employee_id),
            INDEX idx_effective_date (effective_date),
            INDEX idx_change_type (change_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $create_error) {
        $error = "جدول سجل الرواتب غير موجود. يرجى <a href='" . SITE_URL . "/database/create_salary_table.php' style='color: #007bff; font-weight: bold;'>النقر هنا</a> لإنشاء الجدول.";
    }
}

// الحصول على معرف الموظف
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// جلب بيانات الموظف
$employee = null;
if ($employee_id > 0) {
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        redirect(SITE_URL . '/admin/salaries/index.php');
    }
}

// جلب جميع الموظفين
$stmt = $db->query("SELECT id, employee_code, first_name, last_name, salary FROM employees WHERE status = 'active' ORDER BY first_name, last_name");
$all_employees = $stmt->fetchAll();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $new_salary = isset($_POST['new_salary']) ? (float)$_POST['new_salary'] : 0;
    $effective_date = cleanInput($_POST['effective_date'] ?? '');
    $reason = cleanInput($_POST['reason'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    
    if ($employee_id <= 0) {
        $error = 'يرجى اختيار موظف';
    } elseif ($new_salary <= 0) {
        $error = 'الراتب يجب أن يكون أكبر من صفر';
    } elseif (empty($effective_date)) {
        $error = 'تاريخ السريان مطلوب';
    } else {
        try {
            updateEmployeeSalary($employee_id, $new_salary, $effective_date, $reason, $notes, $_SESSION['user_id']);
            $success = 'تم تحديث الراتب بنجاح';
            
            // إعادة توجيه بعد 2 ثانية
            header("refresh:2;url=" . SITE_URL . "/admin/salaries/view.php?id=$employee_id");
        } catch (Exception $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
.salary-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
}

.form-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-section h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.salary-preview {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.salary-preview h4 {
    color: #666;
    margin-bottom: 15px;
}

.salary-comparison {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
}

.comparison-item {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
}

.comparison-item .label {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.comparison-item .value {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary-color);
}

.change-info {
    text-align: center;
    padding: 15px;
    margin-top: 15px;
    border-radius: 8px;
    font-weight: bold;
}

.change-info.increase {
    background: #d4edda;
    color: #155724;
}

.change-info.decrease {
    background: #f8d7da;
    color: #721c24;
}

.change-info.no-change {
    background: #e2e3e5;
    color: #383d41;
}
</style>

<div class="salary-form-container">
    <h1 style="margin-bottom: 30px;">💰 <?php echo $employee ? 'تعديل راتب: ' . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) : 'إضافة راتب جديد'; ?></h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" id="salaryForm">
        <div class="form-section">
            <h3>معلومات الراتب</h3>
            
            <div class="form-group">
                <label for="employee_id">الموظف <span class="required">*</span></label>
                <select id="employee_id" name="employee_id" class="form-control" required onchange="loadEmployeeSalary(this.value)">
                    <option value="">-- اختر موظف --</option>
                    <?php foreach ($all_employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" 
                                data-salary="<?php echo $emp['salary']; ?>"
                                <?php echo ($employee && $employee['id'] == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="current_salary">الراتب الحالي</label>
                <input type="text" id="current_salary" class="form-control" readonly 
                       value="<?php echo $employee ? number_format($employee['salary'], 2) . ' د.ع' : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="new_salary">الراتب الجديد <span class="required">*</span></label>
                <input type="number" id="new_salary" name="new_salary" class="form-control" 
                       step="0.01" min="0" required 
                       value="<?php echo $employee ? htmlspecialchars($employee['salary']) : ''; ?>"
                       oninput="calculateChange()">
            </div>
            
            <div class="form-group">
                <label for="effective_date">تاريخ السريان <span class="required">*</span></label>
                <input type="date" id="effective_date" name="effective_date" class="form-control" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="reason">سبب التغيير</label>
                <select id="reason" name="reason" class="form-control">
                    <option value="">-- اختر السبب --</option>
                    <option value="ترقية">ترقية</option>
                    <option value="زيادة سنوية">زيادة سنوية</option>
                    <option value="تعديل وظيفي">تعديل وظيفي</option>
                    <option value="تقييم أداء">تقييم أداء</option>
                    <option value="تعديل إداري">تعديل إداري</option>
                    <option value="أخرى">أخرى</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">ملاحظات</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" 
                          placeholder="ملاحظات إضافية عن التغيير..."></textarea>
            </div>
            
            <!-- معاينة التغيير -->
            <div class="salary-preview" id="salaryPreview" style="display: none;">
                <h4>معاينة التغيير</h4>
                <div class="salary-comparison">
                    <div class="comparison-item">
                        <div class="label">الراتب الحالي</div>
                        <div class="value" id="previewOldSalary">0.00</div>
                    </div>
                    <div class="comparison-item">
                        <div class="label">الراتب الجديد</div>
                        <div class="value" id="previewNewSalary">0.00</div>
                    </div>
                </div>
                <div class="change-info" id="changeInfo">
                    <div id="changeAmount">0.00 د.ع</div>
                    <div id="changePercentage" style="font-size: 14px; margin-top: 5px;">0%</div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="index.php" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">💾 حفظ الراتب</button>
        </div>
    </form>
</div>

<script>
function loadEmployeeSalary(employeeId) {
    const select = document.getElementById('employee_id');
    const option = select.options[select.selectedIndex];
    const currentSalary = option.getAttribute('data-salary') || 0;
    
    document.getElementById('current_salary').value = parseFloat(currentSalary).toFixed(2) + ' د.ع';
    document.getElementById('new_salary').value = currentSalary;
    
    calculateChange();
}

function calculateChange() {
    const currentSalary = parseFloat(document.getElementById('current_salary').value.replace(/[^\d.]/g, '')) || 0;
    const newSalary = parseFloat(document.getElementById('new_salary').value) || 0;
    
    if (currentSalary > 0 && newSalary > 0) {
        const change = newSalary - currentSalary;
        const changePercent = currentSalary > 0 ? ((change / currentSalary) * 100).toFixed(2) : 0;
        
        document.getElementById('previewOldSalary').textContent = currentSalary.toFixed(2) + ' د.ع';
        document.getElementById('previewNewSalary').textContent = newSalary.toFixed(2) + ' د.ع';
        document.getElementById('changeAmount').textContent = 
            (change >= 0 ? '+' : '') + change.toFixed(2) + ' د.ع';
        document.getElementById('changePercentage').textContent = 
            (changePercent >= 0 ? '+' : '') + changePercent + '%';
        
        const changeInfo = document.getElementById('changeInfo');
        changeInfo.className = 'change-info';
        if (change > 0) {
            changeInfo.classList.add('increase');
        } else if (change < 0) {
            changeInfo.classList.add('decrease');
        } else {
            changeInfo.classList.add('no-change');
        }
        
        document.getElementById('salaryPreview').style.display = 'block';
    } else {
        document.getElementById('salaryPreview').style.display = 'none';
    }
}

// تهيئة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const employeeId = document.getElementById('employee_id').value;
    if (employeeId) {
        loadEmployeeSalary(employeeId);
    }
    
    document.getElementById('new_salary').addEventListener('input', calculateChange);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

