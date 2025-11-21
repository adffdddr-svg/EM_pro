<?php
/**
 * Employee Management System
 * صفحة الإعدادات
 */

define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$page_title = 'الإعدادات';

// الحصول على الإعدادات من قاعدة البيانات
$db = getDB();
$settings = [];

// إنشاء جدول settings إذا لم يكن موجوداً
try {
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by INT NULL,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_setting_group (setting_group),
        INDEX idx_setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // الجدول موجود بالفعل
}

// جلب الإعدادات
try {
    $stmt = $db->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_group']][$row['setting_key']] = $row;
    }
} catch (PDOException $e) {
    $settings = [];
}

// دالة مساعدة للحصول على قيمة الإعداد
function getSetting($group, $key, $default = '') {
    global $settings;
    return isset($settings[$group][$key]['setting_value']) 
        ? $settings[$group][$key]['setting_value'] 
        : $default;
}

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

include __DIR__ . '/../includes/header.php';
?>
<!-- تم حذف تبويب البوت الذكي نهائياً - <?php echo date('Y-m-d H:i:s'); ?> -->

<style>
.settings-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h1 {
    color: var(--primary-color);
    font-size: 32px;
    margin-bottom: 10px;
}

.settings-header p {
    color: #666;
    font-size: 16px;
}

.settings-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
    flex-wrap: wrap;
    overflow-x: auto;
}

.tab-button {
    padding: 15px 25px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
    position: relative;
    top: 2px;
    white-space: nowrap;
}

.tab-button:hover {
    color: var(--primary-color);
    background: rgba(44, 62, 80, 0.05);
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    animation: fadeIn 0.3s;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-form {
    display: grid;
    gap: 25px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: 1 / -1;
    width: 100%;
    display: block !important;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-group input,
.form-group textarea,
.form-group select {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    font-family: inherit;
    background: white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
}

.form-group input[type="color"] {
    height: 50px;
    cursor: pointer;
}

.form-group small {
    color: #666;
    font-size: 13px;
    margin-top: 5px;
}

.btn-save {
    background: var(--primary-color);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 20px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-save:hover {
    background: #1a252f;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
}

.btn-save:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
    margin: 30px 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

/* تنسيقات اختيار الثيم */
.theme-selection-container {
    margin-top: 20px;
    display: block !important;
    visibility: visible !important;
    width: 100%;
}

.theme-options-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
    visibility: visible !important;
    opacity: 1 !important;
}

.theme-card {
    position: relative;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.theme-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.theme-card.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
}

.theme-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.theme-card-label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px;
    cursor: pointer;
    position: relative;
}

.theme-card-icon {
    font-size: 24px;
    width: 30px;
    text-align: center;
}

.theme-card-name {
    flex: 1;
    font-weight: 500;
    font-size: 15px;
    color: var(--text-color);
}

.theme-card.active .theme-card-name {
    color: white;
}

.theme-card-check {
    opacity: 0;
    font-size: 20px;
    font-weight: bold;
    color: var(--success-color);
    transition: opacity 0.2s;
}

.theme-card.active .theme-card-check {
    opacity: 1;
    color: white;
}

/* الوضع الليلي لبطاقات الثيم */
[data-theme="dark"] .theme-card,
[data-theme="dark-blue"] .theme-card,
[data-theme="dark-pink"] .theme-card {
    background: var(--card-bg);
    border-color: var(--border-color);
}

[data-theme="dark"] .theme-card:hover,
[data-theme="dark-blue"] .theme-card:hover,
[data-theme="dark-pink"] .theme-card:hover {
    border-color: var(--secondary-color);
}

[data-theme="dark"] .theme-card.active,
[data-theme="dark-blue"] .theme-card.active,
[data-theme="dark-pink"] .theme-card.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

@media (max-width: 768px) {
    .settings-container {
        padding: 15px;
    }
    
    .settings-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .theme-options-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }
    
    .theme-card-label {
        padding: 14px 16px;
    }
    
    .theme-card-icon {
        font-size: 20px;
    }
    
    .theme-card-name {
        font-size: 13px;
    }
}
</style>

<div class="settings-container">
    <div class="settings-header">
        <h1>⚙️ إعدادات النظام</h1>
        <p>إدارة جميع إعدادات النظام من مكان واحد</p>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <button class="tab-button active" onclick="showTab('general')">🌐 عام</button>
        <button class="tab-button" onclick="showTab('display')">📱 العرض</button>
        <button class="tab-button" onclick="showTab('security')">🔒 الأمان</button>
        <button class="tab-button" onclick="showTab('leaves')">📅 الإجازات</button>
        <button class="tab-button" onclick="showTab('email')">📧 البريد</button>
        <button class="tab-button" onclick="showTab('notifications')">🔔 الإشعارات</button>
        <button class="tab-button" onclick="showTab('files')">📁 الملفات</button>
        <!-- تم حذف تبويب البوت الذكي -->
    </div>
    
    <!-- تبويب الإعدادات العامة -->
    <div id="tab-general" class="tab-content active">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="general">
            <div class="settings-grid">
                <div class="form-group">
                    <label>اسم الموقع</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars(getSetting('general', 'site_name', SITE_NAME)); ?>" required>
                    <small>الاسم الذي يظهر في أعلى الموقع</small>
                </div>
                
                <div class="form-group">
                    <label>رابط الموقع</label>
                    <input type="url" name="site_url" value="<?php echo htmlspecialchars(getSetting('general', 'site_url', SITE_URL)); ?>" required>
                    <small>الرابط الكامل للموقع</small>
                </div>
                
                <div class="form-group full-width">
                    <label>وصف الموقع</label>
                    <textarea name="site_description" rows="3"><?php echo htmlspecialchars(getSetting('general', 'site_description', '')); ?></textarea>
                    <small>وصف مختصر عن الموقع</small>
                </div>
                
                <div class="form-group">
                    <label>المنطقة الزمنية</label>
                    <select name="timezone" required>
                        <option value="Asia/Baghdad" <?php echo getSetting('general', 'timezone', 'Asia/Baghdad') == 'Asia/Baghdad' ? 'selected' : ''; ?>>Asia/Baghdad (بغداد)</option>
                        <option value="Asia/Dubai" <?php echo getSetting('general', 'timezone') == 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (دبي)</option>
                        <option value="Asia/Riyadh" <?php echo getSetting('general', 'timezone') == 'Asia/Riyadh' ? 'selected' : ''; ?>>Asia/Riyadh (الرياض)</option>
                        <option value="UTC" <?php echo getSetting('general', 'timezone') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    </select>
                    <small>المنطقة الزمنية للنظام</small>
                </div>
                
                <div class="form-group">
                    <label>اللغة الافتراضية</label>
                    <select name="language" required>
                        <option value="ar" <?php echo getSetting('general', 'language', 'ar') == 'ar' ? 'selected' : ''; ?>>العربية</option>
                        <option value="en" <?php echo getSetting('general', 'language') == 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                    <small>اللغة الافتراضية للموقع</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ الإعدادات العامة</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات العرض -->
    <div id="tab-display" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="display">
            <div class="settings-grid">
                <!-- قسم اختيار الثيم -->
                <div class="form-group full-width" id="theme-selection-section" style="margin-bottom: 30px !important; padding: 20px !important; background: #f8f9fa !important; border-radius: 10px !important; border: 2px solid #e0e0e0 !important; display: block !important; visibility: visible !important; opacity: 1 !important; width: 100% !important; position: relative !important; z-index: 1 !important;">
                    <label style="font-size: 18px !important; font-weight: 700 !important; margin-bottom: 15px !important; display: block !important; color: var(--primary-color) !important; visibility: visible !important;">
                        🌈 اختيار الثيم
                    </label>
                    <small style="display: block !important; margin-bottom: 20px !important; color: #666 !important; font-size: 14px !important; visibility: visible !important;">
                        اختر الثيم المفضل لك من القائمة أدناه - سيتم تطبيق الثيم فوراً عند الاختيار
                    </small>
                    
                    <div class="theme-selection-container" style="display: block !important; visibility: visible !important; width: 100% !important;">
                        <?php 
                        $current_theme = getSetting('display', 'user_theme', 'light');
                        if (empty($current_theme)) {
                            $current_theme = getSetting('display', 'dark_mode_enabled', '0') == '1' ? 'dark' : 'light';
                        }
                        ?>
                        
                        <div class="theme-options-grid" style="display: grid !important; visibility: visible !important; opacity: 1 !important;">
                            <div class="theme-card <?php echo $current_theme === 'auto' ? 'active' : ''; ?>" data-theme="auto">
                                <input type="radio" name="user_theme" value="auto" id="theme_auto" <?php echo $current_theme === 'auto' ? 'checked' : ''; ?>>
                                <label for="theme_auto" class="theme-card-label">
                                    <span class="theme-card-icon">🔄</span>
                                    <span class="theme-card-name">حسب النظام</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'light' ? 'active' : ''; ?>" data-theme="light">
                                <input type="radio" name="user_theme" value="light" id="theme_light" <?php echo $current_theme === 'light' ? 'checked' : ''; ?>>
                                <label for="theme_light" class="theme-card-label">
                                    <span class="theme-card-icon">☀️</span>
                                    <span class="theme-card-name">الوضع النهاري</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'dark' ? 'active' : ''; ?>" data-theme="dark">
                                <input type="radio" name="user_theme" value="dark" id="theme_dark" <?php echo $current_theme === 'dark' ? 'checked' : ''; ?>>
                                <label for="theme_dark" class="theme-card-label">
                                    <span class="theme-card-icon">🌙</span>
                                    <span class="theme-card-name">الوضع الليلي</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'dark-blue' ? 'active' : ''; ?>" data-theme="dark-blue">
                                <input type="radio" name="user_theme" value="dark-blue" id="theme_dark_blue" <?php echo $current_theme === 'dark-blue' ? 'checked' : ''; ?>>
                                <label for="theme_dark_blue" class="theme-card-label">
                                    <span class="theme-card-icon">🌃</span>
                                    <span class="theme-card-name">أزرق ليلي</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'dark-pink' ? 'active' : ''; ?>" data-theme="dark-pink">
                                <input type="radio" name="user_theme" value="dark-pink" id="theme_dark_pink" <?php echo $current_theme === 'dark-pink' ? 'checked' : ''; ?>>
                                <label for="theme_dark_pink" class="theme-card-label">
                                    <span class="theme-card-icon">🌺</span>
                                    <span class="theme-card-name">وردي ليلي</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'classic' ? 'active' : ''; ?>" data-theme="classic">
                                <input type="radio" name="user_theme" value="classic" id="theme_classic" <?php echo $current_theme === 'classic' ? 'checked' : ''; ?>>
                                <label for="theme_classic" class="theme-card-label">
                                    <span class="theme-card-icon">📜</span>
                                    <span class="theme-card-name">كلاسيكي</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'blue' ? 'active' : ''; ?>" data-theme="blue">
                                <input type="radio" name="user_theme" value="blue" id="theme_blue" <?php echo $current_theme === 'blue' ? 'checked' : ''; ?>>
                                <label for="theme_blue" class="theme-card-label">
                                    <span class="theme-card-icon">💙</span>
                                    <span class="theme-card-name">أزرق عصري</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'elegant' ? 'active' : ''; ?>" data-theme="elegant">
                                <input type="radio" name="user_theme" value="elegant" id="theme_elegant" <?php echo $current_theme === 'elegant' ? 'checked' : ''; ?>>
                                <label for="theme_elegant" class="theme-card-label">
                                    <span class="theme-card-icon">✨</span>
                                    <span class="theme-card-name">أنيق ونظيف</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'vibrant' ? 'active' : ''; ?>" data-theme="vibrant">
                                <input type="radio" name="user_theme" value="vibrant" id="theme_vibrant" <?php echo $current_theme === 'vibrant' ? 'checked' : ''; ?>>
                                <label for="theme_vibrant" class="theme-card-label">
                                    <span class="theme-card-icon">🌈</span>
                                    <span class="theme-card-name">نابض وناعم</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                            
                            <div class="theme-card <?php echo $current_theme === 'pink' ? 'active' : ''; ?>" data-theme="pink">
                                <input type="radio" name="user_theme" value="pink" id="theme_pink" <?php echo $current_theme === 'pink' ? 'checked' : ''; ?>>
                                <label for="theme_pink" class="theme-card-label">
                                    <span class="theme-card-icon">🌸</span>
                                    <span class="theme-card-name">وردي أنثوي</span>
                                    <span class="theme-card-check">✓</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>عدد العناصر في الصفحة</label>
                    <input type="number" name="items_per_page" value="<?php echo htmlspecialchars(getSetting('display', 'items_per_page', ITEMS_PER_PAGE)); ?>" min="5" max="100" required>
                    <small>عدد الموظفين المعروضين في كل صفحة</small>
                </div>
                
                <div class="form-group">
                    <label>صيغة التاريخ</label>
                    <select name="date_format" required>
                        <option value="Y-m-d" <?php echo getSetting('display', 'date_format', DATE_FORMAT) == 'Y-m-d' ? 'selected' : ''; ?>>2024-12-25</option>
                        <option value="d/m/Y" <?php echo getSetting('display', 'date_format') == 'd/m/Y' ? 'selected' : ''; ?>>25/12/2024</option>
                        <option value="Y/m/d" <?php echo getSetting('display', 'date_format') == 'Y/m/d' ? 'selected' : ''; ?>>2024/12/25</option>
                    </select>
                    <small>صيغة عرض التاريخ في الموقع</small>
                </div>
                
                <div class="form-group">
                    <label>صيغة الوقت</label>
                    <select name="time_format" required>
                        <option value="24" <?php echo getSetting('display', 'time_format', '24') == '24' ? 'selected' : ''; ?>>24 ساعة</option>
                        <option value="12" <?php echo getSetting('display', 'time_format') == '12' ? 'selected' : ''; ?>>12 ساعة (AM/PM)</option>
                    </select>
                    <small>صيغة عرض الوقت</small>
                </div>
                
                <div class="form-group">
                    <label>حجم الخط الافتراضي</label>
                    <input type="number" name="font_size" value="<?php echo htmlspecialchars(getSetting('display', 'font_size', '16')); ?>" min="12" max="24" required>
                    <small>حجم الخط بالبكسل</small>
                </div>
                
                <div class="form-group">
                    <label>اللون الأساسي</label>
                    <input type="color" name="primary_color" value="<?php echo htmlspecialchars(getSetting('display', 'primary_color', '#2c3e50')); ?>" required>
                    <small>اللون الأساسي للموقع</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_statistics" value="1" 
                               <?php echo getSetting('display', 'show_statistics', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إظهار الإحصائيات</span>
                    </label>
                    <small>إظهار الإحصائيات في لوحة التحكم</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_photos" value="1" 
                               <?php echo getSetting('display', 'show_photos', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إظهار الصور الشخصية</span>
                    </label>
                    <small>إظهار الصور الشخصية للموظفين</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات العرض</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات الأمان -->
    <div id="tab-security" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="security">
            <div class="settings-grid">
                <div class="form-group">
                    <label>الحد الأدنى لطول كلمة المرور</label>
                    <input type="number" name="password_min_length" value="<?php echo htmlspecialchars(getSetting('security', 'password_min_length', PASSWORD_MIN_LENGTH)); ?>" min="4" max="20" required>
                    <small>الحد الأدنى لعدد الأحرف في كلمة المرور</small>
                </div>
                
                <div class="form-group">
                    <label>انتهاء الجلسة (بالثواني)</label>
                    <input type="number" name="session_timeout" value="<?php echo htmlspecialchars(getSetting('security', 'session_timeout', SESSION_TIMEOUT)); ?>" min="300" max="86400" required>
                    <small>مدة انتهاء الجلسة (3600 = ساعة واحدة)</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="two_factor_enabled" value="1" 
                               <?php echo getSetting('security', 'two_factor_enabled', '0') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل تسجيل الدخول بخطوتين</span>
                    </label>
                    <small>تفعيل المصادقة الثنائية</small>
                </div>
                
                <div class="form-group">
                    <label>عدد محاولات تسجيل الدخول الفاشلة</label>
                    <input type="number" name="max_login_attempts" value="<?php echo htmlspecialchars(getSetting('security', 'max_login_attempts', '5')); ?>" min="3" max="10" required>
                    <small>عدد المحاولات قبل الحظر</small>
                </div>
                
                <div class="form-group">
                    <label>مدة الحظر (بالدقائق)</label>
                    <input type="number" name="lockout_duration" value="<?php echo htmlspecialchars(getSetting('security', 'lockout_duration', '15')); ?>" min="5" max="60" required>
                    <small>مدة الحظر بعد تجاوز المحاولات</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="https_enabled" value="1" 
                               <?php echo getSetting('security', 'https_enabled', '0') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل HTTPS</span>
                    </label>
                    <small>تفعيل Cookie Secure (يتطلب HTTPS)</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="csrf_protection" value="1" 
                               <?php echo getSetting('security', 'csrf_protection', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل حماية CSRF</span>
                    </label>
                    <small>حماية من هجمات CSRF</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="xss_protection" value="1" 
                               <?php echo getSetting('security', 'xss_protection', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل حماية XSS</span>
                    </label>
                    <small>حماية من هجمات XSS</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember_me_enabled" value="1" 
                               <?php echo getSetting('security', 'remember_me_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل Remember Me</span>
                    </label>
                    <small>السماح للمستخدمين بتذكر تسجيل الدخول</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات الأمان</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات الإجازات -->
    <div id="tab-leaves" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="leaves">
            <div class="settings-grid">
                <div class="form-group">
                    <label>الرصيد الافتراضي للإجازات السنوية</label>
                    <input type="number" name="default_annual_leave" value="<?php echo htmlspecialchars(getSetting('leaves', 'default_annual_leave', '30')); ?>" min="0" max="365" step="0.5" required>
                    <small>عدد أيام الإجازة السنوية الافتراضية للموظف الجديد</small>
                </div>
                
                <div class="form-group">
                    <label>الرصيد الافتراضي للإجازات الشهرية</label>
                    <input type="number" name="default_monthly_leave" value="<?php echo htmlspecialchars(getSetting('leaves', 'default_monthly_leave', '2')); ?>" min="0" max="10" step="0.5" required>
                    <small>عدد أيام الإجازة الشهرية الافتراضية</small>
                </div>
                
                <div class="form-group">
                    <label>عدد أيام الإجازة المرضية المسموحة</label>
                    <input type="number" name="max_sick_leave" value="<?php echo htmlspecialchars(getSetting('leaves', 'max_sick_leave', '15')); ?>" min="0" max="365" required>
                    <small>الحد الأقصى لأيام الإجازة المرضية سنوياً</small>
                </div>
                
                <div class="form-group">
                    <label>عدد أيام الإجازة الطارئة المسموحة</label>
                    <input type="number" name="max_emergency_leave" value="<?php echo htmlspecialchars(getSetting('leaves', 'max_emergency_leave', '5')); ?>" min="0" max="30" required>
                    <small>الحد الأقصى لأيام الإجازة الطارئة سنوياً</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="unpaid_leave_enabled" value="1" 
                               <?php echo getSetting('leaves', 'unpaid_leave_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل الإجازات غير المدفوعة</span>
                    </label>
                    <small>السماح بطلب إجازات غير مدفوعة</small>
                </div>
                
                <div class="form-group">
                    <label>عدد أيام الإشعار المسبق للإجازة</label>
                    <input type="number" name="leave_notice_days" value="<?php echo htmlspecialchars(getSetting('leaves', 'leave_notice_days', '3')); ?>" min="0" max="30" required>
                    <small>الحد الأدنى لأيام الإشعار قبل طلب الإجازة</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="multi_approval_enabled" value="1" 
                               <?php echo getSetting('leaves', 'multi_approval_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل نظام الموافقات المتعددة</span>
                    </label>
                    <small>يتطلب موافقة عدة أشخاص على الإجازة</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات الإجازات</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات البريد الإلكتروني -->
    <div id="tab-email" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="email">
            <div class="settings-grid">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_enabled" value="1" 
                               <?php echo getSetting('email', 'email_enabled', '0') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل إرسال الإيميلات</span>
                    </label>
                    <small>تفعيل/تعطيل إرسال الإيميلات بالكامل</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP Server</label>
                    <input type="text" name="smtp_server" value="<?php echo htmlspecialchars(getSetting('email', 'smtp_server', '')); ?>" placeholder="smtp.gmail.com">
                    <small>عنوان خادم SMTP</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?php echo htmlspecialchars(getSetting('email', 'smtp_port', '587')); ?>" min="1" max="65535" required>
                    <small>منفذ SMTP (587 لـ TLS، 465 لـ SSL)</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input type="text" name="smtp_username" value="<?php echo htmlspecialchars(getSetting('email', 'smtp_username', '')); ?>" placeholder="your-email@gmail.com">
                    <small>اسم المستخدم لخادم SMTP</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_password" value="<?php echo htmlspecialchars(getSetting('email', 'smtp_password', '')); ?>" placeholder="••••••••">
                    <small>كلمة مرور SMTP</small>
                </div>
                
                <div class="form-group">
                    <label>نوع التشفير</label>
                    <select name="smtp_encryption" required>
                        <option value="tls" <?php echo getSetting('email', 'smtp_encryption', 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo getSetting('email', 'smtp_encryption') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo getSetting('email', 'smtp_encryption') == 'none' ? 'selected' : ''; ?>>بدون تشفير</option>
                    </select>
                    <small>نوع التشفير المستخدم</small>
                </div>
                
                <div class="form-group">
                    <label>عنوان المرسل (From Email)</label>
                    <input type="email" name="from_email" value="<?php echo htmlspecialchars(getSetting('email', 'from_email', '')); ?>" placeholder="noreply@example.com">
                    <small>البريد الإلكتروني الذي يظهر كمرسل</small>
                </div>
                
                <div class="form-group">
                    <label>اسم المرسل (From Name)</label>
                    <input type="text" name="from_name" value="<?php echo htmlspecialchars(getSetting('email', 'from_name', 'نظام إدارة الموظفين')); ?>" required>
                    <small>الاسم الذي يظهر كمرسل</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات البريد</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات الإشعارات -->
    <div id="tab-notifications" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="notifications">
            <div class="settings-grid">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notifications_enabled" value="1" 
                               <?php echo getSetting('notifications', 'notifications_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل الإشعارات</span>
                    </label>
                    <small>تفعيل/تعطيل جميع الإشعارات</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_new_employee" value="1" 
                               <?php echo getSetting('notifications', 'notify_new_employee', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات إضافة موظف جديد</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_employee_update" value="1" 
                               <?php echo getSetting('notifications', 'notify_employee_update', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات تعديل بيانات موظف</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_leave_request" value="1" 
                               <?php echo getSetting('notifications', 'notify_leave_request', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات طلبات الإجازات</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_leave_approval" value="1" 
                               <?php echo getSetting('notifications', 'notify_leave_approval', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات الموافقات/الرفض</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_session_timeout" value="1" 
                               <?php echo getSetting('notifications', 'notify_session_timeout', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات انتهاء الجلسة</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="notify_errors" value="1" 
                               <?php echo getSetting('notifications', 'notify_errors', '1') == '1' ? 'checked' : ''; ?>>
                        <span>إشعارات الأخطاء</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>طريقة الإشعار</label>
                    <select name="notification_method" required>
                        <option value="email" <?php echo getSetting('notifications', 'notification_method', 'both') == 'email' ? 'selected' : ''; ?>>البريد الإلكتروني فقط</option>
                        <option value="in-app" <?php echo getSetting('notifications', 'notification_method') == 'in-app' ? 'selected' : ''; ?>>داخل التطبيق فقط</option>
                        <option value="both" <?php echo getSetting('notifications', 'notification_method') == 'both' ? 'selected' : ''; ?>>كلاهما</option>
                    </select>
                    <small>طريقة إرسال الإشعارات</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات الإشعارات</button>
        </form>
    </div>
    
    <!-- تبويب إعدادات الملفات -->
    <div id="tab-files" class="tab-content">
        <form method="POST" action="settings/save.php" class="settings-form">
            <input type="hidden" name="group" value="files">
            <div class="settings-grid">
                <div class="form-group">
                    <label>الحد الأقصى لحجم الملف (MB)</label>
                    <input type="number" name="max_file_size" value="<?php echo htmlspecialchars(getSetting('files', 'max_file_size', '5')); ?>" min="1" max="100" required>
                    <small>الحد الأقصى لحجم الملفات المرفوعة</small>
                </div>
                
                <div class="form-group full-width">
                    <label>أنواع الملفات المسموحة</label>
                    <input type="text" name="allowed_file_types" value="<?php echo htmlspecialchars(getSetting('files', 'allowed_file_types', 'image/jpeg,image/png,image/gif,image/webp')); ?>" required>
                    <small>مفصولة بفواصل (مثال: image/jpeg,image/png,application/pdf)</small>
                </div>
                
                <div class="form-group">
                    <label>جودة ضغط الصور</label>
                    <input type="number" name="image_quality" value="<?php echo htmlspecialchars(getSetting('files', 'image_quality', '85')); ?>" min="50" max="100" required>
                    <small>نسبة الجودة من 50 إلى 100</small>
                </div>
                
                <div class="form-group">
                    <label>مكان حفظ الملفات</label>
                    <select name="file_storage" required>
                        <option value="local" <?php echo getSetting('files', 'file_storage', 'local') == 'local' ? 'selected' : ''; ?>>محلي (Local)</option>
                        <option value="cloud" <?php echo getSetting('files', 'file_storage') == 'cloud' ? 'selected' : ''; ?>>سحابي (Cloud)</option>
                    </select>
                    <small>مكان حفظ الملفات المرفوعة</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="upload_enabled" value="1" 
                               <?php echo getSetting('files', 'upload_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <span>تفعيل رفع الملفات</span>
                    </label>
                    <small>تفعيل/تعطيل رفع الملفات بالكامل</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="auto_cleanup" value="1" 
                               <?php echo getSetting('files', 'auto_cleanup', '0') == '1' ? 'checked' : ''; ?>>
                        <span>مسح الملفات القديمة تلقائياً</span>
                    </label>
                    <small>حذف الملفات غير المستخدمة تلقائياً</small>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 حفظ إعدادات الملفات</button>
        </form>
    </div>
</div>

<script>
// حذف تبويب البوت الذكي إذا كان موجوداً
(function() {
    'use strict';
    
    function removeBotTab() {
        // حذف زر التبويب
        const botButtons = document.querySelectorAll('.tab-button');
        botButtons.forEach(btn => {
            const text = btn.textContent || btn.innerText || '';
            if (text.includes('البوت') || text.includes('🤖') || text.includes('bot') || btn.onclick && btn.onclick.toString().includes('ai')) {
                btn.remove();
                console.log('تم حذف زر تبويب البوت الذكي');
            }
        });
        
        // حذف محتوى التبويب
        const botTab = document.getElementById('tab-ai');
        if (botTab) {
            botTab.remove();
            console.log('تم حذف محتوى تبويب البوت الذكي');
        }
    }
    
    // محاولة الحذف عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeBotTab);
    } else {
        removeBotTab();
    }
    
    // محاولة أخرى بعد تأخير قصير
    setTimeout(removeBotTab, 100);
    setTimeout(removeBotTab, 500); 
})();

function showTab(tabName) {
    // منع فتح تبويب البوت الذكي
    if (tabName === 'ai' || tabName === 'bot') {
        console.warn('تم منع فتح تبويب البوت الذكي');
        return;
    }
    
    // إخفاء جميع التبويبات
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // إزالة active من جميع الأزرار
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // إظهار التبويب المحدد
    const targetTab = document.getElementById('tab-' + tabName);
    if (targetTab) {
        targetTab.classList.add('active');
        if (event && event.target) {
            event.target.classList.add('active');
        }
    }
}

function toggleDarkMode(enabled) {
    if (typeof applyTheme === 'function') {
        applyTheme(enabled ? 'dark' : 'light');
    }
}

// التعامل مع اختيار الثيم من البطاقات
(function() {
    'use strict';
    
    // التأكد من تحميل الصفحة
    function initThemeSelector() {
        console.log('Initializing theme selector...');
        
        // التأكد من ظهور قسم اختيار الثيم
        const themeSection = document.getElementById('theme-selection-section');
        if (themeSection) {
            themeSection.style.display = 'block';
            themeSection.style.visibility = 'visible';
            themeSection.style.opacity = '1';
            console.log('Theme section found and made visible');
        } else {
            console.error('Theme section NOT FOUND!');
        }
        
        // عند اختيار ثيم جديد
        const radios = document.querySelectorAll('.theme-card input[type="radio"]');
        console.log('Found theme radios:', radios.length);
        
        radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const theme = this.value;
            
            // إزالة active من جميع البطاقات
            document.querySelectorAll('.theme-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // إضافة active للبطاقة المحددة
            this.closest('.theme-card').classList.add('active');
            
            // تطبيق الثيم فوراً
            if (typeof applyTheme === 'function') {
                applyTheme(theme);
            } else {
                // إذا لم يكن applyTheme موجوداً، استخدم localStorage مباشرة
                const actualTheme = theme === 'auto' ? 
                    (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : 
                    theme;
                document.documentElement.setAttribute('data-theme', actualTheme);
                localStorage.setItem('theme', theme);
            }
            
            // حفظ في قاعدة البيانات
            saveThemeToDatabase(theme);
            
            // إظهار إشعار
            showThemeNotification(theme);
        });
    });
    
    // عند النقر على بطاقة الثيم (بدون radio)
    document.querySelectorAll('.theme-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // إذا لم يكن النقر على label أو radio
            if (!e.target.closest('.theme-card-label') && !e.target.closest('input')) {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });
        });
        
        // عند النقر على بطاقة الثيم (بدون radio)
        const cards = document.querySelectorAll('.theme-card');
        console.log('Found theme cards:', cards.length);
        
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                // إذا لم يكن النقر على label أو radio
                if (!e.target.closest('.theme-card-label') && !e.target.closest('input')) {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                }
            });
        });
    }
    
    // محاولة التهيئة عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeSelector);
    } else {
        // الصفحة محملة بالفعل
        initThemeSelector();
    }
    
    // محاولة أخرى بعد تأخير قصير (للتأكد)
    setTimeout(initThemeSelector, 500);
})();

// حفظ الثيم في قاعدة البيانات
function saveThemeToDatabase(theme) {
    const formData = new FormData();
    formData.append('group', 'display');
    formData.append('user_theme', theme);
    formData.append('dark_mode_enabled', (theme === 'dark' || theme === 'dark-blue' || theme === 'dark-pink') ? '1' : '0');
    
    const siteUrl = window.SITE_URL || '';
    if (siteUrl) {
        fetch(siteUrl + '/admin/settings/save.php', {
            method: 'POST',
            body: formData
        }).catch(err => {
            console.log('Theme saved locally only');
        });
    }
}

// إظهار إشعار عند تغيير الثيم
function showThemeNotification(theme) {
    const themes = {
        'auto': '🔄 تم تفعيل الوضع التلقائي',
        'light': '☀️ تم تفعيل الوضع النهاري',
        'dark': '🌙 تم تفعيل الوضع الليلي',
        'dark-blue': '🌃 تم تفعيل الثيم الأزرق الليلي',
        'dark-pink': '🌺 تم تفعيل الثيم الوردي الليلي',
        'classic': '📜 تم تفعيل الثيم الكلاسيكي',
        'blue': '💙 تم تفعيل الثيم الأزرق العصري',
        'elegant': '✨ تم تفعيل الثيم الأنيق',
        'vibrant': '🌈 تم تفعيل الثيم النابض',
        'pink': '🌸 تم تفعيل الثيم الوردي'
    };
    
    const message = themes[theme] || 'تم تغيير الثيم';
    
    // إنشاء إشعار مؤقت
    const notification = document.createElement('div');
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

