<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// التحقق من الصلاحيات
requireAdmin();

$pageTitle = 'لوحة التحكم';
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// حذف تسجيل
if (isset($_GET['delete_registration']) && $eventId > 0) {
    $regId = (int)$_GET['delete_registration'];
    if ($regId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ? AND event_id = ?");
            $stmt->execute([$regId, $eventId]);
                header('Location: ' . url('pages/admin.php?event_id=' . $eventId . '&deleted=1'));
                exit;
        } catch (PDOException $e) {
            error_log("Error deleting registration: " . $e->getMessage());
        }
    }
}

// جلب قائمة الفعاليات
$stmt = $pdo->query("SELECT e.*, 
    COUNT(r.id) as registration_count,
    COALESCE(SUM(r.seats), 0) as total_seats
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    GROUP BY e.id
    ORDER BY e.created_at DESC");
$events = $stmt->fetchAll();

// جلب التسجيلات لفعالية محددة
$registrations = [];
if ($eventId > 0) {
    $stmt = $pdo->prepare("SELECT r.*, e.title as event_title 
                           FROM registrations r
                           JOIN events e ON r.event_id = e.id
                           WHERE r.event_id = ?
                           ORDER BY r.registered_at DESC");
    $stmt->execute([$eventId]);
    $registrations = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1>لوحة التحكم</h1>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            <p>تم حذف التسجيل بنجاح.</p>
        </div>
    <?php endif; ?>
    
    <h2>الفعاليات</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>التاريخ</th>
                    <th>عدد التسجيلات</th>
                    <th>المقاعد المحجوزة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">لا توجد فعاليات</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo escape($event['title']); ?></td>
                            <td><?php echo formatDateTime($event['start_datetime']); ?></td>
                            <td><?php echo $event['registration_count']; ?></td>
                            <td><?php echo $event['total_seats']; ?> / <?php echo $event['capacity'] > 0 ? $event['capacity'] : '∞'; ?></td>
                            <td>
                                <a href="<?php echo url('pages/admin.php?event_id=' . $event['id']); ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">عرض التسجيلات</a>
                                <a href="<?php echo url('pages/event_edit.php?id=' . $event['id']); ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">تعديل</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($eventId > 0 && !empty($registrations)): ?>
        <h2 style="margin-top: 3rem;">تسجيلات: <?php echo escape($registrations[0]['event_title']); ?></h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>عدد المقاعد</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo escape($reg['user_name']); ?></td>
                            <td><?php echo escape($reg['user_email']); ?></td>
                            <td><?php echo $reg['seats']; ?></td>
                            <td><?php echo formatDateTime($reg['registered_at']); ?></td>
                            <td>
                                <a href="<?php echo url('pages/admin.php?event_id=' . $eventId . '&delete_registration=' . $reg['id']); ?>" 
                                   class="btn btn-danger" 
                                   style="padding: 0.5rem 1rem; font-size: 0.9rem;"
                                   data-confirm-delete>حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($eventId > 0): ?>
        <div class="alert alert-info" style="margin-top: 2rem;">
            <p>لا توجد تسجيلات لهذه الفعالية.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

