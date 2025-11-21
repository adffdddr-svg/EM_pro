/**
 * Employee Management System
 * التحقق من النماذج
 */

document.addEventListener('DOMContentLoaded', function() {
    const employeeForm = document.getElementById('employeeForm');
    
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(e) {
            if (!validateEmployeeForm()) {
                e.preventDefault();
                return false;
            }
        });
        
        // التحقق أثناء الكتابة
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                validateEmailField(this);
            });
        }
        
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('blur', function() {
                validatePhoneField(this);
            });
        }
        
        const salaryInput = document.getElementById('salary');
        if (salaryInput) {
            salaryInput.addEventListener('blur', function() {
                validateSalaryField(this);
            });
        }
    }
});

// التحقق من نموذج الموظف
function validateEmployeeForm() {
    let isValid = true;
    
    // التحقق من الاسم الأول
    const firstName = document.getElementById('first_name');
    if (firstName && !firstName.value.trim()) {
        showFieldError(firstName, 'الاسم الأول مطلوب');
        isValid = false;
    } else {
        clearFieldError(firstName);
    }
    
    // التحقق من الاسم الأخير
    const lastName = document.getElementById('last_name');
    if (lastName && !lastName.value.trim()) {
        showFieldError(lastName, 'الاسم الأخير مطلوب');
        isValid = false;
    } else {
        clearFieldError(lastName);
    }
    
    // التحقق من البريد الإلكتروني
    const email = document.getElementById('email');
    if (email) {
        if (!email.value.trim()) {
            showFieldError(email, 'البريد الإلكتروني مطلوب');
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showFieldError(email, 'البريد الإلكتروني غير صحيح');
            isValid = false;
        } else {
            clearFieldError(email);
        }
    }
    
    // التحقق من الهاتف
    const phone = document.getElementById('phone');
    if (phone && phone.value.trim() && !validatePhone(phone.value)) {
        showFieldError(phone, 'رقم الهاتف غير صحيح');
        isValid = false;
    } else if (phone) {
        clearFieldError(phone);
    }
    
    // التحقق من المسمى الوظيفي
    const position = document.getElementById('position');
    if (position && !position.value.trim()) {
        showFieldError(position, 'المسمى الوظيفي مطلوب');
        isValid = false;
    } else {
        clearFieldError(position);
    }
    
    // التحقق من الراتب
    const salary = document.getElementById('salary');
    if (salary) {
        if (!salary.value || parseFloat(salary.value) <= 0) {
            showFieldError(salary, 'الراتب يجب أن يكون أكبر من صفر');
            isValid = false;
        } else {
            clearFieldError(salary);
        }
    }
    
    // التحقق من تاريخ التوظيف
    const hireDate = document.getElementById('hire_date');
    if (hireDate && !hireDate.value) {
        showFieldError(hireDate, 'تاريخ التوظيف مطلوب');
        isValid = false;
    } else {
        clearFieldError(hireDate);
    }
    
    // التحقق من الصورة
    const photo = document.getElementById('photo');
    if (photo && photo.files.length > 0) {
        const file = photo.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (file.size > maxSize) {
            showFieldError(photo, 'حجم الملف كبير جداً (الحد الأقصى: 5MB)');
            isValid = false;
        } else if (!allowedTypes.includes(file.type)) {
            showFieldError(photo, 'نوع الملف غير مسموح');
            isValid = false;
        } else {
            clearFieldError(photo);
        }
    }
    
    return isValid;
}

// التحقق من حقل البريد الإلكتروني
function validateEmailField(field) {
    if (!field.value.trim()) {
        showFieldError(field, 'البريد الإلكتروني مطلوب');
        return false;
    } else if (!validateEmail(field.value)) {
        showFieldError(field, 'البريد الإلكتروني غير صحيح');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

// التحقق من حقل الهاتف
function validatePhoneField(field) {
    if (field.value.trim() && !validatePhone(field.value)) {
        showFieldError(field, 'رقم الهاتف غير صحيح');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

// التحقق من حقل الراتب
function validateSalaryField(field) {
    if (!field.value || parseFloat(field.value) <= 0) {
        showFieldError(field, 'الراتب يجب أن يكون أكبر من صفر');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

// إظهار خطأ في الحقل
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.style.borderColor = '#e74c3c';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// إزالة خطأ من الحقل
function clearFieldError(field) {
    field.style.borderColor = '';
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

