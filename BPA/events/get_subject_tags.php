<?php
// get_subject_tags.php
// Returns JSON array of tags for a given subject_id
session_start();
require_once '../database/DatabaseConnection.php';
header('Content-Type: application/json; charset=utf-8');

$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
if ($subjectId <= 0) {
    echo json_encode(['success' => true, 'tags' => []]);
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;
// turn off mysqli exceptions for flexible probing of possible tag tables
mysqli_report(MYSQLI_REPORT_OFF);

$tags = [];

// Try several common table/column patterns to find tags related to a subject
$queries = [
    // Project-specific: tags table with subject linkage
    ['sql' => 'SELECT tag_id AS id, tag_name AS name FROM tags WHERE tag_subject_id = ?', 'types' => 'i'],
    ['sql' => 'SELECT tag_id AS id, tag_name AS name FROM tags WHERE tag_category_id = ?', 'types' => 'i'],
    // Common: tags table has subject_id
    ['sql' => 'SELECT tag_id AS id, tag_name AS name FROM tags WHERE subject_id = ?', 'types' => 'i'],
    // Common junction: subject_tags (subject_id, tag_id)
    ['sql' => 'SELECT t.tag_id AS id, t.tag_name AS name FROM tags t JOIN subject_tags st ON t.tag_id = st.tag_id WHERE st.subject_id = ?', 'types' => 'i'],
    // Alternate junction name tags_subjects
    ['sql' => 'SELECT t.tag_id AS id, t.tag_name AS name FROM tags t JOIN tags_subjects ts ON t.tag_id = ts.tag_id WHERE ts.subject_id = ?', 'types' => 'i'],
    // Alternate column name for tag text
    ['sql' => 'SELECT tag_id AS id, tag_text AS name FROM tags WHERE subject_id = ?', 'types' => 'i']
];

foreach ($queries as $q) {
    $stmt = $conn->prepare($q['sql']);
    if (!$stmt) continue;
    $stmt->bind_param($q['types'], $subjectId);
    try {
        if (!$stmt->execute()) {
            $stmt->close();
            continue;
        }
        $res = $stmt->get_result();
    } catch (\Exception $e) {
        // SQL error for this pattern; try next
        $stmt->close();
        continue;
    }
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $tags[] = ['id' => $row['id'], 'name' => $row['name']];
        }
        $stmt->close();
        break;
    }
    $stmt->close();
}
// If no tags found, try to discover potential tag-like tables/columns to help
$discovery = [];
if (empty($tags)) {
    $discSql = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND (COLUMN_NAME LIKE '%tag%' OR TABLE_NAME LIKE '%tag%') ORDER BY TABLE_NAME";
    $discRes = $conn->query($discSql);
    if ($discRes) {
        while ($r = $discRes->fetch_assoc()) {
            $discovery[] = ['table' => $r['TABLE_NAME'], 'column' => $r['COLUMN_NAME']];
        }
        $discRes->close();
    }
}

$db->closeConnection();

echo json_encode(['success' => true, 'tags' => $tags, 'discovery' => $discovery]);
exit;
