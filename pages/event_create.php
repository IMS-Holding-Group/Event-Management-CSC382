<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// التحقق من الصلاحيات
if (!isOrganizer()) {
    header('Location: ' . url('index.php'));
    exit;
}

$pageTitle = 'إنشاء فعالية جديدة';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'رمز الأمان غير صحيح. يرجى المحاولة مرة أخرى.';
    } else {
        // جمع البيانات
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_datetime = trim($_POST['start_datetime'] ?? '');
        $end_datetime = trim($_POST['end_datetime'] ?? '');
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        
        // التحقق من البيانات
        if (empty($title) || !validateLength($title, 1, 255)) {
            $errors[] = 'العنوان مطلوب ويجب أن يكون بين 1 و 255 حرفاً.';
        }
        
        if (!empty($description) && !validateLength($description, 0, 5000)) {
            $errors[] = 'الوصف طويل جداً (الحد الأقصى 5000 حرف).';
        }
        
        if (!empty($location) && !validateLength($location, 0, 255)) {
            $errors[] = 'الموقع طويل جداً (الحد الأقصى 255 حرف).';
        }
        
        if (empty($start_datetime)) {
            $errors[] = 'تاريخ ووقت البدء مطلوب.';
        } else {
            $startDate = DateTime::createFromFormat('Y-m-d\TH:i', $start_datetime);
            if (!$startDate) {
                $errors[] = 'تاريخ ووقت البدء غير صحيح.';
            }
        }
        
        if (!empty($end_datetime)) {
            $endDate = DateTime::createFromFormat('Y-m-d\TH:i', $end_datetime);
            if (!$endDate) {
                $errors[] = 'تاريخ ووقت الانتهاء غير صحيح.';
            } elseif (isset($startDate) && $endDate < $startDate) {
                $errors[] = 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء.';
            }
        }
        
        if ($capacity < 0) {
            $errors[] = 'السعة يجب أن تكون رقماً موجباً أو صفر.';
        }
        
        // معالجة رفع الصورة
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            // التحقق من الحجم
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'حجم الصورة كبير جداً (الحد الأقصى 2MB).';
            }
            
            // التحقق من الامتداد
            if (!isAllowedFileExtension($file['name'])) {
                $errors[] = 'نوع الملف غير مسموح. المسموح: jpg, jpeg, png, webp';
            }
            
            if (empty($errors)) {
                // إنشاء المجلد إذا لم يكن موجوداً
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                
                // إنشاء اسم فريد للملف
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $image_filename = uniqid('event_', true) . '.' . $ext;
                $target_path = UPLOAD_DIR . '/' . $image_filename;
                
                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $errors[] = 'فشل رفع الصورة.';
                }
            }
        }
        
        // إدراج البيانات في قاعدة البيانات
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO events (title, description, location, start_datetime, end_datetime, capacity, image_filename, created_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title,
                    $description ?: null,
                    $location ?: null,
                    $start_datetime,
                    $end_datetime ?: null,
                    $capacity,
                    $image_filename,
                    $_SESSION['user_id']
                ]);
                
                $success = true;
                header('Location: ' . url('index.php?created=1'));
                exit;
            } catch (PDOException $e) {
                error_log("Error creating event: " . $e->getMessage());
                $errors[] = 'حدث خطأ أثناء إنشاء الفعالية. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>إنشاء فعالية جديدة</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo escape($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="title">عنوان الفعالية *</label>
            <input type="text" id="title" name="title" required maxlength="255" 
                   value="<?php echo isset($_POST['title']) ? escape($_POST['title']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="description">الوصف</label>
            <textarea id="description" name="description" maxlength="5000" 
                      rows="5"><?php echo isset($_POST['description']) ? escape($_POST['description']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="location">الموقع</label>
            <input type="text" id="location" name="location" maxlength="255" 
                   value="<?php echo isset($_POST['location']) ? escape($_POST['location']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="start_datetime">تاريخ ووقت البدء *</label>
            <input type="datetime-local" id="start_datetime" name="start_datetime" required 
                   value="<?php echo isset($_POST['start_datetime']) ? escape($_POST['start_datetime']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="end_datetime">تاريخ ووقت الانتهاء</label>
            <input type="datetime-local" id="end_datetime" name="end_datetime" 
                   value="<?php echo isset($_POST['end_datetime']) ? escape($_POST['end_datetime']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="capacity">السعة (عدد المقاعد)</label>
            <input type="number" id="capacity" name="capacity" min="0" 
                   value="<?php echo isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0; ?>">
            <div class="help-text">اتركه 0 لعدم تحديد سعة محددة</div>
        </div>
        
        <div class="form-group">
            <label for="image">صورة الفعالية</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">
            <div class="help-text">الحجم الأقصى: 2MB. المسموح: jpg, png, webp</div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">إنشاء الفعالية</button>
            <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

