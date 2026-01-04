<?php
<?php
// Deprecated test: username uniqueness test replaced with `unit_test_user_username_unique.php`.
echo "[SKIP] Deprecated test. Use unit_test_user_username_unique.php instead.\n"; exit(0);
?>
ob_start();
include __DIR__ . '/../php/db_connect.php';

function fail($m){ ob_end_clean(); echo "[FAIL] $m\n"; exit(1); }
function pass($m){ echo "[PASS] $m\n"; }

// cleanup
$conn->query("DELETE FROM users WHERE name LIKE 'ut_nip_%' OR name LIKE 'ut_a%' OR name LIKE 'ut_b%'");

// Prepare first add
$_POST = ['name' => 'ut_nip_a', 'nip' => 'nip_unique_1', 'password' => 'testpw', 'role' => 'user'];
$_GET = ['action' => 'add'];
ob_start(); include __DIR__ . '/../php/user_api.php'; $out1 = ob_get_clean();
$pos = strpos($out1, '{'); $json1 = $pos !== false ? substr($out1, $pos) : $out1; $r1 = json_decode($json1, true);
if (!$r1 || empty($r1['success'])) fail('First add failed: ' . $out1);
pass('First add succeeded');

// Second add with same nip should fail
$_POST = ['name' => 'ut_nip_b', 'nip' => 'nip_unique_1', 'password' => 'testpw', 'role' => 'user'];
$_GET = ['action' => 'add'];
ob_start(); include __DIR__ . '/../php/user_api.php'; $out2 = ob_get_clean();
$pos2 = strpos($out2, '{'); $json2 = $pos2 !== false ? substr($out2, $pos2) : $out2; $r2 = json_decode($json2, true);
if ($r2 && empty($r2['success']) && isset($r2['msg']) && strpos($r2['msg'], 'NIP') !== false) pass('Duplicate NIP prevented on add');
else fail('Duplicate NIP not prevented on add: ' . $out2);

// Now create two distinct users and attempt to edit
// create user A
$_POST = ['name' => 'ut_a', 'nip' => 'nip_a', 'password' => 'pw', 'role' => 'user']; $_GET = ['action' => 'add']; ob_start(); include __DIR__ . '/../php/user_api.php'; ob_end_clean();
// create user B
$_POST = ['name' => 'ut_b', 'nip' => 'nip_b', 'password' => 'pw', 'role' => 'user']; $_GET = ['action' => 'add']; ob_start(); include __DIR__ . '/../php/user_api.php'; ob_end_clean();
// fetch user ids
$r = $conn->query("SELECT id FROM users WHERE nip='nip_a' LIMIT 1"); $ua = $r->fetch_assoc();
$r = $conn->query("SELECT id FROM users WHERE nip='nip_b' LIMIT 1"); $ub = $r->fetch_assoc();
if (!$ua || !$ub) fail('Failed to create users for edit test');
// attempt to edit B to use nip_a
$_POST = ['id' => $ub['id'], 'name' => 'ut_b', 'nip' => 'nip_a']; $_GET = ['action' => 'edit']; ob_start(); include __DIR__ . '/../php/user_api.php'; $out3 = ob_get_clean();
$pos3 = strpos($out3, '{'); $json3 = $pos3 !== false ? substr($out3, $pos3) : $out3; $r3 = json_decode($json3, true);
if ($r3 && empty($r3['success']) && isset($r3['msg']) && strpos($r3['msg'], 'NIP') !== false) pass('Duplicate NIP prevented on edit');
else fail('Duplicate NIP not prevented on edit: ' . $out3);

// cleanup
$conn->query("DELETE FROM users WHERE name LIKE 'ut_nip_%' OR name LIKE 'ut_a%' OR name LIKE 'ut_b%'");
pass('Cleanup done');

echo "Unit test completed successfully.\n"; exit(0);
?>