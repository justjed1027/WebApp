<?php
session_start();
require_once '../database/DatabaseConnection.php';

if (!isset($_SESSION['user_id'])) {
    // Not logged in — redirect to login or halt
    header('Location: ../login/login.html');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Collect POSTed fields (optional)
$firstName = isset($_POST['firstName']) && $_POST['firstName'] !== '' ? $_POST['firstName'] : null;
$lastName = isset($_POST['lastName']) && $_POST['lastName'] !== '' ? $_POST['lastName'] : null;
$phone = isset($_POST['phone']) && $_POST['phone'] !== '' ? $_POST['phone'] : null;
$bio = isset($_POST['bio']) && $_POST['bio'] !== '' ? $_POST['bio'] : null;

// Handle optional avatar upload. Store uploads the same way posts do so public paths resolve via the existing symlink.
$profileFilepath = null;
if (isset($_FILES['avatar']) && isset($_FILES['avatar']['error']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['avatar'];
    // Basic validation: max 10MB
    $maxBytes = 10 * 1024 * 1024;
    if ($f['size'] <= $maxBytes) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (in_array($mime, $allowed)) {
            // Use the same upload directory as posts so symlink/publicPath behavior matches
            $uploadDir = __DIR__ . '/../post/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $orig = basename($f['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
            $newName = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
            $target = $uploadDir . $newName;
            if (move_uploaded_file($f['tmp_name'], $target)) {
                // Store DB path using the same prefix posts use
                $profileFilepath = 'BPA/post/uploads/' . $newName;
            }
        }
    }
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Ensure a profile row exists for this user. Table name assumed to be `profile` and PK `profile_id`.
// Adjust table/column names here if your schema differs.
$selectSql = "SELECT profile_id FROM profile WHERE user_id = ? LIMIT 1";
$selectStmt = $conn->prepare($selectSql);
if ($selectStmt) {
    $selectStmt->bind_param('i', $user_id);
    $selectStmt->execute();
    $selectStmt->store_result();
    if ($selectStmt->num_rows > 0) {
        // profile exists — update any provided fields (leave others as-is)
        $selectStmt->bind_result($profile_id);
        $selectStmt->fetch();
        $selectStmt->close();

        $updates = [];
        $types = '';
        $values = [];

        if ($firstName !== null) { $updates[] = 'user_firstname = ?'; $types .= 's'; $values[] = $firstName; }
        if ($lastName !== null)  { $updates[] = 'user_lastname = ?';  $types .= 's'; $values[] = $lastName; }
        if ($phone !== null)     { $updates[] = 'phone = ?';          $types .= 's'; $values[] = $phone; }
        if ($bio !== null)       { $updates[] = 'profile_summary = ?'; $types .= 's'; $values[] = $bio; }
        if ($profileFilepath !== null) { $updates[] = 'profile_filepath = ?'; $types .= 's'; $values[] = $profileFilepath; }

        if (!empty($updates)) {
            $sql = 'UPDATE profile SET ' . implode(', ', $updates) . ' WHERE profile_id = ? AND user_id = ?';
            $types .= 'ii';
            $values[] = $profile_id;
            $values[] = $user_id;

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        // no profile — insert a new row. Insert NULLs for optional fields if not provided.
        $selectStmt->close();
        $insertSql = "INSERT INTO profile (user_id, user_firstname, user_lastname, profile_filepath, profile_summary, phone) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        if ($stmt) {
            $stmt->bind_param('isssss', $user_id, $firstName, $lastName, $profileFilepath, $bio, $phone);
            $stmt->execute();
            $stmt->close();
        }
    }
} else {
    // Could not prepare select — fail silently or log
}

$db->closeConnection();

// Continue to the next setup step
header('Location: page2.php');
exit;
?>
