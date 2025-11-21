<?php
/**
 * Employee Management System
 * تعديل موظف
 */

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin(); // يتطلب صلاحيات المدير

$db = getDB();
$error = '';
$success = '';

// الحصول على معرف الموظف
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL . '/admin/employees/index.php');
}

// الحصول على بيانات الموظف
$stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect(SITE_URL . '/admin/employees/index.php');
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
        $status = cleanInput($_POST['status'] ?? 'active');
        
        // التحقق من المدخلات
        if (empty($first_name) || empty($last_name)) {
            $error = 'الاسم الأول والأخير مطلوبان';
        } elseif (empty($email) || !validateEmail($email)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } elseif (emailExists($email, $id)) {
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
            // رفع الصورة إن وجدت
            $photo = $employee['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['photo'], $employee['photo']);
                if ($upload_result['success']) {
                    $photo = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            // حذف الصورة القديمة إذا طلب المستخدم
            if (isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1') {
                if ($employee['photo']) {
                    deleteImage($employee['photo']);
                    $photo = null;
                }
            }
            
            if (empty($error)) {
                // تحديث الموظف
                try {
                    $stmt = $db->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, department_id = ?, position = ?, salary = ?, hire_date = ?, photo = ?, status = ? WHERE id = ?");
                    $stmt->execute([
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
                        $status,
                        $id
                    ]);
                    
                    $success = 'تم تحديث بيانات الموظف بنجاح';
                    // تحديث بيانات الموظف للعرض
                    $employee = array_merge($employee, [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'department_id' => $department_id,
                        'position' => $position,
                        'salary' => $salary,
                        'hire_date' => $hire_date,
                        'photo' => $photo,
                        'status' => $status
                    ]);
                } catch (PDOException $e) {
                    error_log("Error updating employee: " . $e->getMessage());
                    $error = 'حدث خطأ أثناء تحديث بيانات الموظف';
                }
            }
        }
    }
}

$page_title = 'تعديل موظف';
$additional_css = ['forms.css'];
$additional_js = ['validation.js'];
include __DIR__ . '/../../includes/header.php';
?>

<div class="edit-employee">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">تعديل موظف</h2>
            <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">العودة للقائمة</a>
        </div>

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
                               value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">الاسم الأخير <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">رقم الهاتف</label>
                        <input type="text" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">العنوان</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>المعلومات الوظيفية</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department_id">القسم</label>
                        <select id="department_id" name="department_id" class="form-control">
                            <option value="0">اختر القسم</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">المسمى الوظيفي <span class="required">*</span></label>
                        <input type="text" id="position" name="position" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary">الراتب <span class="required">*</span></label>
                        <input type="number" id="salary" name="salary" class="form-control" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($employee['salary']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">تاريخ التوظيف <span class="required">*</span></label>
                        <input type="date" id="hire_date" name="hire_date" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['hire_date']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo ($employee['status'] == 'active') ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo ($employee['status'] == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>الصورة الشخصية</h3>
                
                <?php if ($employee['photo']): ?>
                    <div class="current-photo" style="margin-bottom: 15px;">
                        <p>الصورة الحالية:</p>
                        <img src="<?php echo UPLOAD_URL . $employee['photo']; ?>" alt="صورة الموظف" style="max-width: 200px; border-radius: 5px;">
                        <div style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="delete_photo" value="1"> حذف الصورة الحالية
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="photo">رفع صورة جديدة</label>
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                    <small>الحجم الأقصى: 5MB | الأنواع المسموحة: JPG, PNG, GIF, WEBP</small>
                </div>
                
                <div class="image-preview" id="imagePreview" style="display: none;">
                    <img id="previewImg" src="" alt="معاينة الصورة">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                <a href="<?php echo SITE_URL; ?>/admin/employees/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">عرض التفاصيل</a>
                <a href="<?php echo SITE_URL; ?>/admin/employees/index.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

