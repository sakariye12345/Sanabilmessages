<?php
$mysqli = new mysqli("localhost", "root", "", "demo");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$result = $mysqli->query("SHOW COLUMNS FROM tbl_allowed_parents");

while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$mysqli->close();
?>
