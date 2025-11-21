/**
 * Mobile Enhancements
 * تحسينات خاصة للهاتف
 */

(function() {
    'use strict';
    
    // منع zoom على double tap (iOS)
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
    
    // تحسين scroll على iOS
    if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
        document.addEventListener('touchmove', function(event) {
            if (event.target.closest('.table-container, .bot-chat-section')) {
                return; // السماح بالscroll في هذه العناصر
            }
        }, { passive: true });
    }
    
    // تحسين touch feedback
    const touchElements = document.querySelectorAll('.btn, .nav-link, .quick-reply-btn, .card');
    touchElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.opacity = '0.7';
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.opacity = '';
            }, 150);
        }, { passive: true });
    });
    
    // تحسين input focus على الهاتف
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            // تأخير بسيط لضمان ظهور لوحة المفاتيح بشكل صحيح
            setTimeout(() => {
                this.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        });
    });
    
    // إضافة swipe gesture لإغلاق القائمة الجانبية
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        // Swipe left to close sidebar (RTL)
        if (diff < -swipeThreshold) {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-menu-overlay');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        }
    }
    
    // تحسين performance على الهاتف
    if ('requestIdleCallback' in window) {
        requestIdleCallback(function() {
            // تحميل lazy loading للصور
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        });
    }
    
    // إضافة vibration feedback (إذا كان متاحاً)
    if ('vibrate' in navigator) {
        const buttons = document.querySelectorAll('.btn-primary, .bot-send-button');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                navigator.vibrate(10); // اهتزاز خفيف
            });
        });
    }
    
    // تحسين scroll behavior
    if (CSS.supports('scroll-behavior', 'smooth')) {
        document.documentElement.style.scrollBehavior = 'smooth';
    }
    
    // منع pull-to-refresh على iOS (اختياري)
    let touchY = 0;
    document.addEventListener('touchstart', function(e) {
        touchY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        const currentY = e.touches[0].clientY;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // منع pull-to-refresh إذا كان المستخدم في أعلى الصفحة
        if (scrollTop === 0 && currentY > touchY) {
            e.preventDefault();
        }
    }, { passive: false });
    
    console.log('Mobile enhancements loaded ✅');
})();

