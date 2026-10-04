<?php
// helpers.php — دوال مساعدة للتحقق والهروب

// تحميل config.php إذا لم يكن محملاً
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * تنظيف وإخراج نص آمن للعرض في HTML
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من أن القيمة عدد صحيح موجب
 */
function validatePositiveInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

/**
 * التحقق من طول النص
 */
function validateLength($string, $min = 0, $max = 255) {
    $length = mb_strlen($string, 'UTF-8');
    return $length >= $min && $length <= $max;
}

/**
 * إنشاء CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من CSRF token
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تنسيق التاريخ والوقت للعرض
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    $date = new DateTime($datetime);
    return $date->format('Y-m-d H:i');
}

/**
 * التحقق من امتداد الملف المسموح
 */
function isAllowedFileExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

/**
 * الحصول على المسار الأساسي للمشروع
 */
function getBaseUrl() {
    // استخدام الثابت من config.php (يتم حسابه في config.php)
    if (defined('BASE_URL')) {
        $base = BASE_URL;
        
        // إذا كان BASE_URL يحتوي على /pages أو \pages، نزيله
        while (strpos($base, '/pages') !== false || strpos($base, '\\pages') !== false || 
               strpos($base, '/pages/') !== false || strpos($base, '\\pages\\') !== false ||
               $base === '/pages' || $base === '\\pages') {
            $base = dirname($base);
            // تنظيف المسار
            $base = rtrim($base, '/\\');
            if ($base === '/' || $base === '\\' || $base === '.') {
                $base = '';
                break;
            }
        }
        
        return $base;
    }
    
    // احتياطي: حساب المسار ديناميكياً
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);
    
    // إذا كان الملف في pages/، نعود مستوى واحد
    while (strpos($scriptDir, '/pages') !== false || strpos($scriptDir, '\\pages') !== false ||
           $scriptDir === '/pages' || $scriptDir === '\\pages') {
        $scriptDir = dirname($scriptDir);
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            $scriptDir = '';
            break;
        }
    }
    
    // إذا كان في الجذر
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        return '';
    }
    
    return rtrim($scriptDir, '/\\');
}

/**
 * إنشاء رابط نسبي
 */
function url($path) {
    $base = getBaseUrl();
    $path = ltrim($path, '/');
    
    // إذا كان المسار يحتوي على استعلامات
    if (strpos($path, '?') !== false) {
        $parts = explode('?', $path, 2);
        $file = $parts[0];
        $query = $parts[1];
        // إذا كان BASE_URL فارغاً، ابدأ من الجذر
        if (empty($base)) {
            return '/' . $file . '?' . $query;
        }
        return $base . '/' . $file . '?' . $query;
    }
    
    // إذا كان BASE_URL فارغاً، ابدأ من الجذر
    if (empty($base)) {
        return '/' . $path;
    }
    
    return $base . '/' . $path;
}

