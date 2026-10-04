<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) : 'إدارة الفعاليات'; ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/styles.css'); ?>">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">
                    <a href="<?php echo url('index.php'); ?>">إدارة الفعاليات</a>
                </h1>
                <nav class="main-nav">
                    <a href="<?php echo url('index.php'); ?>">الرئيسية</a>
                    <?php if (isAuthenticated()): ?>
                        <a href="<?php echo url('pages/event_create.php'); ?>">إنشاء فعالية</a>
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo url('pages/admin.php'); ?>">لوحة التحكم</a>
                        <?php endif; ?>
                        <a href="<?php echo url('logout.php'); ?>">تسجيل الخروج</a>
                    <?php else: ?>
                        <a href="<?php echo url('login.php'); ?>">تسجيل الدخول</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    <main class="main-content">

