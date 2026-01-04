<?php
// Migration: add 'visible' column to user_access if missing
include __DIR__ . '/../php/db_connect.php';

echo "Checking user_access table for 'visible' column...\n";
$colRes = $conn->query("SHOW COLUMNS FROM user_access LIKE 'visible'");
if ($colRes && $colRes->num_rows === 0) {
    echo "Adding 'visible' column...\n";
    if ($conn->query("ALTER TABLE user_access ADD COLUMN visible TINYINT(1) DEFAULT 0")) {
        echo "Added 'visible' column successfully.\n";
    } else {
        echo "Failed to add 'visible' column: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "Column 'visible' already exists.\n";
}

echo "Migration completed.\n";
