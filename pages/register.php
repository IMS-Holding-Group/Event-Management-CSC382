<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($eventId <= 0) {
    header('Location: ' . url('index.php'));
    exit;
}

// جلب بيانات الفعالية
$stmt = $pdo->prepare("SELECT e.*, 
        COALESCE(SUM(r.seats), 0) as total_seats
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.id = ?
        GROUP BY e.id");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . url('index.php'));
    exit;
}

$remainingSeats = $event['capacity'] > 0 ? max(0, $event['capacity'] - $event['total_seats']) : 0;
$pageTitle = 'تسجيل حضور: ' . escape($event['title']);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'رمز الأمان غير صحيح. يرجى المحاولة مرة أخرى.';
    } else {
        // جمع البيانات
        $user_name = trim($_POST['user_name'] ?? '');
        $user_email = trim($_POST['user_email'] ?? '');
        $seats = isset($_POST['seats']) ? (int)$_POST['seats'] : 1;
        
        // التحقق من البيانات
        if (empty($user_name) || !validateLength($user_name, 1, 150)) {
            $errors[] = 'الاسم مطلوب ويجب أن يكون بين 1 و 150 حرفاً.';
        }
        
        if (empty($user_email) || !validateEmail($user_email)) {
            $errors[] = 'البريد الإلكتروني مطلوب ويجب أن يكون صحيحاً.';
        } elseif (!validateLength($user_email, 0, 255)) {
            $errors[] = 'البريد الإلكتروني طويل جداً.';
        }
        
        if ($seats < 1) {
            $errors[] = 'عدد المقاعد يجب أن يكون رقماً موجباً.';
        }
        
        // التحقق من السعة المتبقية
        if ($event['capacity'] > 0) {
            if ($remainingSeats < $seats) {
                $errors[] = "المقاعد المتبقية غير كافية. المتبقي: $remainingSeats مقعد.";
            }
        }
        
        // التحقق من عدم التسجيل المسبق بنفس البريد
        $stmt = $pdo->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_email = ?");
        $stmt->execute([$eventId, $user_email]);
        if ($stmt->fetch()) {
            $errors[] = 'تم التسجيل مسبقاً بهذا البريد الإلكتروني لهذه الفعالية.';
        }
        
        // إدراج التسجيل
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO registrations (event_id, user_name, user_email, seats) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$eventId, $user_name, $user_email, $seats]);
                
                $success = true;
            } catch (PDOException $e) {
                error_log("Error registering for event: " . $e->getMessage());
                $errors[] = 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>تسجيل حضور</h1>
    
    <div class="event-card" style="margin-bottom: 2rem;">
        <h2><?php echo escape($event['title']); ?></h2>
        <div class="event-meta">
            <p><strong>التاريخ:</strong> <?php echo formatDateTime($event['start_datetime']); ?></p>
            <?php if ($event['location']): ?>
                <p><strong>الموقع:</strong> <?php echo escape($event['location']); ?></p>
            <?php endif; ?>
            <?php if ($event['capacity'] > 0): ?>
                <p><strong>المقاعد المتبقية:</strong> <?php echo $remainingSeats; ?> / <?php echo $event['capacity']; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <p>تم التسجيل بنجاح! شكراً لتسجيلك في هذه الفعالية.</p>
            <a href="<?php echo url('pages/event_view.php?id=' . $eventId); ?>" class="btn btn-primary" style="margin-top: 1rem;">عودة لصفحة الفعالية</a>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($event['capacity'] > 0 && $remainingSeats <= 0): ?>
            <div class="alert alert-error">
                <p>عذراً، لا توجد مقاعد متاحة لهذه الفعالية.</p>
            </div>
        <?php else: ?>
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="user_name">الاسم الكامل *</label>
                    <input type="text" id="user_name" name="user_name" required maxlength="150" 
                           value="<?php echo isset($_POST['user_name']) ? escape($_POST['user_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_email">البريد الإلكتروني *</label>
                    <input type="email" id="user_email" name="user_email" required maxlength="255" 
                           value="<?php echo isset($_POST['user_email']) ? escape($_POST['user_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="seats">عدد المقاعد *</label>
                    <input type="number" id="seats" name="seats" required min="1" 
                           max="<?php echo $event['capacity'] > 0 ? $remainingSeats : 999; ?>" 
                           value="<?php echo isset($_POST['seats']) ? (int)$_POST['seats'] : 1; ?>"
                           data-event-id="<?php echo $eventId; ?>">
                    <?php if ($event['capacity'] > 0): ?>
                        <div class="help-text">المقاعد المتبقية: <span data-remaining-seats="<?php echo $eventId; ?>"><?php echo $remainingSeats; ?></span></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success">تأكيد التسجيل</button>
                    <a href="<?php echo url('pages/event_view.php?id=' . $eventId); ?>" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

