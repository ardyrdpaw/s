<?php
include __DIR__ . '/../php/db_connect.php';
// fill empty/NULL usernames with auto-generated values to avoid duplicate empty strings
$conn->query("UPDATE users SET username = CONCAT('auto_username_', id) WHERE username IS NULL OR TRIM(username) = ''");
// check for duplicates
$res = $conn->query("SELECT username, COUNT(*) as c FROM users GROUP BY username HAVING COUNT(*) > 1");
$dups = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $dups[] = $r;
    }
}
if (!empty($dups)) {
    echo "Found duplicate username values. Please resolve duplicates before adding unique constraint:\n";
    foreach ($dups as $d) {
        echo " - " . $d['username'] . " (" . $d['c'] . " occurrences)\n";
    }
    exit(1);
}
// safe to add unique index
$idxName = 'uniq_users_username';
$hasIdx = $conn->query("SHOW INDEX FROM users WHERE Key_name='" . $conn->real_escape_string($idxName) . "'");
if ($hasIdx && $hasIdx->num_rows > 0) {
    echo "Unique index already exists.\n"; exit(0);
}
if ($conn->query("ALTER TABLE users ADD UNIQUE KEY " . $idxName . " (username)")) {
    echo "Added UNIQUE index on users(username) successfully.\n";
} else {
    echo "Failed to add UNIQUE index: " . $conn->error . "\n";
    exit(1);
}
?>