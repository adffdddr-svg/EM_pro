    <?php if (isLoggedIn()): ?>
            </main>
        </div>
    </div>
    <?php else: ?>
    </div>
    <?php endif; ?>
    
    <script>
        // تعريف SITE_URL للاستخدام في JavaScript
        window.SITE_URL = '<?php echo SITE_URL; ?>';
    </script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/mobile-enhancements.js"></script>
    <?php if (isLoggedIn()): ?>
        <script src="<?php echo SITE_URL; ?>/assets/js/dark-mode.js"></script>
    <?php endif; ?>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo SITE_URL . '/assets/js/' . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo SITE_URL; ?>/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registered: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>
</body>
</html>

