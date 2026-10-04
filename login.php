<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'تسجيل الدخول';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $errors[] = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        if (login($username, $password)) {
            header('Location: ' . url('index.php'));
            exit;
        } else {
            $errors[] = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}

// إذا كان المستخدم مسجلاً بالفعل، إعادة توجيه
if (isAuthenticated()) {
    header('Location: ' . url('index.php'));
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>تسجيل الدخول</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo escape($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" data-validate style="max-width: 400px; margin: 2rem auto;">
        <div class="form-group">
            <label for="username">اسم المستخدم *</label>
            <input type="text" id="username" name="username" required 
                   value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="password">كلمة المرور *</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
            <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">العودة</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

