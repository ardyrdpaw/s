<?php
// Unit test for menus protected flag
include __DIR__ . '/../php/db_connect.php';

function fail($m){ echo "[FAIL] $m\n"; exit(1); }
function pass($m){ echo "[PASS] $m\n"; }

$menuKey = 'unit_test_protected_menu';
// cleanup
$conn->query("DELETE FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");

// add menu with protected=1
$stmt = $conn->prepare("INSERT INTO menus (menu_key, label, sort_order, `protected`) VALUES (?, ?, ?, 1)");
$label = 'Unit Protected'; $next = 9999;
$stmt->bind_param('ssi', $menuKey, $label, $next);
if (!$stmt->execute()) fail('Failed to insert test menu: ' . $conn->error);
pass('Inserted protected menu');

// list menus via API
$res = $conn->query("SELECT protected FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
if (!$res) fail('Select failed');
$r = $res->fetch_assoc();
if (intval($r['protected']) !== 1) fail('Expected protected=1');
pass('Protected flag persisted');

// update menu via API-like update
$stmt = $conn->prepare("UPDATE menus SET protected = 0 WHERE menu_key = ?");
$stmt->bind_param('s', $menuKey);
if (!$stmt->execute()) fail('Failed to update menu: ' . $conn->error);
pass('Updated protected->0');

// verify
$res = $conn->query("SELECT protected FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
$r = $res->fetch_assoc();
if (intval($r['protected']) !== 0) fail('Expected protected=0 after update');
pass('Verified update persisted');

// cleanup
$conn->query("DELETE FROM menus WHERE menu_key='" . $conn->real_escape_string($menuKey) . "'");
pass('Cleanup');

echo "Unit test completed.\n"; 
exit(0);
