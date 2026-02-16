<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all tables
    $query = "SHOW TABLES";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tableStructures = [];
    
    foreach ($tables as $table) {
        $descQuery = "DESCRIBE $table";
        $descStmt = $db->query($descQuery);
        $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tableStructures[$table] = $columns;
    }
    
    echo json_encode([
        'success' => true,
        'database' => 'library_db',
        'tables' => $tables,
        'structures' => $tableStructures
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>