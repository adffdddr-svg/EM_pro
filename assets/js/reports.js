/**
 * Employee Management System
 * JavaScript للتقارير والرسوم البيانية
 */

(function() {
    'use strict';

    // تهيئة عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        // إضافة تأثيرات تفاعلية للبطاقات
        const reportCards = document.querySelectorAll('.report-card');
        reportCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // تحسين عرض الرسوم البيانية على الشاشات الصغيرة
        const charts = document.querySelectorAll('canvas');
        charts.forEach(chart => {
            const container = chart.closest('.chart-card');
            if (container) {
                const observer = new ResizeObserver(entries => {
                    entries.forEach(entry => {
                        const chartInstance = Chart.getChart(chart);
                        if (chartInstance) {
                            chartInstance.resize();
                        }
                    });
                });
                observer.observe(container);
            }
        });
    });

    // دالة لتصدير PDF (يمكن تطويرها لاحقاً)
    window.exportPDF = function() {
        window.print();
    };

    // دالة لتصدير Excel (يمكن تطويرها لاحقاً)
    window.exportExcel = function() {
        alert('ميزة التصدير إلى Excel قيد التطوير');
    };

    // تحسين الطباعة
    window.addEventListener('beforeprint', function() {
        // إخفاء الأزرار عند الطباعة
        const actions = document.querySelectorAll('.report-actions');
        actions.forEach(action => {
            action.style.display = 'none';
        });
    });

    window.addEventListener('afterprint', function() {
        // إظهار الأزرار بعد الطباعة
        const actions = document.querySelectorAll('.report-actions');
        actions.forEach(action => {
            action.style.display = 'flex';
        });
    });

})();

