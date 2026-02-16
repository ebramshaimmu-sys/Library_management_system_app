<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Get all active issues
$query = "SELECT bi.issue_id, bi.issue_date, bi.due_date, bi.status,
                 u.username, u.full_name, u.email,
                 b.title, b.author, b.isbn
          FROM book_issues bi
          JOIN users u ON bi.user_id = u.user_id
          JOIN books b ON bi.book_id = b.book_id
          WHERE bi.status = 'issued'
          ORDER BY bi.due_date ASC";

$stmt = $db->prepare($query);
$stmt->execute();

$active_issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $active_issues,
    "total_active" => count($active_issues)
]);
?>