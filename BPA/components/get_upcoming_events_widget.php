<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/DatabaseConnection.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'html' => '<p style="color: #666; text-align: center; padding: 20px; font-size: 14px;">No upcoming events matching your interests</p>'
    ]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$limit = isset($_GET['limit']) && ctype_digit((string)$_GET['limit']) ? (int)$_GET['limit'] : 5;
$limit = max(1, min($limit, 10));

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

$upcomingEvents = [];
$sql = "
SELECT DISTINCT
    e.events_id,
    e.events_title,
    e.events_date,
    e.events_start,
    e.events_end,
    e.events_deadline
FROM events e
INNER JOIN event_subjects es ON e.events_id = es.es_event_id
INNER JOIN user_interests ui ON es.es_subject_id = ui.ui_subject_id AND ui.ui_user_id = ?
WHERE
    TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59')) > NOW()
    AND (e.events_deadline IS NULL OR TIMESTAMP(e.events_deadline, '23:59:59') > NOW())
    AND NOT EXISTS(
        SELECT 1 FROM event_participants ep
        WHERE ep.ep_event_id = e.events_id AND ep.ep_user_id = ?
    )
    AND e.host_user_id <> ?
    AND e.events_visibility = 'public'
GROUP BY e.events_id
ORDER BY e.events_date ASC
LIMIT {$limit}
";

$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param('iii', $currentUserId, $currentUserId, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $eventDate = new DateTime($row['events_date']);
        $day = $eventDate->format('d');
        $month = $eventDate->format('M');

        $timeDisplay = '';
        if (!empty($row['events_start'])) {
            $startTime = new DateTime($row['events_date'] . ' ' . $row['events_start']);
            $timeDisplay = $startTime->format('h:i A');
            if (!empty($row['events_end'])) {
                $endTime = new DateTime($row['events_date'] . ' ' . $row['events_end']);
                $timeDisplay .= ' - ' . $endTime->format('h:i A');
            }
        }

        $upcomingEvents[] = [
            'id' => (int)$row['events_id'],
            'title' => $row['events_title'],
            'day' => $day,
            'month' => $month,
            'time' => $timeDisplay,
        ];
    }

    $stmt->close();
}

$dbConn->closeConnection();

ob_start();
if (empty($upcomingEvents)) {
    echo '<p style="color: #666; text-align: center; padding: 20px; font-size: 14px;">No upcoming events matching your interests</p>';
} else {
    foreach ($upcomingEvents as $event) {
        ?>
        <a href="../events/events.php?event_id=<?php echo $event['id']; ?>" class="side-event-item">
            <div class="side-event-date">
                <span class="side-event-day"><?php echo htmlspecialchars($event['day']); ?></span>
                <span class="side-event-month"><?php echo htmlspecialchars($event['month']); ?></span>
            </div>
            <div class="side-event-info">
                <h4 class="side-event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                <?php if (!empty($event['time'])): ?>
                <p class="side-event-meta"><?php echo htmlspecialchars($event['time']); ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php
    }
}

$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
exit;
?>