<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Get user_id
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "User ID is required"
    ]);
    exit();
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Get user's issue history
$query = "SELECT bi.issue_id, bi.issue_date, bi.due_date, bi.return_date, bi.status,
                 b.title, b.author, b.isbn
          FROM book_issues bi
          JOIN books b ON bi.book_id = b.book_id
          WHERE bi.user_id = :user_id
          ORDER BY bi.issue_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $history,
    "total_records" => count($history)
]);
?>