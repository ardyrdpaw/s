<?php
// Migration: add sort_order column to inf_ti_items and populate ordered values per category
chdir(__DIR__ . '/..');
require_once 'php/db_connect.php';

echo "Applying migration: add sort_order if missing...\n";
$res = $conn->query("SHOW COLUMNS FROM inf_ti_items LIKE 'sort_order'");
if ($res && $res->num_rows === 0) {
    $ok = $conn->query("ALTER TABLE inf_ti_items ADD COLUMN sort_order INT DEFAULT 0 AFTER id");
    if ($ok) echo "Added column sort_order\n"; else echo "Failed to add column: " . $conn->error . "\n";
} else {
    echo "Column sort_order already exists\n";
}

$categories = ['komputer','printer','jaringan'];
foreach ($categories as $cat) {
    echo "Populating sort_order for category: $cat\n";
    $res = $conn->query("SELECT id FROM inf_ti_items WHERE category = '" . $conn->real_escape_string($cat) . "' ORDER BY id ASC");
    if (!$res) { echo "Query failed: " . $conn->error . "\n"; continue; }
    $i = 1;
    while ($r = $res->fetch_assoc()) {
        $stmt = $conn->prepare('UPDATE inf_ti_items SET sort_order = ? WHERE id = ?');
        $stmt->bind_param('ii', $i, $r['id']);
        $stmt->execute();
        $stmt->close();
        $i++;
    }
}

echo "Migration completed.\n";
