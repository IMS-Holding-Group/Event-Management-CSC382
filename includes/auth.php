<?php
// auth.php — دوال التحقق من الهوية (مبسطة للتطوير)

require_once __DIR__ . '/helpers.php';

/**
 * التحقق من تسجيل الدخول (مبسط - يمكن تحسينه)
 * في بيئة التطوير: يمكن استخدام مفتاح سري ثابت
 */
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * التحقق من أن المستخدم مسؤول
 */
function isAdmin() {
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * التحقق من أن المستخدم منظم
 */
function isOrganizer() {
    return isAuthenticated() && (isAdmin() || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'organizer'));
}

/**
 * تسجيل الدخول
 */
function login($username, $password) {
    require_once __DIR__ . '/db_connect.php';
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

/**
 * تسجيل الخروج
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
}

/**
 * إعادة التوجيه إذا لم يكن المستخدم مسجلاً
 */
function requireAuth() {
    if (!isAuthenticated()) {
        require_once __DIR__ . '/helpers.php';
        header('Location: ' . url('login.php'));
        exit;
    }
}

/**
 * إعادة التوجيه إذا لم يكن المستخدم مسؤولاً
 */
function requireAdmin() {
    if (!isAdmin()) {
        require_once __DIR__ . '/helpers.php';
        header('Location: ' . url('index.php'));
        exit;
    }
}

