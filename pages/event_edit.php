<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// التحقق من الصلاحيات
if (!isOrganizer()) {
    header('Location: ' . url('index.php'));
    exit;
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    header('Location: ' . url('index.php'));
    exit;
}

// جلب بيانات الفعالية
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . url('index.php'));
    exit;
}

$pageTitle = 'تعديل فعالية: ' . escape($event['title']);
$errors = [];

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
        $delete_image = isset($_POST['delete_image']) && $_POST['delete_image'] === '1';
        
        // التحقق من البيانات (نفس التحقق في event_create.php)
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
        $image_filename = $event['image_filename'];
        
        if ($delete_image && $image_filename) {
            $old_path = UPLOAD_DIR . '/' . $image_filename;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
            $image_filename = null;
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            if ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'حجم الصورة كبير جداً (الحد الأقصى 2MB).';
            }
            
            if (!isAllowedFileExtension($file['name'])) {
                $errors[] = 'نوع الملف غير مسموح. المسموح: jpg, jpeg, png, webp';
            }
            
            if (empty($errors)) {
                // حذف الصورة القديمة إن وجدت
                if ($image_filename) {
                    $old_path = UPLOAD_DIR . '/' . $image_filename;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                
                // إنشاء المجلد إذا لم يكن موجوداً
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $image_filename = uniqid('event_', true) . '.' . $ext;
                $target_path = UPLOAD_DIR . '/' . $image_filename;
                
                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $errors[] = 'فشل رفع الصورة.';
                }
            }
        }
        
        // تحديث البيانات في قاعدة البيانات
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, location = ?, 
                                      start_datetime = ?, end_datetime = ?, capacity = ?, image_filename = ? 
                                      WHERE id = ?");
                $stmt->execute([
                    $title,
                    $description ?: null,
                    $location ?: null,
                    $start_datetime,
                    $end_datetime ?: null,
                    $capacity,
                    $image_filename,
                    $eventId
                ]);
                
                header('Location: ' . url('pages/event_view.php?id=' . $eventId . '&updated=1'));
                exit;
            } catch (PDOException $e) {
                error_log("Error updating event: " . $e->getMessage());
                $errors[] = 'حدث خطأ أثناء تحديث الفعالية. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

// تنسيق التاريخ للعرض في input datetime-local
$start_datetime_formatted = $event['start_datetime'] ? date('Y-m-d\TH:i', strtotime($event['start_datetime'])) : '';
$end_datetime_formatted = $event['end_datetime'] ? date('Y-m-d\TH:i', strtotime($event['end_datetime'])) : '';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>تعديل فعالية</h1>
    
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
                   value="<?php echo escape($event['title']); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">الوصف</label>
            <textarea id="description" name="description" maxlength="5000" 
                      rows="5"><?php echo escape($event['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="location">الموقع</label>
            <input type="text" id="location" name="location" maxlength="255" 
                   value="<?php echo escape($event['location'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="start_datetime">تاريخ ووقت البدء *</label>
            <input type="datetime-local" id="start_datetime" name="start_datetime" required 
                   value="<?php echo $start_datetime_formatted; ?>">
        </div>
        
        <div class="form-group">
            <label for="end_datetime">تاريخ ووقت الانتهاء</label>
            <input type="datetime-local" id="end_datetime" name="end_datetime" 
                   value="<?php echo $end_datetime_formatted; ?>">
        </div>
        
        <div class="form-group">
            <label for="capacity">السعة (عدد المقاعد)</label>
            <input type="number" id="capacity" name="capacity" min="0" 
                   value="<?php echo $event['capacity']; ?>">
            <div class="help-text">اتركه 0 لعدم تحديد سعة محددة</div>
        </div>
        
        <div class="form-group">
            <label>الصورة الحالية</label>
            <?php if ($event['image_filename'] && file_exists(UPLOAD_DIR . '/' . $event['image_filename'])): ?>
                <div style="margin: 1rem 0;">
                    <img src="<?php echo url('uploads/events/' . $event['image_filename']); ?>" 
                         alt="صورة الفعالية" style="max-width: 300px; border-radius: 6px;">
                </div>
                <label>
                    <input type="checkbox" name="delete_image" value="1">
                    حذف الصورة الحالية
                </label>
            <?php else: ?>
                <p style="color: var(--text-secondary);">لا توجد صورة</p>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="image">صورة جديدة</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">
            <div class="help-text">الحجم الأقصى: 2MB. المسموح: jpg, png, webp</div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
            <a href="<?php echo url('pages/event_view.php?id=' . $eventId); ?>" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

