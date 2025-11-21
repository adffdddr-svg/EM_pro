/**
 * Employee Management System
 * وظائف الأرشيف
 */

// تأكيد الأرشفة
function confirmArchive(employeeName) {
    return confirm('هل أنت متأكد من أرشفة الموظف "' + employeeName + '"؟\n\nسيتم نقل الموظف إلى الأرشيف.');
}

// تأكيد الاستعادة
function confirmRestore(employeeName) {
    return confirm('هل أنت متأكد من استعادة الموظف "' + employeeName + '"؟\n\nسيتم إعادة الموظف إلى القائمة النشطة.');
}

// إضافة معالجات الأحداث للأزرار
document.addEventListener('DOMContentLoaded', function() {
    // أزرار الأرشفة
    const archiveButtons = document.querySelectorAll('a[href*="archive_id"]');
    archiveButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const employeeName = this.closest('tr')?.querySelector('td:nth-child(2)')?.textContent || 'هذا الموظف';
            if (!confirmArchive(employeeName)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // أزرار الاستعادة
    const restoreButtons = document.querySelectorAll('a[href*="restore_id"]');
    restoreButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const employeeName = this.closest('tr')?.querySelector('td:nth-child(2)')?.textContent || 'هذا الموظف';
            if (!confirmRestore(employeeName)) {
                e.preventDefault();
                return false;
            }
        });
    });
});

