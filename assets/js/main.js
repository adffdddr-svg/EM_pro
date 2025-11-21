/**
 * Employee Management System
 * JavaScript الرئيسي
 */

// إخفاء الرسائل تلقائياً بعد 5 ثوان
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// معاينة الصورة قبل الرفع
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (preview && previewImg) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// إضافة معاينة الصورة لجميع حقول رفع الصور
document.addEventListener('DOMContentLoaded', function() {
    const photoInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    photoInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            previewImage(this);
        });
    });
});

// تأكيد الحذف
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من هذا الإجراء؟');
}

// تأكيد الأرشفة
function confirmArchive(message) {
    return confirm(message || 'هل أنت متأكد من أرشفة هذا الموظف؟');
}

// تأكيد الاستعادة
function confirmRestore(message) {
    return confirm(message || 'هل أنت متأكد من استعادة هذا الموظف؟');
}

// تنسيق الأرقام
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// تنسيق التاريخ
function formatDate(dateString) {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// التحقق من صحة البريد الإلكتروني
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// التحقق من صحة الهاتف
function validatePhone(phone) {
    const re = /^[0-9+\-\s()]+$/;
    return re.test(phone);
}

// إظهار رسالة خطأ
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.innerHTML = '<strong>خطأ:</strong> ' + message;
    
    const content = document.querySelector('.content');
    if (content) {
        content.insertBefore(alertDiv, content.firstChild);
    }
}

// إظهار رسالة نجاح
function showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.innerHTML = '<strong>نجاح:</strong> ' + message;
    
    const content = document.querySelector('.content');
    if (content) {
        content.insertBefore(alertDiv, content.firstChild);
    }
}

// إدارة قائمة الموبايل
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle && sidebar) {
        // إظهار/إخفاء زر القائمة حسب حجم الشاشة
        function checkScreenSize() {
            if (window.innerWidth <= 768) {
                mobileMenuToggle.style.display = 'flex';
            } else {
                mobileMenuToggle.style.display = 'none';
                sidebar.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
            }
        }
        
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
        
        // فتح/إغلاق القائمة
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mobileMenuOverlay.classList.toggle('active');
        });
        
        // إغلاق القائمة عند النقر على الـ overlay
        mobileMenuOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
        });
        
        // إغلاق القائمة عند النقر على رابط
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                }
            });
        });
    }
});

