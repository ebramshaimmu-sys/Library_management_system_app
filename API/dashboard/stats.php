<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Total books
    $booksQuery = "SELECT COUNT(*) as total, SUM(total_copies) as total_copies FROM books";
    $booksStmt = $db->query($booksQuery);
    $booksData = $booksStmt->fetch(PDO::FETCH_ASSOC);
    
    // Available books
    $availableQuery = "SELECT SUM(available_copies) as total FROM books";
    $availableStmt = $db->query($availableQuery);
    $availableBooks = $availableStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total users
    $usersQuery = "SELECT COUNT(*) as total FROM users";
    $usersStmt = $db->query($usersQuery);
    $totalUsers = $usersStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Students only
    $studentsQuery = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student'";
    $studentsStmt = $db->query($studentsQuery);
    $totalStudents = $studentsStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active issues
    $activeQuery = "SELECT COUNT(*) as total FROM book_issues WHERE status = 'issued'";
    $activeStmt = $db->query($activeQuery);
    $activeIssues = $activeStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Overdue books
    $overdueQuery = "SELECT COUNT(*) as total FROM book_issues WHERE status = 'issued' AND due_date < NOW()";
    $overdueStmt = $db->query($overdueQuery);
    $overdueBooks = $overdueStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total fines
    $finesQuery = "SELECT SUM(fine_amount) as total FROM book_issues WHERE fine_amount > 0";
    $finesStmt = $db->query($finesQuery);
    $totalFines = $finesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Recent activities
    $recentQuery = "SELECT bi.issue_id, bi.issue_date, bi.return_date, bi.status, 
                           b.title as book_title, u.full_name as user_name
                    FROM book_issues bi
                    JOIN books b ON bi.book_id = b.book_id
                    JOIN users u ON bi.user_id = u.user_id
                    ORDER BY bi.issue_date DESC
                    LIMIT 10";
    $recentStmt = $db->query($recentQuery);
    $recentActivities = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_books' => (int)$booksData['total'],
            'total_copies' => (int)$booksData['total_copies'],
            'available_copies' => (int)$availableBooks,
            'issued_copies' => (int)$booksData['total_copies'] - (int)$availableBooks,
            'total_users' => (int)$totalUsers,
            'total_students' => (int)$totalStudents,
            'active_issues' => (int)$activeIssues,
            'overdue_books' => (int)$overdueBooks,
            'total_fines' => (float)$totalFines,
            'recent_activities' => $recentActivities
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>