<?php
include __DIR__ . '/../php/db_connect.php';
// ensure column 'nip' exists
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'nip'");
if ($colRes && $colRes->num_rows === 0) {
    echo "'nip' column not present; nothing to rename.\n";
    exit(0);
}
// attempt to change column name
if ($conn->query("ALTER TABLE users CHANGE nip username VARCHAR(64) NOT NULL")) {
    echo "Renamed 'nip' to 'username' successfully.\n";
} else {
    echo "Failed to rename column: " . $conn->error . "\n";
    exit(1);
}
// update unique index name if exists
$idxRes = $conn->query("SHOW INDEX FROM users WHERE Key_name='uniq_users_nip'");
if ($idxRes && $idxRes->num_rows > 0) {
    $conn->query("ALTER TABLE users DROP INDEX uniq_users_nip");
    $conn->query("ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)");
    echo "Recreated unique index as uniq_users_username.\n";
} else {
    // ensure username has unique index
    $idxCheck = $conn->query("SHOW INDEX FROM users WHERE Column_name='username' AND Non_unique=0");
    if (!($idxCheck && $idxCheck->num_rows > 0)) {
        $conn->query("ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)");
        echo "Added UNIQUE index on username.\n";
    }
}
echo "Migration completed.\n";
?>