<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->issue_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Issue ID is required'
        ]);
        exit;
    }
    
    // Get issue details
    $issueQuery = "SELECT bi.*, b.title 
                   FROM book_issues bi 
                   JOIN books b ON bi.book_id = b.book_id 
                   WHERE bi.issue_id = :issue_id";
    $issueStmt = $db->prepare($issueQuery);
    $issueStmt->bindParam(':issue_id', $data->issue_id);
    $issueStmt->execute();
    
    $issue = $issueStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$issue) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Issue record not found'
        ]);
        exit;
    }
    
    // Check if already returned
    if ($issue['return_date'] !== null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Book already returned',
            'returned_on' => $issue['return_date']
        ]);
        exit;
    }
    
    // Calculate fine if overdue
    $return_date = date('Y-m-d H:i:s');
    $due_date = new DateTime($issue['due_date']);
    $return_datetime = new DateTime($return_date);
    $fine_amount = 0;
    
    if ($return_datetime > $due_date) {
        $days_overdue = $return_datetime->diff($due_date)->days;
        $fine_per_day = 5; // $5 per day
        $fine_amount = $days_overdue * $fine_per_day;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update issue record
        $updateIssueQuery = "UPDATE book_issues 
                            SET return_date = :return_date, 
                                status = 'returned', 
                                fine_amount = :fine_amount 
                            WHERE issue_id = :issue_id";
        $updateIssueStmt = $db->prepare($updateIssueQuery);
        $params = [
            ':return_date' => $return_date,
            ':fine_amount' => $fine_amount,
            ':issue_id' => $data->issue_id
        ];
        $updateIssueStmt->execute($params);
        
        // Update available copies
        $updateBookQuery = "UPDATE books 
                           SET available_copies = available_copies + 1 
                           WHERE book_id = :book_id";
        $updateBookStmt = $db->prepare($updateBookQuery);
        $updateBookStmt->bindParam(':book_id', $issue['book_id']);
        $updateBookStmt->execute();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book returned successfully',
            'book_title' => $issue['title'],
            'return_date' => $return_date,
            'fine_amount' => $fine_amount,
            'is_overdue' => $fine_amount > 0
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