<?php
/**
 * Employee Management System
 * إضافة موظف جديد
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin(); // يتطلب صلاحيات المدير

$db = getDB();
$error = '';
$success = '';
$fields_missing = false;

// التحقق من وجود الحقول الجديدة باستخدام SHOW COLUMNS
try {
    $stmt = $db->query("SHOW COLUMNS FROM employees");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // التحقق من وجود الحقول المطلوبة (جميع الحقول - 12 حقل)
    $required_fields = [
        'certificate', 
        'certificate_date', 
        'title', 
        'title_date', 
        'current_salary', 
        'new_salary', 
        'last_raise_date', 
        'entitlement_date', 
        'grade_entry_date', 
        'last_promotion_date', 
        'last_promotion_number', 
        'job_notes'
    ];
    
    $missing_fields = array_diff($required_fields, $columns);
    
    // إذا كان أي حقل مفقود، نعرض التحذير
    if (!empty($missing_fields)) {
        $fields_missing = true;
    } else {
        $fields_missing = false;
    }
} catch (PDOException $e) {
    // في حالة الخطأ، نفترض أن الحقول غير موجودة
    $fields_missing = true;
    error_log("Error checking fields: " . $e->getMessage());
}

// الحصول على الأقسام
$departments = getAllDepartments();

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        // تنظيف المدخلات
        $first_name = cleanInput($_POST['first_name'] ?? '');
        $last_name = cleanInput($_POST['last_name'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $phone = cleanInput($_POST['phone'] ?? '');
        $address = cleanInput($_POST['address'] ?? '');
        $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        $position = cleanInput($_POST['position'] ?? '');
        $salary = isset($_POST['salary']) ? (float)$_POST['salary'] : 0;
        $hire_date = cleanInput($_POST['hire_date'] ?? '');
        
        // الحقول الوظيفية الجديدة
        $specialization = cleanInput($_POST['specialization'] ?? '');
        $certificate = cleanInput($_POST['certificate'] ?? '');
        $certificate_date = cleanInput($_POST['certificate_date'] ?? '');
        $title = cleanInput($_POST['title'] ?? '');
        $title_date = cleanInput($_POST['title_date'] ?? '');
        $current_salary = isset($_POST['current_salary']) && $_POST['current_salary'] !== '' ? (float)$_POST['current_salary'] : null;
        $new_salary = isset($_POST['new_salary']) && $_POST['new_salary'] !== '' ? (float)$_POST['new_salary'] : null;
        $last_raise_date = cleanInput($_POST['last_raise_date'] ?? '');
        $entitlement_date = cleanInput($_POST['entitlement_date'] ?? '');
        $grade_entry_date = cleanInput($_POST['grade_entry_date'] ?? '');
        $last_promotion_date = cleanInput($_POST['last_promotion_date'] ?? '');
        $last_promotion_number = cleanInput($_POST['last_promotion_number'] ?? '');
        $job_notes = cleanInput($_POST['job_notes'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        
        // التحقق من المدخلات
        if (empty($first_name) || empty($last_name)) {
            $error = 'الاسم الأول والأخير مطلوبان';
        } elseif (empty($email) || !validateEmail($email)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } elseif (emailExists($email)) {
            $error = 'البريد الإلكتروني مستخدم بالفعل';
        } elseif (!empty($phone) && !validatePhone($phone)) {
            $error = 'رقم الهاتف غير صحيح';
        } elseif (empty($position)) {
            $error = 'المسمى الوظيفي مطلوب';
        } elseif ($salary <= 0) {
            $error = 'الراتب يجب أن يكون أكبر من صفر';
        } elseif (empty($hire_date)) {
            $error = 'تاريخ التوظيف مطلوب';
        } else {
            // توليد رمز موظف
            $employee_code = generateEmployeeCode();
            
            // رفع الصورة إن وجدت
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['photo']);
                if ($upload_result['success']) {
                    $photo = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            if (empty($error)) {
                // إدراج الموظف
                try {
                    // التحقق من وجود الحقول الجديدة باستخدام SHOW COLUMNS
                    $new_fields_exist = false;
                    try {
                        $stmt = $db->query("SHOW COLUMNS FROM employees");
                        $columns = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $columns[] = $row['Field'];
                        }
                        
                        $required_fields = ['certificate', 'title', 'current_salary'];
                        $missing = array_diff($required_fields, $columns);
                        $new_fields_exist = empty($missing);
                    } catch (PDOException $e) {
                        // الحقول غير موجودة
                        $new_fields_exist = false;
                    }
                    
                    // إذا لم يتم تحديد الراتب الحالي، استخدم الراتب الأساسي
                    if ($current_salary === null) {
                        $current_salary = $salary;
                    }
                    
                    if ($new_fields_exist) {
                        // استخدام الحقول الجديدة
                        $stmt = $db->prepare("INSERT INTO employees (employee_code, first_name, last_name, full_name, email, phone, address, department_id, position, salary, hire_date, photo, specialization, certificate, certificate_date, title, title_date, current_salary, new_salary, last_raise_date, entitlement_date, grade_entry_date, last_promotion_date, last_promotion_number, job_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $employee_code,
                            $first_name,
                            $last_name,
                            $full_name,
                            $email,
                            $phone ?: null,
                            $address ?: null,
                            $department_id > 0 ? $department_id : null,
                            $position,
                            $salary,
                            $hire_date,
                            $photo,
                            $specialization ?: null,
                            $certificate ?: null,
                            $certificate_date ?: null,
                            $title ?: null,
                            $title_date ?: null,
                            $current_salary,
                            $new_salary,
                            $last_raise_date ?: null,
                            $entitlement_date ?: null,
                            $grade_entry_date ?: null,
                            $last_promotion_date ?: null,
                            $last_promotion_number ?: null,
                            $job_notes ?: null
                        ]);
                    } else {
                        // استخدام الحقول الأساسية فقط
                        $stmt = $db->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, phone, address, department_id, position, salary, hire_date, photo, specialization) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $employee_code,
                            $first_name,
                            $last_name,
                            $email,
                            $phone ?: null,
                            $address ?: null,
                            $department_id > 0 ? $department_id : null,
                            $position,
                            $salary,
                            $hire_date,
                            $photo,
                            $specialization ?: null
                        ]);
                    }
                    
                    $success = 'تم إضافة الموظف بنجاح';
                    // إعادة تعيين النموذج
                    $_POST = [];
                } catch (PDOException $e) {
                    error_log("Error adding employee: " . $e->getMessage());
                    
                    // عرض رسالة خطأ واضحة
                    $error_msg = $e->getMessage();
                    if (strpos($error_msg, "doesn't exist") !== false || strpos($error_msg, 'Unknown column') !== false) {
                        $error = 'الحقول الوظيفية غير موجودة في قاعدة البيانات. يرجى <a href="' . SITE_URL . '/database/update_employee_job_fields.php" style="color: #667eea; text-decoration: underline; font-weight: bold;">النقر هنا</a> لإضافة الحقول تلقائياً.';
                    } else {
                        $error = 'حدث خطأ أثناء إضافة الموظف: ' . htmlspecialchars(substr($error_msg, 0, 200));
                    }
                }
            }
        }
    }
}

$page_title = 'إضافة موظف جديد';
$additional_css = ['forms.css'];
$additional_js = ['validation.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="add-employee">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">إضافة موظف جديد</h2>
            <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
        </div>

        <?php if ($fields_missing): ?>
            <div id="fields-missing-alert" class="alert alert-error" style="padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin: 20px 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #856404; margin-left: 10px;"></i>
                <div style="display: inline-block; width: calc(100% - 50px);">
                    <strong style="color: #856404; font-size: 16px; display: block; margin-bottom: 10px;">تحذير مهم:</strong>
                    <p style="color: #856404; margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
                        الحقول الوظيفية الجديدة غير موجودة في قاعدة البيانات. يرجى إضافة الحقول تلقائياً قبل إضافة موظف جديد.
                    </p>
                    <div style="margin-top: 15px;">
                        <button type="button" 
                                id="add-fields-btn"
                                class="btn btn-primary" 
                                style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px; border: none; cursor: pointer;">
                            <i class="fas fa-magic"></i> <span id="btn-text">إضافة الحقول تلقائياً</span>
                        </button>
                        <a href="<?php echo SITE_URL; ?>/database/update_employee_job_fields.php" 
                           class="btn btn-secondary" 
                           style="display: inline-block; padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px;">
                            <i class="fas fa-external-link-alt"></i> فتح صفحة الإعدادات
                        </a>
                    </div>
                    <div id="fields-status" style="margin-top: 15px; display: none;"></div>
                </div>
            </div>
            
            <script>
            document.getElementById('add-fields-btn').addEventListener('click', function() {
                const btn = this;
                const btnText = document.getElementById('btn-text');
                const statusDiv = document.getElementById('fields-status');
                
                btn.disabled = true;
                btnText.textContent = 'جاري الإضافة...';
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 5px;"><i class="fas fa-spinner fa-spin"></i> جاري إضافة الحقول...</div>';
                
                fetch('<?php echo SITE_URL; ?>/admin/employees/add_fields_ajax.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background: #d4edda; border-radius: 5px;"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 5px;"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                            btn.disabled = false;
                            btnText.textContent = 'إضافة الحقول تلقائياً';
                        }
                    })
                    .catch(error => {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background: #f8d7da; border-radius: 5px;"><i class="fas fa-exclamation-circle"></i> حدث خطأ: ' + error.message + '</div>';
                        btn.disabled = false;
                        btnText.textContent = 'إضافة الحقول تلقائياً';
                    });
            });
            </script>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="employee-form" id="employeeForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-section">
                <h3>المعلومات الشخصية</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">الاسم الأول <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">الاسم الأخير <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">رقم الهاتف</label>
                        <input type="text" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">العنوان</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>المعلومات الوظيفية</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name_display">الاسم</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''))); ?>" readonly style="background: #f5f5f5;">
                        <small>يتم توليده تلقائياً من الاسم الأول والأخير</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="department_id">القسم</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="0">اختر القسم</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="certificate">الشهادة</label>
                        <input type="text" id="certificate" name="certificate" class="form-control" 
                               placeholder="مثل: ماجستير، دكتوراه"
                               value="<?php echo htmlspecialchars($_POST['certificate'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="certificate_date">تاريخ الحصول على الشهادة</label>
                        <input type="date" id="certificate_date" name="certificate_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['certificate_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">اللقب</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               placeholder="مثل: مدرس، أستاذ"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="title_date">تاريخ الحصول على اللقب</label>
                        <input type="date" id="title_date" name="title_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['title_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="specialization">التخصص</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" 
                               placeholder="مثل: الانظمة الطبية الذكية، الامن السيبراني"
                               value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="employee_code_display">الرقم الوظيفي</label>
                        <input type="text" class="form-control" value="سيتم توليده تلقائياً" readonly style="background: #f5f5f5;">
                        <small>سيتم إنشاؤه تلقائياً عند الحفظ</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_salary">الراتب الحالي</label>
                        <input type="number" id="current_salary" name="current_salary" class="form-control" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['current_salary'] ?? ($_POST['salary'] ?? '')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_salary">الراتب الجديد</label>
                        <input type="number" id="new_salary" name="new_salary" class="form-control" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['new_salary'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_raise_date">تاريخ آخر زيادة</label>
                        <input type="date" id="last_raise_date" name="last_raise_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_raise_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="entitlement_date">تاريخ الاستحقاق</label>
                        <input type="date" id="entitlement_date" name="entitlement_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['entitlement_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="grade_entry_date">تاريخ الدخول بدرجة</label>
                        <input type="date" id="grade_entry_date" name="grade_entry_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['grade_entry_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_promotion_date">تاريخ آخر ترفيع</label>
                        <input type="date" id="last_promotion_date" name="last_promotion_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_promotion_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_promotion_number">رقم آخر ترفيع</label>
                        <input type="text" id="last_promotion_number" name="last_promotion_number" class="form-control" 
                               placeholder="رقم قرار الترفيع"
                               value="<?php echo htmlspecialchars($_POST['last_promotion_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary">الراتب الأساسي <span class="required">*</span></label>
                        <input type="number" id="salary" name="salary" class="form-control" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="hire_date">تاريخ التوظيف <span class="required">*</span></label>
                        <input type="date" id="hire_date" name="hire_date" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['hire_date'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">المسمى الوظيفي <span class="required">*</span></label>
                        <input type="text" id="position" name="position" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="job_notes">الملاحظات</label>
                    <textarea id="job_notes" name="job_notes" class="form-control" rows="4" 
                              placeholder="أي ملاحظات إضافية متعلقة بالوظيفة..."><?php echo htmlspecialchars($_POST['job_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>الصورة الشخصية</h3>
                
                <div class="form-group">
                    <label for="photo">رفع صورة</label>
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                    <small>الحجم الأقصى: 5MB | الأنواع المسموحة: JPG, PNG, GIF, WEBP</small>
                </div>
                
                <div class="image-preview" id="imagePreview" style="display: none;">
                    <img id="previewImg" src="" alt="معاينة الصورة">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">إضافة الموظف</button>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

