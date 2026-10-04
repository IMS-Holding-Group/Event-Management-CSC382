<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    header('Location: ' . url('index.php'));
    exit;
}

// جلب بيانات الفعالية
$stmt = $pdo->prepare("SELECT e.*, 
        COUNT(r.id) as registered_count,
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
$pageTitle = escape($event['title']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="event-detail">
        <h1><?php echo escape($event['title']); ?></h1>
        
        <?php if ($event['image_filename'] && file_exists(UPLOAD_DIR . '/' . $event['image_filename'])): ?>
            <img src="<?php echo url('uploads/events/' . $event['image_filename']); ?>" 
                 alt="<?php echo escape($event['title']); ?>" 
                 class="event-detail-image">
        <?php endif; ?>
        
        <div class="event-detail-meta">
            <div>
                <strong>تاريخ البدء:</strong> <?php echo formatDateTime($event['start_datetime']); ?>
            </div>
            <?php if ($event['end_datetime']): ?>
                <div>
                    <strong>تاريخ الانتهاء:</strong> <?php echo formatDateTime($event['end_datetime']); ?>
                </div>
            <?php endif; ?>
            <?php if ($event['location']): ?>
                <div>
                    <strong>الموقع:</strong> <?php echo escape($event['location']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($event['description']): ?>
            <div class="event-detail-description">
                <?php echo nl2br(escape($event['description'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($event['capacity'] > 0): ?>
            <div class="capacity-info">
                <strong>السعة الإجمالية:</strong> <?php echo $event['capacity']; ?> مقعد<br>
                <strong>المقاعد المحجوزة:</strong> <?php echo $event['total_seats']; ?> مقعد<br>
                <strong>المقاعد المتبقية:</strong> <span data-remaining-seats="<?php echo $event['id']; ?>"><?php echo $remainingSeats; ?></span> مقعد
            </div>
        <?php endif; ?>
        
        <div class="event-actions" style="margin-top: 2rem;">
            <a href="<?php echo url('pages/register.php?event_id=' . $event['id']); ?>" class="btn btn-success">
                تسجيل حضور
            </a>
            <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">العودة للقائمة</a>
            <?php if (isOrganizer()): ?>
                <a href="<?php echo url('pages/event_edit.php?id=' . $event['id']); ?>" class="btn btn-primary">تعديل</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

