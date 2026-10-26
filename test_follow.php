<?php
require_once 'includes/config.php';
echo "<h1>Test Follow System</h1>";

// Check session
session_start();
echo "<pre>Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "</pre>";

// Check if follows table exists
echo "<h2>Checking if follows table exists...</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'follows'");
    if ($stmt->fetch()) {
        echo "<p style='color:green;'>✅ Table 'follows' exists!</p>";
        
        // Get a test user
        $stmt_user = $pdo->query("SELECT id, fullname FROM users WHERE id != " . ($_SESSION['user_id'] ?? 0) . " LIMIT 1");
        $test_user = $stmt_user->fetch();
        if ($test_user) {
            echo "<p>Test user: " . $test_user['fullname'] . " (ID: " . $test_user['id'] . ")</p>";
            
            // Test toggle follow
            echo "<h3>Testing toggle follow...</h3>";
            echo "<form method='POST' action='api/toggle_follow.php'>
                <input type='hidden' name='user_id' value='".$test_user['id']."'>
                <button type='submit'>Toggle Follow</button>
            </form>";
        } else {
            echo "<p style='color:orange;'>⚠️ No other users in database</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Table 'follows' does NOT exist! Please run add_follows_table.php!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>