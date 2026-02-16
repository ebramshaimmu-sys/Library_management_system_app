<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate input
    if (empty($data->title) || empty($data->author)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Title and Author are required'
        ]);
        exit;
    }
    
    // Insert query - matches your actual table structure
    $query = "INSERT INTO books 
              (title, author, isbn, publisher, publication_year, category, total_copies, available_copies, shelf_location, description, cover_image_url) 
              VALUES 
              (:title, :author, :isbn, :publisher, :publication_year, :category, :total_copies, :available_copies, :shelf_location, :description, :cover_image_url)";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':author', $data->author);
    
    $isbn = $data->isbn ?? null;
    $stmt->bindParam(':isbn', $isbn);
    
    $publisher = $data->publisher ?? null;
    $stmt->bindParam(':publisher', $publisher);
    
    $publication_year = $data->publication_year ?? null;
    $stmt->bindParam(':publication_year', $publication_year);
    
    $category = $data->category ?? null;
    $stmt->bindParam(':category', $category);
    
    $total_copies = $data->total_copies ?? 1;
    $stmt->bindParam(':total_copies', $total_copies);
    $stmt->bindParam(':available_copies', $total_copies);
    
    $shelf_location = $data->shelf_location ?? null;
    $stmt->bindParam(':shelf_location', $shelf_location);
    
    $description = $data->description ?? null;
    $stmt->bindParam(':description', $description);
    
    $cover_image_url = $data->cover_image_url ?? null;
    $stmt->bindParam(':cover_image_url', $cover_image_url);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Book created successfully',
            'book_id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create book'
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