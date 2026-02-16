<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if specific book_id is requested
    $book_id = isset($_GET['book_id']) ? $_GET['book_id'] : null;
    
    if ($book_id) {
        // Single book
        $query = "SELECT * FROM books WHERE book_id = :book_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $book_id);
        $stmt->execute();
        
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            echo json_encode([
                'success' => true,
                'data' => $book
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Book not found'
            ]);
        }
    } else {
        // All books with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM books";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get books
        $query = "SELECT * FROM books ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $books,
            'pagination' => [
                'total_records' => $total_records,
                'current_page' => $page,
                'total_pages' => ceil($total_records / $per_page),
                'per_page' => $per_page
            ]
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
