<?php
// config.php — داخل المشروع فقط

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'event_management_csc382');
define('DB_USER', 'root');
define('DB_PASS', ''); // يترك فارغًا افتراضياً للمستخدم المحلي — المستخدم يغيره يدويًا

define('BASE_PATH', __DIR__); // جذر المشروع
define('UPLOAD_DIR', BASE_PATH . '/uploads/events');

// حساب المسار الأساسي للـ URL (يعمل في بيئة الإنتاج والتطوير)
if (!defined('BASE_URL')) {
    // حساب المسار من موقع config.php (جذر المشروع) إلى DOCUMENT_ROOT
    $configDir = str_replace('\\', '/', __DIR__);
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    // إزالة DOCUMENT_ROOT من مسار config.php للحصول على المسار النسبي
    if (strpos($configDir, $docRoot) === 0) {
        $baseUrl = substr($configDir, strlen($docRoot));
    } else {
        // إذا لم يكن config.php داخل DOCUMENT_ROOT، استخدم SCRIPT_NAME
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // إذا كان السكربت في pages/، عد للخلف مستوى واحد للوصول للجذر
        if (strpos($scriptDir, '/pages') !== false || strpos($scriptDir, '\\pages') !== false) {
            $baseUrl = dirname($scriptDir);
        } elseif (strpos($scriptDir, '/includes') !== false || strpos($scriptDir, '\\includes') !== false) {
            // إذا كان في includes/، عد للخلف مستوى واحد
            $baseUrl = dirname($scriptDir);
        } else {
            $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : $scriptDir;
        }
    }
    
    // تنظيف المسار
    $baseUrl = rtrim($baseUrl, '/\\');
    
    // إذا كان المسار فارغاً أو '/' فقط، استخدم '' للجذر
    if (empty($baseUrl) || $baseUrl === '/') {
        $baseUrl = '';
    }
    
    define('BASE_URL', $baseUrl);
}

// إعدادات الأمان
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// مفتاح CSRF (يمكن تغييره في بيئة الإنتاج)
define('CSRF_SECRET', 'event_management_csc382_secret_key_change_in_production');

