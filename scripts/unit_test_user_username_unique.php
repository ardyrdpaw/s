<?php
// Unit test: ensure username uniqueness enforced via API
ob_start();
include __DIR__ . '/../php/db_connect.php';

function fail($m){ ob_end_clean(); echo "[FAIL] $m\n"; exit(1); }
function pass($m){ echo "[PASS] $m\n"; }

// cleanup
$conn->query("DELETE FROM users WHERE name LIKE 'ut_username_%' OR name LIKE 'ut_a%' OR name LIKE 'ut_b%'");

// Prepare first add
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// run as admin for API operations
$_SESSION['user_id'] = 1; $_SESSION['role'] = 'admin';
$_POST = ['name' => 'ut_username_a', 'username' => 'username_unique_1', 'password' => 'testpw', 'role' => 'user'];
$_GET = ['action' => 'add'];
ob_start(); include __DIR__ . '/../php/user_api.php'; $out1 = ob_get_clean();
$pos = strpos($out1, '{'); $json1 = $pos !== false ? substr($out1, $pos) : $out1; $r1 = json_decode($json1, true);
if (!$r1 || empty($r1['success'])) fail('First add failed: ' . $out1);
pass('First add succeeded');

// Second add with same username should fail
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['user_id'] = 1; $_SESSION['role'] = 'admin';
$_POST = ['name' => 'ut_username_b', 'username' => 'username_unique_1', 'password' => 'testpw', 'role' => 'user'];
$_GET = ['action' => 'add'];
ob_start(); include __DIR__ . '/../php/user_api.php'; $out2 = ob_get_clean();
$pos2 = strpos($out2, '{'); $json2 = $pos2 !== false ? substr($out2, $pos2) : $out2; $r2 = json_decode($json2, true);
if ($r2 && empty($r2['success']) && isset($r2['msg']) && strpos($r2['msg'], 'Username') !== false) pass('Duplicate username prevented on add');
else fail('Duplicate username not prevented on add: ' . $out2);

// Now create two distinct users and attempt to edit
// create user A
if (session_status() !== PHP_SESSION_ACTIVE) session_start(); $_SESSION['user_id'] = 1; $_SESSION['role'] = 'admin';
$_POST = ['name' => 'ut_a', 'username' => 'username_a', 'password' => 'pw', 'role' => 'user']; $_GET = ['action' => 'add']; ob_start(); include __DIR__ . '/../php/user_api.php'; ob_end_clean();
// create user B
if (session_status() !== PHP_SESSION_ACTIVE) session_start(); $_SESSION['user_id'] = 1; $_SESSION['role'] = 'admin';
$_POST = ['name' => 'ut_b', 'username' => 'username_b', 'password' => 'pw', 'role' => 'user']; $_GET = ['action' => 'add']; ob_start(); include __DIR__ . '/../php/user_api.php'; ob_end_clean();
// fetch user ids
$r = $conn->query("SELECT id FROM users WHERE username='username_a' LIMIT 1"); $ua = $r->fetch_assoc();
$r = $conn->query("SELECT id FROM users WHERE username='username_b' LIMIT 1"); $ub = $r->fetch_assoc();
if (!$ua || !$ub) fail('Failed to create users for edit test');
// attempt to edit B to use username_a
$_POST = ['id' => $ub['id'], 'name' => 'ut_b', 'username' => 'username_a']; $_GET = ['action' => 'edit']; ob_start(); include __DIR__ . '/../php/user_api.php'; $out3 = ob_get_clean();
$pos3 = strpos($out3, '{'); $json3 = $pos3 !== false ? substr($out3, $pos3) : $out3; $r3 = json_decode($json3, true);
if ($r3 && empty($r3['success']) && isset($r3['msg']) && strpos($r3['msg'], 'Username') !== false) pass('Duplicate username prevented on edit');
else fail('Duplicate username not prevented on edit: ' . $out3);

// cleanup
$conn->query("DELETE FROM users WHERE name LIKE 'ut_username_%' OR name LIKE 'ut_a%' OR name LIKE 'ut_b%'");
pass('Cleanup done');

echo "Unit test completed successfully.\n"; exit(0);
?>