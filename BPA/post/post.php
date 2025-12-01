<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

// Function to convert timestamp to "time ago" format
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

// Normalize a DB file path into a web-visible path relative to this script.
function publicPath($dbPath)
{
  if (empty($dbPath)) return '';
  // If the path was stored like "BPA/post/uploads/filename.ext" convert to ./uploads/filename.ext
  if (strpos($dbPath, 'BPA/post/uploads/') === 0) {
    return './uploads/' . basename($dbPath);
  }
  // If path already starts with uploads/ (relative) make it explicit relative
  if (strpos($dbPath, 'uploads/') === 0) {
    return './' . $dbPath;
  }
  // If it's an absolute web path (starts with /) return as-is
  if (strpos($dbPath, '/') === 0) {
    return $dbPath;
  }
  // Otherwise return as-is (fallback)
  return $dbPath;
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Handle inline post submission (text-only)
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
  if (empty($_SESSION['user_id'])) {
    header('Location: ../landing/landing.php');
    exit;
  }

  $user_id = $_SESSION['user_id'];
  $content = isset($_POST['content']) ? trim($_POST['content']) : '';

  if ($content !== '') {
    // Detect columns we can write to (created_at, file_path)
    $hasCreatedAt = false;
    $hasFilePath = false;
    $colRes = $conn->query("SHOW COLUMNS FROM posts LIKE 'created_at'");
    if ($colRes && $colRes->num_rows > 0) { $hasCreatedAt = true; $colRes->free(); }
    $colRes2 = $conn->query("SHOW COLUMNS FROM posts LIKE 'file_path'");
    if ($colRes2 && $colRes2->num_rows > 0) { $hasFilePath = true; $colRes2->free(); }

    // Prepare to handle an uploaded file (optional)
    $fileSaved = false;
    $dbFilePath = null; // path to store in DB if available
    if (isset($_FILES['avatar']) && isset($_FILES['avatar']['error']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
      $f = $_FILES['avatar'];
      // Basic validation: max 10MB
      $maxBytes = 10 * 1024 * 1024;
      if ($f['size'] > $maxBytes) {
        $error = 'File is too large. Max 10 MB.';
      } else {
        // Determine mime type using finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        $allowed = [
          'image/jpeg','image/png','image/gif','image/webp',
          'video/mp4','video/webm','video/ogg',
          'application/pdf','text/plain',
          'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        if (!in_array($mime, $allowed)) {
          $error = 'Unsupported file type for upload.';
        } else {
          // Ensure uploads directory exists
          $uploadDir = __DIR__ . '/uploads/';
          if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
          // Sanitize original name and create unique filename
          $orig = basename($f['name']);
          $ext = pathinfo($orig, PATHINFO_EXTENSION);
          $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
          $newName = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
          $target = $uploadDir . $newName;
          if (move_uploaded_file($f['tmp_name'], $target)) {
            $fileSaved = true;
            // Store a web-relative path (from project root). Adjust if your routing differs.
            $dbFilePath = 'BPA/post/uploads/' . $newName;
          } else {
            $error = 'Failed to move uploaded file.';
          }
        }
      }
    }

    // If there was an upload error, don't attempt DB insert
    if (isset($error) && $error !== '') {
      // fall through to rendering page and showing error
    } else {
      // Build INSERT depending on available columns
      if ($hasCreatedAt && $hasFilePath) {
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, file_path, created_at) VALUES (?, ?, ?, ?)");
        if ($stmt) { $stmt->bind_param("isss", $user_id, $content, $dbFilePath, $now); }
      } elseif ($hasCreatedAt && !$hasFilePath) {
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
        if ($stmt) { $stmt->bind_param("iss", $user_id, $content, $now); }
      } elseif (!$hasCreatedAt && $hasFilePath) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, file_path) VALUES (?, ?, ?)");
        if ($stmt) { $stmt->bind_param("iss", $user_id, $content, $dbFilePath); }
      } else {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        if ($stmt) { $stmt->bind_param("is", $user_id, $content); }
      }

      if (isset($stmt) && $stmt) {
        if ($stmt->execute()) {
          $stmt->close();
          header('Location: post.php');
          exit;
        } else {
          $error = 'Error saving post: ' . htmlspecialchars($stmt->error);
          $stmt->close();
        }
      } else {
        $error = 'Database error preparing statement.';
      }
    }
  } else {
    $error = 'Post content cannot be empty.';
  }
}

 $sql = "SELECT posts.post_id, posts.user_id, posts.content, posts.created_at, posts.file_path, COALESCE(user.user_username, '') AS user_username 
  FROM posts 
  LEFT JOIN user ON posts.user_id = user.user_id
  ORDER BY posts.created_at DESC";
$result = $conn->query($sql);

// Debug helper: append ?debug=1 to URL to see query status
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
  if ($result === false) {
    echo '<pre>SQL Error: ' . htmlspecialchars($conn->error) . '</pre>';
  } else {
    echo '<pre>SQL OK — rows: ' . intval($result->num_rows) . "\n";
    // show first row sample
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      echo 'First row: ' . htmlspecialchars(print_r($row, true));
      // rewind result pointer
      $result->data_seek(0);
    }
    echo '</pre>';
  }
}

$posts = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
  }
}

$user = new User();

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);
} else {
  header('location: ../landing/landing.php');
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {

  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {

    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
  }
}
/*
side bar svgs
no app logo yet so just put in a black box or smth as a place holder
user profile svg (this will be used if a user doesn't have a profile picture)
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
</svg>

home svg (place holder 1 for the post page)
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
  <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5"/>
</svg>

dms svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots-fill" viewBox="0 0 16 16">
  <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
</svg>

forum svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-text-fill" viewBox="0 0 16 16">
  <path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M5 4h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m0 2h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1"/>
</svg>

conection svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
  <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
</svg>

courses svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
  <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
</svg>

calendar svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
</svg>

events svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
  <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
</svg>

settings svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
  <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
</svg>

log out svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
  <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
</svg>
theme toggle
    sun svg
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-brightness-high" viewBox="0 0 16 16">
      <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
    </svg>
    moon svg
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-moon" viewBox="0 0 16 16">
      <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286"/>
    </svg>
(create a switch with moon reprenting dark mode on one side and the sun represneting light mode on the other)

top bar
search svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
</svg>

notifications svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/>
</svg>

message svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
  <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
  <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2"/>
</svg>

profile svg
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
</svg>
*/


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap — Posts</title>
  <link rel="stylesheet" href="style.css?v=nav-20251022">
  <style>
    
    .create-post-input {
      display: block;
      width: 360px;
      max-width: 100%;
      min-height: 72px;
      height: auto; 
      padding: 12px 14px;
      border-radius: 12px;
      background: rgba(255,255,255,0.03);
      color: inherit;
      border: 1px solid rgba(255,255,255,0.06);
      resize: none; 
      overflow: hidden; 
      font-family: inherit;
      font-size: 1.3rem;
      line-height: 1.3;
      field-sizing: content;    
    }

   
    .create-post-actions { margin-top: 8px; }
  </style>
  <style>
    /* Modal for file preview */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .modal-backdrop.active { display:flex; }
    .modal-box {
      background: #111;
      color: #fff;
      padding: 18px;
      border-radius: 10px;
      max-width: 90%;
      max-height: 85%;
      overflow: auto;
      box-shadow: 0 8px 30px rgba(0,0,0,0.6);
    }
    .modal-box img, .modal-box video {
      max-width: 100%;
      max-height: 70vh;
      display:block;
      margin: 0 auto;
    }
    .modal-close {
      display:inline-block;
      margin-top:8px;
      padding:6px 10px;
      background:#333;
      color:#fff;
      border-radius:6px;
      cursor:pointer;
    }
  </style>
</head>

<body>

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <!-- Top Section: Logo & Profile -->
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <div class="logo-placeholder"><img class=".logo-placeholder" src="../images/skillswaplogotrans.png"></div>
        <span class="logo-text">SkillSwap</span>
      </div>

      <div class="sidebar-profile">
        <div class="profile-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </div>
        <div class="profile-info">
          <h3 class="profile-name"><?php echo htmlspecialchars($user->user_username); ?></h3>
          <p class="profile-email"><?php echo htmlspecialchars($user->user_email); ?></p>
          <a href="../profile/profile.php" class="view-profile-link">View Profile</a>
        </div>
      </div>
    </div>

    <!-- Middle Section: Main Navigation -->
    <div class="sidebar-middle">
      <div class="nav-group">
        <a href="post.php" class="nav-link active" data-tooltip="Posts">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
          </svg>
          <span>Posts</span>
        </a>

        <a href="../dms/dms.php" class="nav-link" data-tooltip="Direct Messages">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          </svg>
          <span>DMs</span>
        </a>

        <a href="../forum/forums.html" class="nav-link" data-tooltip="Forum">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M5 4h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m0 2h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1" />
          </svg>
          <span>Forum</span>
        </a>

        <a href="../connections/Connections.php" class="nav-link" data-tooltip="Connections">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
          </svg>
          <span>Connections</span>
        </a>
      </div>

      <div class="nav-divider"></div>

      <div class="nav-group">
        <a href="../courses/courses.html" class="nav-link" data-tooltip="Courses">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783" />
          </svg>
          <span>Courses</span>
        </a>

        <a href="../calendar/calendar.php" class="nav-link" data-tooltip="Calendar">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
          </svg>
          <span>Calendar</span>
        </a>

        <a href="../events/events.php" class="nav-link" data-tooltip="Events">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z" />
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
          </svg>
          <span>Events</span>
        </a>
      </div>
    </div>

    <!-- Bottom Section: Utilities -->
    <div class="sidebar-bottom">
      <div class="nav-divider"></div>

      <a href="..//settings/settings.php" class="nav-link" data-tooltip="Settings">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Settings</span>
      </a>

      <a href="post.php?action=logout" class="nav-link" data-tooltip="Log Out">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
        </svg>
        <span>Log Out</span>
      </a>
      
      <form action="delete_oldest_post.php" method="POST" style="display:inline;">
        <button type="submit" style="margin-left:16px;">Delete Oldest Post</button>
      </form>

      <div class="theme-toggle">
        <button class="theme-toggle-btn" id="themeToggle">
          <div class="toggle-switch">
            <div class="toggle-slider">
              <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708" />
              </svg>
              <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286" />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>
  </aside>

  <!-- File preview modal -->
  <div id="filePreviewModal" class="modal-backdrop" role="dialog" aria-hidden="true">
    <div class="modal-box" id="filePreviewContent">
      <div id="filePreviewInner"></div>
      <div style="text-align:center;"><button id="modalCloseBtn" class="modal-close">Close</button></div>
    </div>
  </div>

  <!-- Top Bar Navigation -->
  <header class="topbar">
    <div class="topbar-left"></div>
    <div class="topbar-center">
      <h1 class="page-title">Posts</h1>
    </div>
    <div class="topbar-right">
      <div class="search-container">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
        </svg>
        <input type="text" class="search-input" placeholder="Search people, posts, and courses...">
      </div>

      <button class="icon-btn" aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z" />
        </svg>
        <span class="badge">3</span>
      </button>

      <button class="icon-btn" aria-label="Messages">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2" />
        </svg>
        <span class="badge">7</span>
      </button>

      <div class="profile-dropdown">
        <button class="profile-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </button>
      </div>
    </div>
  </header>

  <!-- Main Content Area -->
  <main class="main-content">
    <!-- Create Post (inline form) -->
    <div class="create-post-card">
      <div class="create-post-header">
        <div class="user-avatar-small">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </div>
            <form id="inline-post-form" action="post.php" method="POST" enctype="multipart/form-data">
              <textarea name="content" class="create-post-input" placeholder="Ask a question or share something helpful..." rows="3"></textarea>
              <div class="file-row" style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                <input type="file" id="avatar" name="avatar" accept="image/*" style="display:inline-block;">
                <span id="fileLabel" style="color:#ddd;">Pick a file to upload</span>
                <button type="button" id="filePreviewBtn" class="create-post-btn" style="padding:6px 10px;">Preview</button>
                <button type="button" id="fileRemoveBtn" class="create-post-btn" style="padding:6px 10px; background:#551A8B;">Remove</button>
              </div>
      </div>
          <div class="create-post-actions">
            <button type="submit" class="create-post-btn">Submit</button>
            </form>
          </div>
    </div>

    <!-- Posts Feed -->
    <div id="posts-container">
      <?php if (count($posts) === 0): ?>
          <div class="post" style="background:#fff3cd;border:1px solid #ffeebaff;padding:16px;margin-bottom:16px;color:black;">No posts yet.</div>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <div class="post" style="background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
              <div class="post-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <?php $displayName = !empty($post['user_username']) ? $post['user_username'] : ('User #' . intval($post['user_id'])); ?>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="post-author-avatar" style="width:40px;height:40px;border-radius:50%;background:#e9ecef;color:#333;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:1rem;">
                    <?php
                      $initial = '';
                      if (!empty($post['user_username'])) {
                        $initial = mb_strtoupper(mb_substr($post['user_username'], 0, 1));
                      } else {
                        $initial = 'U';
                      }
                      echo htmlspecialchars($initial);
                    ?>
                  </div>
                  <div style="display:flex;flex-direction:column;">
                    <div style="font-weight:600;color:#111"><?php echo htmlspecialchars($displayName); ?></div>
                    <div style="font-size:0.85rem;color:#666"><?php echo isset($post['created_at']) ? htmlspecialchars(timeAgo($post['created_at'])) : 'just now'; ?></div>
                  </div>
                </div>

                <?php
                  $payload = [
                    'content' => $post['content'] ?? '',
                    // normalize file path so client-side modal can load it correctly
                    'file_path' => !empty($post['file_path']) ? publicPath($post['file_path']) : '',
                    'username' => $displayName,
                    'created_at' => $post['created_at'] ?? ''
                  ];
                  $payloadAttr = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');
                ?>

                <div>
                  <button type="button" class="view-post-btn create-post-btn" data-post="<?php echo $payloadAttr; ?>" style="padding:6px 10px;">View</button>
                </div>
              </div>
              <div class="post-content" style="color:#333;line-height:1.5;">
                <p style="margin:0;"><?php echo nl2br(htmlspecialchars(mb_strlen($post['content']) > 400 ? mb_substr($post['content'],0,400) . '...' : $post['content'])); ?></p>
                  <?php if (!empty($post['file_path'])):
                    // normalize path for browser
                    $publicPath = publicPath($post['file_path']);
                  ?>
                  <div style="margin-top:8px;">
                    <?php $ext = strtolower(pathinfo($post['file_path'], PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                      <img src="<?php echo htmlspecialchars($publicPath); ?>" alt="attachment" style="max-width:200px;border-radius:8px;display:block;margin-top:8px;" />
                    <?php else: ?>
                      <div style="margin-top:8px;color:#555;font-size:0.9rem;">Attachment: <a href="<?php echo htmlspecialchars($publicPath); ?>" target="_blank">Open</a></div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- Post Detail Modal -->
    <div id="postDetailModal" class="modal-backdrop" role="dialog" aria-hidden="true">
      <div class="modal-box" id="postDetailBox">
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
          <div id="postDetailAvatar" style="width:48px;height:48px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-weight:600;color:#333;font-size:1.1rem;"></div>
          <div>
            <div id="postDetailUser" style="font-weight:700;color:#fff"></div>
            <div id="postDetailTime" style="font-size:0.85rem;color:#bbb"></div>
          </div>
        </div>
        <div id="postDetailContent" style="color:#fff;line-height:1.5;margin-bottom:12px;"></div>
        <div id="postDetailMedia" style="text-align:center;">
        </div>
        <div style="text-align:center;margin-top:12px;"><button id="postDetailClose" class="modal-close">Close</button></div>
      </div>
    </div>

  </main>


</body>

    <script src="script.js?v=20251103"></script>
  </body>

  </html>