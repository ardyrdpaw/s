<?php
include 'db_connect.php';
session_start(); include_once __DIR__ . '/auth.php'; require_login(); require_menu_access('user');
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

switch ($action) {
    case 'list':
        // only admins can list all users
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); break; }
        $users = [];
        $result = $conn->query('SELECT id, name, username, role, keterangan FROM users');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['data' => $users]);
        break;
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        // allow admin or the user themselves
        if (!$isAdmin && (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) !== $id)) {
            http_response_code(403); echo json_encode(['error' => 'Forbidden']); break;
        }
        $result = $conn->query("SELECT id, name, username, role, keterangan, preferences FROM users WHERE id=$id");
        $user = $result ? $result->fetch_assoc() : null;
        // decode preferences JSON to object if present
        if ($user && isset($user['preferences']) && $user['preferences'] !== null) {
            $prefs = json_decode($user['preferences'], true);
            $user['preferences'] = $prefs === null ? new stdClass() : $prefs;
        }
        echo json_encode(['data' => $user]);
        break;
    case 'add':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); break; }
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $username = $conn->real_escape_string($_POST['username'] ?? '');
        $keterangan = $conn->real_escape_string($_POST['keterangan'] ?? '');
        $pw = $_POST['password'] ?? '';
        if (!$pw) { echo json_encode(['success' => false, 'msg' => 'Password required']); break; }
        if (!trim($name) || !trim($username)) { echo json_encode(['success' => false, 'msg' => 'Name and username required']); break; }
        // ensure username is unique
        $chk = $conn->query("SELECT id FROM users WHERE username='" . $conn->real_escape_string($username) . "' LIMIT 1");
        if ($chk && $chk->num_rows > 0) { echo json_encode(['success' => false, 'msg' => 'Username already in use']); break; }
        $passwordHash = password_hash($pw, PASSWORD_DEFAULT);
        $password = $conn->real_escape_string($passwordHash);
        $role = $conn->real_escape_string($_POST['role'] ?? 'user');
        $conn->query("INSERT INTO users (name, username, password, role, keterangan) VALUES ('$name', '$username', '$password', '$role', '$keterangan')");
        echo json_encode(['success' => true]);
        break;
    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        // only admin or the user themselves can edit (users can edit their own name/username/password but not role)
        if ($id <= 0) { echo json_encode(['success' => false, 'msg' => 'Invalid user id']); break; }
        if (!$isAdmin && (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) !== $id)) { http_response_code(403); echo json_encode(['success' => false, 'msg' => 'Forbidden']); break; }
        // server-side validation for name/username
        $nameRaw = $_POST['name'] ?? '';
        $usernameRaw = $_POST['username'] ?? '';
        if (!trim($nameRaw) || !trim($usernameRaw)) {
            echo json_encode(['success' => false, 'msg' => 'Invalid name or username']); break;
        }
        $name = $conn->real_escape_string($nameRaw);
        $username = $conn->real_escape_string($usernameRaw);
        $keterangan = $conn->real_escape_string($_POST['keterangan'] ?? '');
        // ensure username is unique for other users
        $chk = $conn->query("SELECT id FROM users WHERE username='" . $conn->real_escape_string($username) . "' AND id<>" . intval($id) . " LIMIT 1");
        if ($chk && $chk->num_rows > 0) { echo json_encode(['success' => false, 'msg' => 'Username already in use']); break; }
        $set = "name='$name', username='$username', keterangan='$keterangan'";
        if ($isAdmin) {
            $role = $conn->real_escape_string($_POST['role'] ?? 'user');
            $set .= ", role='$role'";
        }
        if (!empty($_POST['password'])) {
            // hash the password before storing
            $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $set .= ", password='" . $conn->real_escape_string($passwordHash) . "'";
        }
        $conn->query("UPDATE users SET $set WHERE id=$id");
        echo json_encode(['success' => true, 'msg' => 'Profile updated']);
        break;
    case 'preferences':
        // GET -> fetch current user's prefs (or any user if admin and id provided)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = intval($_GET['id'] ?? $_SESSION['user_id'] ?? 0);
            if (!$isAdmin && (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) !== $id)) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); break; }
            $result = $conn->query("SELECT preferences FROM users WHERE id=$id");
            $prefs = [];
            if ($result && ($row = $result->fetch_assoc()) && $row['preferences']) {
                $prefs = json_decode($row['preferences'], true) ?: [];
            }
            echo json_encode(['data' => $prefs]);
            break;
        }
        // POST -> save preferences JSON
        $id = intval($_POST['id'] ?? $_SESSION['user_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'msg' => 'Invalid user id']); break; }
        if (!$isAdmin && (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) !== $id)) { http_response_code(403); echo json_encode(['success' => false, 'msg' => 'Forbidden']); break; }
        $prefsRaw = $_POST['preferences'] ?? '';
        if (is_string($prefsRaw)) {
            $decoded = json_decode($prefsRaw, true);
            if ($decoded === null && $prefsRaw !== 'null') { echo json_encode(['success' => false, 'msg' => 'Malformed preferences JSON']); break; }
            $prefs = $decoded ?? [];
        } elseif (is_array($prefsRaw)) {
            $prefs = $prefsRaw;
        } else {
            $prefs = [];
        }
        // simple validation: ensure theme is either 'light' or 'dark' if present
        if (isset($prefs['theme']) && !in_array($prefs['theme'], ['light','dark'])) { echo json_encode(['success' => false, 'msg' => 'Invalid theme']); break; }
        $prefsJson = $conn->real_escape_string(json_encode($prefs));
        $conn->query("UPDATE users SET preferences='$prefsJson' WHERE id=$id");
        echo json_encode(['success' => true, 'data' => $prefs]);
        break;
    case 'delete':
        if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); break; }
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM users WHERE id=$id");
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>