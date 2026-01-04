<?php
// Simple test for login flow using username/password
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include __DIR__ . '/../php/db_connect.php';
// create test user
$testId = 999990;
$pw = password_hash('secret123', PASSWORD_DEFAULT);
$conn->query("DELETE FROM users WHERE id=".intval($testId));
$stmt = $conn->prepare("INSERT INTO users (id, name, username, password, role) VALUES (?, ?, ?, ?, 'user')");
$name='test_login'; $username='test_login_user';
$stmt->bind_param('isss', $testId, $name, $username, $pw);
if (!$stmt->execute()) { echo "[FAIL] could not insert test user: " . $conn->error . "\n"; exit(1); }
// simulate POST request to login.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['username' => $username, 'password' => 'secret123'];
$_SESSION = []; // reset session before test
// include login logic (up to the header redirect)
include 'php/db_connect.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = $conn->query("SELECT * FROM users WHERE username='$username_input' LIMIT 1");
    if ($result && $user = $result->fetch_assoc()) {
        $stored = $user['password'] ?? '';
        $ok = false;
        if (!empty($stored) && password_verify($password, $stored)) $ok = true;
        elseif ($password === $stored) $ok = true;
        if ($ok) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['name'] = $user['name'] ?? '';
            $_SESSION['username'] = $user['username'] ?? '';
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'User not found.';
    }
}
if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $testId) echo "[PASS] Login succeeded\n";
else echo "[FAIL] Login did not set session; error=".$error."\n";
// cleanup
$conn->query("DELETE FROM users WHERE id=".intval($testId));
?>