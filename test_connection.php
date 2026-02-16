<?php
// Correct path - no ../ needed since config is in the same parent directory
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
}
?>
