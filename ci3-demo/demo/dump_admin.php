<?php
define('BASEPATH', 'true');
define('ENVIRONMENT', 'development');
require_once('application/config/database.php');

$db_config = $db['default'];
$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Dumping admin user from database: " . $db_config['database'] . "\n";
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result && $result->num_rows > 0) {
    print_r($result->fetch_assoc());
} else {
    echo "Admin user not found or error: " . $conn->error . "\n";
}

$conn->close();
?>
