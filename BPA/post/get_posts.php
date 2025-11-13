<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

// Helper: normalize a DB file path into a web-visible path
function publicPath($dbPath)
{
  if (empty($dbPath)) return '';
  if (strpos($dbPath, 'BPA/post/uploads/') === 0) {
    return './uploads/' . basename($dbPath);
  }
  if (strpos($dbPath, 'uploads/') === 0) {
    return './' . $dbPath;
  }
  if (strpos($dbPath, '/') === 0) {
    return $dbPath;
  }
  return $dbPath;
}

// Helper: time ago function
function timeAgo($timestamp)
{
  $datetime = new DateTime($timestamp);
  $now = new DateTime();
  $interval = $now->diff($datetime);

  if ($interval->y > 0) {
    return $interval->y . " year" . ($interval->y > 1 ? "s" : "");
  }
  if ($interval->m > 0) {
    return $interval->m . " month" . ($interval->m > 1 ? "s" : "");
  }
  if ($interval->d > 0) {
    return $interval->d . " day" . ($interval->d > 1 ? "s" : "");
  }
  if ($interval->h > 0) {
    return $interval->h . " hour" . ($interval->h > 1 ? "s" : "");
  }
  if ($interval->i > 0) {
    return $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
  }
  return "just now";
}

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

// Get offset and limit from request
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$limit = isset($_GET['limit']) ? min(20, max(1, intval($_GET['limit']))) : 10; // default 10, max 20

$db = new DatabaseConnection();
$conn = $db->connection;

// Query posts with pagination
$sql = "SELECT posts.post_id, posts.user_id, posts.content, posts.created_at, posts.file_path, COALESCE(user.user_username, '') AS user_username 
  FROM posts 
  LEFT JOIN user ON posts.user_id = user.user_id
  ORDER BY posts.created_at DESC
  LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . htmlspecialchars($conn->error)]);
  exit;
}

$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
  // Build post object
  $displayName = !empty($row['user_username']) ? $row['user_username'] : ('User #' . intval($row['user_id']));
  $initial = $displayName ? mb_strtoupper(mb_substr($displayName, 0, 1)) : 'U';
  
  $posts[] = [
    'post_id' => intval($row['post_id']),
    'user_id' => intval($row['user_id']),
    'username' => $displayName,
    'initial' => $initial,
    'content' => $row['content'] ?? '',
    'file_path' => !empty($row['file_path']) ? publicPath($row['file_path']) : '',
    'created_at' => $row['created_at'] ?? '',
    'time_ago' => $row['created_at'] ? timeAgo($row['created_at']) : 'just now'
  ];
}

$stmt->close();

// Also get total count for pagination info (optional)
$countSql = "SELECT COUNT(*) as total FROM posts";
$countResult = $conn->query($countSql);
$countRow = $countResult ? $countResult->fetch_assoc() : ['total' => 0];
$total = intval($countRow['total'] ?? 0);

echo json_encode([
  'success' => true,
  'posts' => $posts,
  'offset' => $offset,
  'limit' => $limit,
  'total' => $total,
  'has_more' => ($offset + $limit) < $total
]);
