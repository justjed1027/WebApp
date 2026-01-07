<?php
// cleanup_old_events.php
// Deletes events older than 7 days after their event date/end.
// Intended for CLI (Windows Task Scheduler) to save DB space.

use mysqli_sql_exception;

session_start();
require_once __DIR__ . '/../database/DatabaseConnection.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow CLI by default. For web/manual trigger, require a token.
$allowWeb = false; // set true if you want to allow via HTTP with a token
$token = isset($_GET['token']) ? $_GET['token'] : null;
$validToken = getenv('EVENTS_CLEANUP_TOKEN') ?: null; // optionally set in environment

// Optional dry-run and days overrides
$dryRun = false;
$days = 7; // retention window after events_date

// Parse CLI args if present
if (php_sapi_name() === 'cli' && isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--dry-run') $dryRun = true;
        if (strpos($arg, '--days=') === 0) {
            $val = (int)substr($arg, strlen('--days='));
            if ($val > 0) $days = $val;
        }
    }
} else {
    // Web query params
    if (!$allowWeb || !$token || !$validToken || !hash_equals($validToken, $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: CLI only or valid token required']);
        exit;
    }
    if (isset($_GET['dryRun'])) $dryRun = $_GET['dryRun'] == '1';
    if (isset($_GET['days'])) {
        $val = (int)$_GET['days'];
        if ($val > 0) $days = $val;
    }
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connection;

    // Find candidate events to delete strictly based on events_date:
    // Delete when today's date is greater than events_date + N days.
    // Example: events_date=2026-01-20, N=7 -> delete on 2026-01-28 (CURDATE() > 2026-01-27).
    $sql = "SELECT events_id FROM events
            WHERE events_date IS NOT NULL
              AND CURDATE() > DATE_ADD(events_date, INTERVAL ? DAY)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new mysqli_sql_exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $toDelete = [];
    while ($row = $result->fetch_assoc()) {
        $toDelete[] = (int)$row['events_id'];
    }
    $stmt->close();

    if ($dryRun) {
        echo json_encode([
            'success' => true,
            'message' => 'Dry-run: no deletions performed',
            'days' => $days,
            'wouldDelete' => $toDelete,
            'count' => count($toDelete)
        ]);
        $db->closeConnection();
        exit;
    }

    $conn->begin_transaction();

    $deletedCount = 0;

    // Prepared delete statements
    $delParticipants = $conn->prepare('DELETE FROM event_participants WHERE ep_event_id = ?');
    $delTags = $conn->prepare('DELETE FROM events_tags WHERE et_events_id = ?');
    $delSubjects = $conn->prepare('DELETE FROM event_subjects WHERE es_event_id = ?');
    $delEvent = $conn->prepare('DELETE FROM events WHERE events_id = ?');

    foreach ($toDelete as $eventId) {
        // Delete children first to satisfy FKs
        if ($delParticipants) { $delParticipants->bind_param('i', $eventId); $delParticipants->execute(); }
        if ($delTags) { $delTags->bind_param('i', $eventId); $delTags->execute(); }
        if ($delSubjects) { $delSubjects->bind_param('i', $eventId); $delSubjects->execute(); }
        if ($delEvent) {
            $delEvent->bind_param('i', $eventId);
            $delEvent->execute();
            $deletedCount += ($delEvent->affected_rows > 0) ? 1 : 0;
        }
    }

    // Close statements
    if ($delParticipants) $delParticipants->close();
    if ($delTags) $delTags->close();
    if ($delSubjects) $delSubjects->close();
    if ($delEvent) $delEvent->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Cleanup completed',
        'deleted' => $deletedCount,
        'checked' => count($toDelete),
        'days' => $days
    ]);

    $db->closeConnection();
    exit;
} catch (mysqli_sql_exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}
