<?php
/**
 * Create a review for a session participant
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'db.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$sessionId = isset($data['session_id']) ? intval($data['session_id']) : 0;
$revieweeId = isset($data['reviewee_id']) ? intval($data['reviewee_id']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$title = isset($data['title']) ? trim((string)$data['title']) : '';
$text = isset($data['text']) ? trim((string)$data['text']) : '';
$reviewerId = intval($_SESSION['user_id']);

if ($sessionId <= 0 || $revieweeId <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']));
}

if ($text === '' || strlen($text) < 3) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Review text is required']));
}

if (strlen($text) > 2000 || strlen($title) > 255) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Review is too long']));
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();

    // Create table if not exists
    $createSql = "CREATE TABLE IF NOT EXISTS session_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        rating TINYINT NOT NULL,
        title VARCHAR(255) NULL,
        review_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_review (session_id, reviewer_id),
        INDEX idx_reviewee (reviewee_id),
        INDEX idx_session (session_id)
    )";
    $conn->query($createSql);

    // Verify user is part of session and determine reviewee
    $verifySql = "SELECT request_id, requester_id, recipient_id, status
                  FROM session_requests
                  WHERE request_id = ? AND (requester_id = ? OR recipient_id = ?)
                  LIMIT 1";
    $verifyStmt = $conn->prepare($verifySql);
    if (!$verifyStmt) {
        throw new Exception('Verify prepare failed: ' . $conn->error);
    }

    $verifyStmt->bind_param('iii', $sessionId, $reviewerId, $reviewerId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();

    if ($verifyResult->num_rows === 0) {
        http_response_code(403);
        $verifyStmt->close();
        exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }

    $sessionRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    if (!in_array($sessionRow['status'], ['accepted', 'cancelled'], true)) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'Session not eligible for review']));
    }

    $expectedReviewee = ($reviewerId == intval($sessionRow['requester_id']))
        ? intval($sessionRow['recipient_id'])
        : intval($sessionRow['requester_id']);

    if ($revieweeId !== $expectedReviewee) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'Invalid review target']));
    }

    if ($revieweeId === $reviewerId) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'Cannot review yourself']));
    }

    // Check duplicate
    $dupSql = "SELECT review_id FROM session_reviews WHERE session_id = ? AND reviewer_id = ? LIMIT 1";
    $dupStmt = $conn->prepare($dupSql);
    if (!$dupStmt) {
        throw new Exception('Duplicate check prepare failed: ' . $conn->error);
    }
    $dupStmt->bind_param('ii', $sessionId, $reviewerId);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    if ($dupResult->num_rows > 0) {
        http_response_code(409);
        $dupStmt->close();
        exit(json_encode(['success' => false, 'error' => 'You already reviewed this session']));
    }
    $dupStmt->close();

    // Insert review
    $insertSql = "INSERT INTO session_reviews (session_id, reviewer_id, reviewee_id, rating, title, review_text)
                  VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        throw new Exception('Insert prepare failed: ' . $conn->error);
    }

    $insertStmt->bind_param('iiiiss', $sessionId, $reviewerId, $revieweeId, $rating, $title, $text);
    if (!$insertStmt->execute()) {
        throw new Exception('Insert execute failed: ' . $insertStmt->error);
    }

    $reviewId = $insertStmt->insert_id;
    $insertStmt->close();

    exit(json_encode([
        'success' => true,
        'review_id' => $reviewId
    ]));

} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
