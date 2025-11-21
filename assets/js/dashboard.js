/**
 * Employee Management System
 * JavaScript للوحة التحكم المحسّنة
 */

(function() {
    'use strict';
    
    // تهيئة عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        initDashboard();
    });
    
    function initDashboard() {
        // تحسين البطاقات الإحصائية
        enhanceStatCards();
        
        // تحسين Activity Feed
        enhanceActivityFeed();
        
        // تحسين الجداول
        enhanceTables();
        
        // إضافة تأثيرات تفاعلية
        addInteractiveEffects();
    }
    
    // تحسين البطاقات الإحصائية
    function enhanceStatCards() {
        const statCards = document.querySelectorAll('.stat-card.enhanced');
        
        statCards.forEach((card, index) => {
            // إضافة تأثير عند الظهور
            card.style.animationDelay = `${index * 0.1}s`;
            
            // إضافة تأثير عند النقر
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
            
            // تحديث Charts ديناميكياً
            updateMiniCharts(card);
        });
    }
    
    // تحديث Mini Charts
    function updateMiniCharts(card) {
        const chartBars = card.querySelectorAll('.chart-bar');
        if (chartBars.length > 0) {
            chartBars.forEach((bar, index) => {
                bar.style.animationDelay = `${index * 0.1}s`;
            });
        }
    }
    
    // تحسين Activity Feed
    function enhanceActivityFeed() {
        const activityItems = document.querySelectorAll('.activity-item');
        
        activityItems.forEach((item, index) => {
            // إضافة تأثير عند الظهور
            item.style.animationDelay = `${index * 0.1}s`;
            
            // إضافة تأثير عند Hover
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(-5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
        
        // تحسين Scrollbar
        const activityFeed = document.querySelector('.activity-feed');
        if (activityFeed) {
            // Smooth scroll
            activityFeed.style.scrollBehavior = 'smooth';
        }
    }
    
    // تحسين الجداول
    function enhanceTables() {
        const tableRows = document.querySelectorAll('.dashboard-grid .table tbody tr');
        
        tableRows.forEach((row, index) => {
            // إضافة تأثير عند الظهور
            row.style.animationDelay = `${index * 0.05}s`;
            
            // إضافة تأثير عند Hover
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
    
    // إضافة تأثيرات تفاعلية
    function addInteractiveEffects() {
        // تأثير Ripple على Quick Links
        const quickLinks = document.querySelectorAll('.quick-link-card');
        
        quickLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // تأثير Counter Animation للأرقام
        animateCounters();
    }
    
    // تحريك الأرقام (Counter Animation)
    function animateCounters() {
        const statValues = document.querySelectorAll('.stat-value');
        
        statValues.forEach(statValue => {
            const target = parseInt(statValue.textContent);
            if (isNaN(target)) return;
            
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    statValue.textContent = target;
                    clearInterval(timer);
                } else {
                    statValue.textContent = Math.floor(current);
                }
            }, duration / steps);
        });
    }
    
    // إضافة CSS للـ Ripple Effect
    const style = document.createElement('style');
    style.textContent = `
        .quick-link-card {
            position: relative;
            overflow: hidden;
        }
        
        .quick-link-card .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // تحديث البيانات كل 30 ثانية (اختياري)
    // setInterval(() => {
    //     // يمكن إضافة AJAX call هنا لتحديث البيانات
    // }, 30000);
})();

