<?php
include __DIR__ . '/../php/db_connect.php';
// ensure username column exists; if only 'nip' exists, rename it
$resUsername = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
$resNip = $conn->query("SHOW COLUMNS FROM users LIKE 'nip'");
if ($resUsername && $resUsername->num_rows > 0) {
    echo "'username' column already exists.\n";
} elseif ($resNip && $resNip->num_rows > 0) {
    // rename nip -> username
    $conn->query("ALTER TABLE users CHANGE nip username VARCHAR(64) NOT NULL");
    echo "Renamed 'nip' to 'username'.\n";
} else {
    $conn->query("ALTER TABLE users ADD COLUMN username VARCHAR(64) DEFAULT NULL AFTER name");
    echo "Added 'username' column successfully.\n";
}
$res2 = $conn->query("SHOW COLUMNS FROM users LIKE 'keterangan'");
if ($res2 && $res2->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN keterangan TEXT DEFAULT NULL AFTER role");
    echo "Added 'keterangan' column successfully.\n";
} else {
    echo "'keterangan' column already exists.\n";
}
echo "Migration completed.\n";
?>