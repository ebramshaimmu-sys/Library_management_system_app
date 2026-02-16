<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get raw input
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    // Debug: Log what was received (temporary)
    error_log("Received data: " . print_r($data, true));
    
    // Validate required fields
    if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->full_name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'received' => [
                'username' => isset($data->username) ? 'present' : 'missing',
                'email' => isset($data->email) ? 'present' : 'missing',
                'password' => isset($data->password) ? 'present' : 'missing',
                'full_name' => isset($data->full_name) ? 'present' : 'missing'
            ]
        ]);
        exit;
    }
    
    // Check for duplicate email
    $checkQuery = "SELECT user_id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $data->email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit;
    }
    
    // Check for duplicate username
    $checkQuery = "SELECT user_id FROM users WHERE username = :username";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':username', $data->username);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists'
        ]);
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
    
    $query = "INSERT INTO users (username, email, password, full_name, phone, user_type) 
              VALUES (:username, :email, :password, :full_name, :phone, :user_type)";
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':username' => $data->username,
        ':email' => $data->email,
        ':password' => $password_hash,
        ':full_name' => $data->full_name,
        ':phone' => $data->phone ?? null,
        ':user_type' => $data->user_type ?? 'student'
    ];
    
    if ($stmt->execute($params)) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create user'
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