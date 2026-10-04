<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'قائمة الفعاليات';

// البحث والفرز
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'start_datetime';

// بناء الاستعلام
$sql = "SELECT e.*, 
        COUNT(r.id) as registered_count,
        COALESCE(SUM(r.seats), 0) as total_seats
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND e.title LIKE ?";
    $params[] = "%$search%";
}

$sql .= " GROUP BY e.id";

// الفرز
$allowedSorts = ['start_datetime', 'title', 'created_at'];
if (in_array($sort, $allowedSorts)) {
    $sql .= " ORDER BY e.$sort ASC";
} else {
    $sql .= " ORDER BY e.start_datetime ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>قائمة الفعاليات</h1>
    
    <div class="search-filter">
        <form method="GET" action="">
            <div class="form-group">
                <label for="search">بحث في العناوين:</label>
                <input type="text" id="search" name="search" value="<?php echo escape($search); ?>" placeholder="ابحث عن فعالية...">
            </div>
            <div class="form-group">
                <label for="sort">ترتيب حسب:</label>
                <select id="sort" name="sort">
                    <option value="start_datetime" <?php echo $sort === 'start_datetime' ? 'selected' : ''; ?>>تاريخ البدء</option>
                    <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>العنوان</option>
                    <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>تاريخ الإنشاء</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">بحث</button>
            <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">إعادة تعيين</a>
        </form>
    </div>
    
    <?php if (empty($events)): ?>
        <div class="alert alert-info">
            <p>لا توجد فعاليات متاحة حالياً.</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <h2><?php echo escape($event['title']); ?></h2>
                    <div class="event-meta">
                        <p><strong>التاريخ:</strong> <?php echo formatDateTime($event['start_datetime']); ?></p>
                        <?php if ($event['location']): ?>
                            <p><strong>الموقع:</strong> <?php echo escape($event['location']); ?></p>
                        <?php endif; ?>
                        <?php if ($event['capacity'] > 0): ?>
                            <p><strong>المقاعد:</strong> <?php echo $event['total_seats']; ?> / <?php echo $event['capacity']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['description']): ?>
                        <div class="event-description">
                            <?php echo escape(mb_substr($event['description'], 0, 150)); ?>
                            <?php if (mb_strlen($event['description']) > 150): ?>...<?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="event-actions">
                        <a href="<?php echo url('pages/event_view.php?id=' . $event['id']); ?>" class="btn btn-primary">عرض التفاصيل</a>
                        <a href="<?php echo url('pages/register.php?event_id=' . $event['id']); ?>" class="btn btn-success">تسجيل حضور</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

