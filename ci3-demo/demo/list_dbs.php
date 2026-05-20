<?php
define('BASEPATH', 'true');
define('ENVIRONMENT', 'development');
require_once('application/config/database.php');

$db_config = $db['default'];
$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Currently configured database in CI3: " . $db_config['database'] . "\n\n";

echo "All available databases:\n";
$result = $conn->query("SHOW DATABASES");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $db_name = $row['Database'];
        if (in_array($db_name, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
        
        echo "- " . $db_name;
        
        $conn->select_db($db_name);
        
        // Count users if exists
        $table_check = $conn->query("SHOW TABLES LIKE 'users'");
        if ($table_check && $table_check->num_rows > 0) {
            $user_count = $conn->query("SELECT count(*) as total FROM users");
            if ($user_count) {
                $count = $user_count->fetch_assoc()['total'];
                echo " (users: $count)";
            }
        }
        
        // Specific school demo check
        $allowed_parents = $conn->query("SHOW TABLES LIKE 'tbl_allowed_parents'");
        if ($allowed_parents && $allowed_parents->num_rows > 0) {
            echo " [MATCH: Has tbl_allowed_parents]";
        }
        
        echo "\n";
    }
} else {
    echo "Error listing databases: " . $conn->error . "\n";
}

$conn->close();
?>
