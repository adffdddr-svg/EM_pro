/**
 * نظام اختيار الثيم المحسّن
 * Employee Management System
 */

(function() {
    'use strict';
    
    // تعريف الثيمات المتاحة
    const themes = {
        'auto': { name: 'حسب النظام', icon: '🔄', actual: null },
        'dark': { name: 'الوضع الليلي', icon: '🌙', actual: 'dark' },
        'dark-blue': { name: 'أزرق ليلي', icon: '🌃', actual: 'dark-blue' },
        'dark-pink': { name: 'وردي ليلي', icon: '🌺', actual: 'dark-pink' },
        'classic': { name: 'كلاسيكي', icon: '📜', actual: 'classic' },
        'light': { name: 'الوضع النهاري', icon: '☀️', actual: 'light' },
        'blue': { name: 'أزرق عصري', icon: '💙', actual: 'blue' },
        'elegant': { name: 'أنيق ونظيف', icon: '✨', actual: 'elegant' },
        'vibrant': { name: 'نابض وناعم', icon: '🌈', actual: 'vibrant' },
        'pink': { name: 'وردي أنثوي', icon: '🌸', actual: 'pink' }
    };
    
    // الحصول على التفضيل المحفوظ
    function getThemePreference() {
        // أولاً: من localStorage
        const saved = localStorage.getItem('theme');
        if (saved && saved !== 'auto' && themes[saved]) {
            return saved;
        }
        
        // إذا كان تلقائي، استخدم تفضيلات النظام
        if (saved === 'auto' || !saved) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }
        
        return 'light';
    }
    
    // الحصول على الثيم الفعلي (للثيم التلقائي)
    function getActualTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'auto' || !saved) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }
        return saved || 'light';
    }
    
    // تطبيق الوضع
    function applyTheme(theme) {
        const actualTheme = theme === 'auto' ? getActualTheme() : theme;
        document.documentElement.setAttribute('data-theme', actualTheme);
        localStorage.setItem('theme', theme);
        
        // تحديث الواجهة
        updateThemeUI(theme);
        
        // حفظ في قاعدة البيانات (فقط إذا كان theme صحيح)
        if (themes[theme]) {
            saveThemePreference(theme);
        }
    }
    
    // تحديث واجهة اختيار الثيم
    function updateThemeUI(theme) {
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const themeOptions = document.querySelectorAll('.theme-option');
        
        // تحديث الأيقونة والنص
        if (themeIcon && themeText && themes[theme]) {
            themeIcon.textContent = themes[theme].icon;
            themeText.textContent = themes[theme].name;
        }
        
        // تحديث الخيارات النشطة
        themeOptions.forEach(option => {
            option.classList.remove('active');
            if (option.dataset.theme === theme) {
                option.classList.add('active');
            }
        });
    }
    
    // حفظ التفضيل في قاعدة البيانات
    function saveThemePreference(theme) {
        const formData = new FormData();
        formData.append('group', 'display');
        formData.append('user_theme', theme);
        formData.append('dark_mode_enabled', theme === 'dark' ? '1' : '0');
        
        const siteUrl = window.SITE_URL || '';
        if (siteUrl) {
            fetch(siteUrl + '/admin/settings/save.php', {
                method: 'POST',
                body: formData
            }).catch(err => {
                console.log('Theme preference saved locally only');
            });
        }
    }
    
    // فتح/إغلاق قائمة الثيم
    function toggleThemeMenu() {
        const selector = document.querySelector('.theme-selector');
        if (selector) {
            selector.classList.toggle('active');
        }
    }
    
    // اختيار ثيم جديد
    function selectTheme(theme) {
        applyTheme(theme);
        toggleThemeMenu();
        
        // إشعار نجاح
        showThemeNotification(theme);
    }
    
    // إظهار إشعار التغيير
    function showThemeNotification(theme) {
        if (!themes[theme]) return;
        
        const themeInfo = themes[theme];
        const message = `${themeInfo.icon} تم تفعيل ${themeInfo.name}`;
        
        // إنشاء إشعار مؤقت
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success-color);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideDown 0.3s ease;
            font-weight: 500;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }
    
    // تهيئة الوضع عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
        
        // إضافة event listeners
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleThemeMenu();
            });
        }
        
        // اختيار ثيم من القائمة
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                selectTheme(this.dataset.theme);
            });
        });
        
        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', function(e) {
            const selector = document.querySelector('.theme-selector');
            if (selector && !selector.contains(e.target)) {
                selector.classList.remove('active');
            }
        });
        
        // الاستماع لتغيير تفضيلات النظام (للثيم التلقائي)
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme === 'auto' || !currentTheme) {
                    applyTheme('auto');
                }
            });
        }
    });
    
    // جعل الدوال متاحة عالمياً
    window.toggleTheme = function(theme) {
        if (theme) {
            selectTheme(theme);
        } else {
            toggleThemeMenu();
        }
    };
    window.applyTheme = applyTheme;
    window.getThemePreference = getThemePreference;
    window.getActualTheme = getActualTheme;
    window.saveThemePreference = saveThemePreference;
})();
