<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if (empty($data->book_id) || empty($data->user_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Book ID and User ID are required'
        ]);
        exit;
    }
    
    // Check if book exists and has available copies
    $bookQuery = "SELECT book_id, title, available_copies FROM books WHERE book_id = :book_id";
    $bookStmt = $db->prepare($bookQuery);
    $bookStmt->bindParam(':book_id', $data->book_id);
    $bookStmt->execute();
    
    $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
        exit;
    }
    
    if ($book['available_copies'] <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No copies available for this book'
        ]);
        exit;
    }
    
    // Check if user exists
    $userQuery = "SELECT user_id, full_name FROM users WHERE user_id = :user_id";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':user_id', $data->user_id);
    $userStmt->execute();
    
    if ($userStmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Calculate due date (default 14 days from now)
    $days_to_return = isset($data->days) ? (int)$data->days : 14;
    $due_date = date('Y-m-d H:i:s', strtotime("+$days_to_return days"));
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Create issue record
        $issueQuery = "INSERT INTO book_issues (book_id, user_id, due_date, status, notes) 
                       VALUES (:book_id, :user_id, :due_date, 'issued', :notes)";
        $issueStmt = $db->prepare($issueQuery);
        
        $params = [
            ':book_id' => $data->book_id,
            ':user_id' => $data->user_id,
            ':due_date' => $due_date,
            ':notes' => isset($data->notes) ? $data->notes : null
        ];
        
        $issueStmt->execute($params);
        $issue_id = $db->lastInsertId();
        
        // Update available copies
        $updateQuery = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = :book_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':book_id', $data->book_id);
        $updateStmt->execute();
        
        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Book issued successfully',
            'issue_id' => $issue_id,
            'due_date' => $due_date,
            'book_title' => $book['title']
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>