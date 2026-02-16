<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST");

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
    $checkQuery = "SELECT book_id FROM books WHERE book_id = :book_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':book_id', $data->book_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
        exit;
    }
    
    // Build dynamic update query
    $updates = [];
    $params = [];
    
    if (isset($data->title)) {
        $updates[] = "title = :title";
        $params[':title'] = $data->title;
    }
    if (isset($data->author)) {
        $updates[] = "author = :author";
        $params[':author'] = $data->author;
    }
    if (isset($data->isbn)) {
        $updates[] = "isbn = :isbn";
        $params[':isbn'] = $data->isbn;
    }
    if (isset($data->publisher)) {
        $updates[] = "publisher = :publisher";
        $params[':publisher'] = $data->publisher;
    }
    if (isset($data->publication_year)) {
        $updates[] = "publication_year = :publication_year";
        $params[':publication_year'] = $data->publication_year;
    }
    if (isset($data->category)) {
        $updates[] = "category = :category";
        $params[':category'] = $data->category;
    }
    if (isset($data->total_copies)) {
        $updates[] = "total_copies = :total_copies";
        $params[':total_copies'] = $data->total_copies;
    }
    if (isset($data->available_copies)) {
        $updates[] = "available_copies = :available_copies";
        $params[':available_copies'] = $data->available_copies;
    }
    if (isset($data->shelf_location)) {
        $updates[] = "shelf_location = :shelf_location";
        $params[':shelf_location'] = $data->shelf_location;
    }
    if (isset($data->description)) {
        $updates[] = "description = :description";
        $params[':description'] = $data->description;
    }
    if (isset($data->cover_image_url)) {
        $updates[] = "cover_image_url = :cover_image_url";
        $params[':cover_image_url'] = $data->cover_image_url;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields to update'
        ]);
        exit;
    }
    
    // Add book_id to params
    $params[':book_id'] = $data->book_id;
    
    // Build and execute query
    $query = "UPDATE books SET " . implode(', ', $updates) . " WHERE book_id = :book_id";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'Book updated successfully',
            'book_id' => $data->book_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update book'
        ]);
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