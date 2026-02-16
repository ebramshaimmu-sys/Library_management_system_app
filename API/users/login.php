<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

$username = $data->username;
$password = $data->password;

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Query to get user
$query = "SELECT user_id, username, password, full_name, user_type 
          FROM users 
          WHERE username = :username 
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Use password_verify() for hashed passwords
    if (password_verify($password, $row['password'])) {
        // Password is correct
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "user_id" => $row['user_id'],
                "username" => $row['username'],
                "full_name" => $row['full_name'],
                "user_type" => $row['user_type']
            ]
        ]);
    } else {
        // Password is wrong
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid password"
        ]);
    }
} else {
    // Username not found
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
}
?>