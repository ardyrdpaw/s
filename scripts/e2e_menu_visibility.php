<?php
// E2E-ish test: ensure menu visibility affects what's rendered in layout_header.php
include __DIR__ . '/../php/db_connect.php';

$testUser = 999997;
$menuKey = 'e2e_menu_visible_all';
$menuLabel = 'E2E Visible Menu';

function fail($m){ echo "[FAIL] $m\n"; exit(1); }
function pass($m){ echo "[PASS] $m\n"; }

// Ensure test menu exists
$res = $conn->query("SELECT COUNT(*) as c FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
$r = $res->fetch_assoc();
if (intval($r['c']) === 0) {
    $maxRes = $conn->query("SELECT MAX(sort_order) as m FROM menus");
    $next = 1;
    if ($maxRes) { $mr = $maxRes->fetch_assoc(); $next = intval($mr['m'] ?? 0) + 1; }
    $stmt = $conn->prepare("INSERT INTO menus (menu_key, label, sort_order) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $menuKey, $menuLabel, $next);
    if (!$stmt->execute()) fail('Failed to create test menu: ' . $conn->error);
    pass('Inserted test menu');
} else {
    pass('Test menu exists');
}

// Ensure test user exists
$res = $conn->query("SELECT COUNT(*) as c FROM users WHERE id = " . intval($testUser));
$r = $res->fetch_assoc();
if (intval($r['c']) === 0) {
    $stmt = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?)");
    // weak password placeholder; we won't use it to login
    $pw = password_hash('test', PASSWORD_DEFAULT);
    $name = 'e2e_user'; $email = 'e2e_user@example';
    $stmt->bind_param('isss', $testUser, $name, $email, $pw);
    if (!$stmt->execute()) fail('Failed to create test user: ' . $conn->error);
    pass('Inserted test user');
} else {
    pass('Test user exists');
}

// set visible = 0 for this user/menu
$conn->query("DELETE FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key='" . $conn->real_escape_string($menuKey) . "'");
$conn->query("INSERT INTO user_access (user_id, menu_key, `full`, can_create, can_read, can_update, can_delete, visible) VALUES (" . intval($testUser) . ", '" . $conn->real_escape_string($menuKey) . "', 0,0,0,0,0, 0)");
pass('Inserted user_access visible=0');

// simulate session and include header to capture navigation
session_start();
$_SESSION['user_id'] = $testUser;
$_SESSION['role'] = 'user';
ob_start();
include __DIR__ . '/../layout_header.php';
$html = ob_get_clean();
if (strpos($html, $menuLabel) !== false) fail('Menu label found in header output despite visible=0');
pass('Menu not shown when visible=0 (non-admin)');

// now set visible = 1 and re-include header
$conn->query("UPDATE user_access SET visible = 1 WHERE user_id = " . intval($testUser) . " AND menu_key='" . $conn->real_escape_string($menuKey) . "'");
ob_start();
include __DIR__ . '/../layout_header.php';
$html2 = ob_get_clean();
if (strpos($html2, $menuLabel) === false) fail('Menu label NOT found after visible=1');
pass('Menu shown when visible=1 (non-admin)');

// cleanup: remove user_access and test menu/user
$conn->query("DELETE FROM user_access WHERE user_id = " . intval($testUser) . " AND menu_key='" . $conn->real_escape_string($menuKey) . "'");
$conn->query("DELETE FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
$conn->query("DELETE FROM users WHERE id = " . intval($testUser));
pass('Cleanup done');

echo "E2E menu visibility test completed successfully.\n";
exit(0);
