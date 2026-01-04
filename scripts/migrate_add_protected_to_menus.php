<?php
// Migration: add 'protected' column to menus if missing and set sensible defaults
include __DIR__ . '/../php/db_connect.php';

echo "Checking menus table for 'protected' column...\n";
$colRes = $conn->query("SHOW COLUMNS FROM menus LIKE 'protected'");
if ($colRes && $colRes->num_rows === 0) {
    echo "Adding 'protected' column...\n";
    if ($conn->query("ALTER TABLE menus ADD COLUMN `protected` TINYINT(1) DEFAULT 0")) {
        echo "Added 'protected' column successfully.\n";
        // Mark 'user' and 'menus' as protected by default if they exist
        $conn->query("UPDATE menus SET `protected` = 1 WHERE menu_key IN ('user','menus')");
        echo "Set default protected flags for known admin menus.\n";
    } else {
        echo "Failed to add 'protected' column: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "Column 'protected' already exists.\n";
}

echo "Migration completed.\n";
