<?php
// Migration: add 'preferences' column to users if missing
include __DIR__ . '/../php/db_connect.php';

echo "Checking users table for 'preferences' column...\n";
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'preferences'");
if ($colRes && $colRes->num_rows === 0) {
    echo "Adding 'preferences' column...\n";
    if ($conn->query("ALTER TABLE users ADD COLUMN preferences TEXT DEFAULT NULL")) {
        echo "Added 'preferences' column successfully.\n";
    } else {
        echo "Failed to add 'preferences' column: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "Column 'preferences' already exists.\n";
}

echo "Migration completed.\n";
