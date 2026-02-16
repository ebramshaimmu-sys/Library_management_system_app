<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, POST");

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->book_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Book ID is required'
        ]);
        exit;
    }
    
    // Check if book exists
    $checkQuery = "SELECT book_id, title FROM books WHERE book_id = :book_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':book_id', $data->book_id);
    $checkStmt->execute();
    
    $book = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
        exit;
    }
    
    // Optional: Check if book has active issues
    $issueCheckQuery = "SELECT COUNT(*) as count FROM book_issues 
                        WHERE book_id = :book_id AND return_date IS NULL";
    $issueStmt = $db->prepare($issueCheckQuery);
    $issueStmt->bindParam(':book_id', $data->book_id);
    $issueStmt->execute();
    $issueCount = $issueStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($issueCount > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete book with active issues',
            'active_issues' => (int)$issueCount
        ]);
        exit;
    }
    
    // Delete the book
    $deleteQuery = "DELETE FROM books WHERE book_id = :book_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':book_id', $data->book_id);
    
    if ($deleteStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Book deleted successfully',
            'deleted_book' => $book['title']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete book'
        ]);
    }
    
} catch (PDOException $e) {
    // If book_issues table doesn't exist, ignore that check
    if (strpos($e->getMessage(), "book_issues") !== false) {
        // Retry without issue check
        try {
            $deleteQuery = "DELETE FROM books WHERE book_id = :book_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':book_id', $data->book_id);
            
            if ($deleteStmt->execute() && $deleteStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Book deleted successfully'
                ]);
            }
        } catch (Exception $e2) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e2->getMessage()
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>