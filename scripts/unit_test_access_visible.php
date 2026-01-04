<?php
// Simple CLI unit test for visible column / behavior
include __DIR__ . '/../php/db_connect.php';

$testUser = 999999;
$menuKey = 'test_menu_visible';

function fail($msg){ echo "[FAIL] $msg\n"; exit(1); }
function pass($msg){ echo "[PASS] $msg\n"; }

// Check column exists
$colRes = $conn->query("SHOW COLUMNS FROM user_access LIKE 'visible'");
if (!$colRes || $colRes->num_rows === 0) {
    fail("'visible' column does not exist in user_access");
} else {
    pass("'visible' column exists");
}

// Ensure test menu exists
$res = $conn->query("SELECT COUNT(*) as c FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
$r = $res->fetch_assoc();
if (intval($r['c']) === 0) {
    $maxRes = $conn->query("SELECT MAX(sort_order) as m FROM menus");
    $maxRow = $maxRes ? $maxRes->fetch_assoc() : null;
    $next = intval($maxRow['m'] ?? 0) + 1;
    $stmt = $conn->prepare("INSERT INTO menus (menu_key, label, sort_order) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $menuKey, $menuKey, $next);
    if (!$stmt->execute()) fail("Failed to insert test menu: " . $conn->error);
    pass("Inserted test menu");
} else {
    pass("Test menu exists");
}

// Clean previous test entries
$conn->query("DELETE FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key = '" . $conn->real_escape_string($menuKey) . "'");

// Insert visible = 0 entry
$stmt = $conn->prepare("INSERT INTO user_access (user_id, menu_key, `full`, can_create, can_read, can_update, can_delete, visible) VALUES (?, ?, 0,0,0,0,0, ?)");
$vis = 0;
$stmt->bind_param('isi', $testUser, $menuKey, $vis);
if (!$stmt->execute()) fail("Failed to insert user_access row: " . $conn->error);
pass("Inserted user_access with visible=0");

// Verify stored value
$res = $conn->query("SELECT visible FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key = '" . $conn->real_escape_string($menuKey) . "'");
if (!$res) fail("Select failed: " . $conn->error);
$row = $res->fetch_assoc();
if (intval($row['visible']) !== 0) fail("Expected visible=0, got " . $row['visible']);
pass("Verified visible=0 stored correctly");

// Update to visible = 1
$conn->query("UPDATE user_access SET visible = 1 WHERE user_id = " . intval($testUser) . " AND menu_key = '" . $conn->real_escape_string($menuKey) . "'");
$res = $conn->query("SELECT visible FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key = '" . $conn->real_escape_string($menuKey) . "'");
$row = $res->fetch_assoc();
if (intval($row['visible']) !== 1) fail("Expected visible=1 after update, got " . $row['visible']);
pass("Verified visible=1 update works");

// Cleanup
$conn->query("DELETE FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key = '" . $conn->real_escape_string($menuKey) . "'");
pass("Cleanup done");

echo "All tests completed successfully.\n";
exit(0);
