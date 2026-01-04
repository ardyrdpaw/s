<?php
// Unit test for user preferences storage via user_api
ob_start();
include __DIR__ . '/../php/db_connect.php';

$testUser = 999998;
function fail($m){ ob_end_clean(); echo "[FAIL] $m\n"; exit(1); }
function pass($m){ echo "[PASS] $m\n"; }

// ensure preferences column exists
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'preferences'");
if (!$colRes || $colRes->num_rows === 0) fail("'preferences' column missing");
pass("'preferences' column exists");

// create test user
$conn->query("DELETE FROM users WHERE id = " . intval($testUser));
$stmt = $conn->prepare("INSERT INTO users (id, name, username, password, role) VALUES (?, ?, ?, ?, 'user')");
$pw = password_hash('test', PASSWORD_DEFAULT); $name='ut_user'; $username='ut_username_999';
$stmt->bind_param('isss', $testUser, $name, $username, $pw);
if (!$stmt->execute()) fail('Failed to insert test user: ' . $conn->error);
pass('Inserted test user');

// update preferences via direct SQL (simulate API)
$prefs = json_encode(['theme'=>'dark','notifications'=>1]);
$conn->query("UPDATE users SET preferences='" . $conn->real_escape_string($prefs) . "' WHERE id = " . intval($testUser));
$res = $conn->query("SELECT preferences FROM users WHERE id = " . intval($testUser));
$r = $res->fetch_assoc();
if ($r['preferences'] !== $prefs) fail('Preferences did not persist via SQL');
pass('Preferences persisted via SQL');

// now test API edit: (we'll call the API script directly)
// prepare session so that the API recognizes this as the same user
session_start();
$_SESSION['user_id'] = $testUser;

$_POST = [];
$_POST['id'] = $testUser;
$_POST['preferences'] = json_encode(['theme'=>'light','notifications'=>0]);
$_GET['action'] = 'preferences';
ob_start(); include __DIR__ . '/../php/user_api.php'; $out = ob_get_clean();
// attempt to find JSON payload in output (strip PHP notices/warnings)
$pos = strpos($out, '{');
if ($pos !== false) $jsonOut = substr($out, $pos); else $jsonOut = $out;
$resp = json_decode($jsonOut,true);
if (!$resp || empty($resp['success'])) fail('API preferences POST failed: ' . $out);
pass('API preferences POST returned success');

// read back via API preferences GET
$_GET = ['action'=>'preferences','id'=>$testUser];
ob_start(); include __DIR__ . '/../php/user_api.php'; $out2 = ob_get_clean();
$pos2 = strpos($out2, '{');
if ($pos2 !== false) $jsonOut2 = substr($out2, $pos2); else $jsonOut2 = $out2;
$r2 = json_decode($jsonOut2,true);
if (!$r2 || !isset($r2['data'])) fail('API preferences GET did not return data: ' . $out2);
if ($r2['data']['theme'] !== 'light') fail('Preferences value mismatch');
pass('API preferences GET returned updated preferences');

// cleanup
$conn->query("DELETE FROM users WHERE id = " . intval($testUser));
pass('Cleanup done');

echo "Unit test completed successfully.\n"; exit(0);
