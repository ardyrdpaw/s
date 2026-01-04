<?php
include __DIR__ . '/../php/db_connect.php';
// check if column exists
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if (!$colRes || $colRes->num_rows === 0) {
    echo "'email' column not present; nothing to do.\n";
    exit(0);
}
// ensure no code references remain (best-effort check is out-of-band). Proceed to drop column
if ($conn->query("ALTER TABLE users DROP COLUMN email")) {
    echo "Dropped 'email' column successfully.\n";
} else {
    echo "Failed to drop 'email' column: " . $conn->error . "\n";
    exit(1);
}
?>