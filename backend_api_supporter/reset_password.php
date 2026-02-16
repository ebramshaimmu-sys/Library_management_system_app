<?php
// Connect to database
$host = 'localhost';
$dbname = 'library_db';  // Change this to your database name
$username = 'root';       // Your MySQL username
$password = '';           // Your MySQL password (usually empty for XAMPP)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Generate new password hash
    $new_password = 'admin123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update admin user password
    $sql = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        echo "<h2>✅ Password Reset Successful!</h2>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>New Password: <strong>admin123</strong></p>";
        echo "<p>Password Hash: <code>$hashed_password</code></p>";
        echo "<hr>";
        echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file after use for security!</p>";
    } else {
        echo "❌ Failed to update password";
    }
    
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
