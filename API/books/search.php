<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get search parameters (supports both 'q' and 'keyword')
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : (isset($_GET['q']) ? $_GET['q'] : '');
$category = isset($_GET['category']) ? $_GET['category'] : '';
$author = isset($_GET['author']) ? $_GET['author'] : '';

// Build query
$query = "SELECT * FROM books WHERE 1=1";
$params = [];

if (!empty($keyword)) {
    $query .= " AND (title LIKE :keyword OR author LIKE :keyword2 OR isbn LIKE :keyword3 OR publisher LIKE :keyword4)";
    $keywordParam = "%$keyword%";
    $params[':keyword'] = $keywordParam;
    $params[':keyword2'] = $keywordParam;
    $params[':keyword3'] = $keywordParam;
    $params[':keyword4'] = $keywordParam;
}

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

if (!empty($author)) {
    $query .= " AND author LIKE :author";
    $params[':author'] = "%$author%";
}

$query .= " ORDER BY title ASC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($books),
        'search_params' => [
            'keyword' => $keyword,
            'category' => $category,
            'author' => $author
        ],
        'data' => $books
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>