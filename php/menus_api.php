<?php
include 'db_connect.php';
session_start(); include_once __DIR__ . '/auth.php'; require_login(); require_menu_access('menus');
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

// simple admin check
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
// protect write operations
if (in_array($action, ['add','edit','delete']) && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

switch ($action) {
    case 'list':
        $rows = [];
        $res = $conn->query("SELECT id, menu_key, label, sort_order, COALESCE(`protected`,0) as `protected` FROM menus ORDER BY sort_order ASC, label ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        echo json_encode(['data' => $rows]);
        break;
    case 'add':
        $keyRaw = $_POST['menu_key'] ?? '';
        $key = $conn->real_escape_string($keyRaw);
        $labelRaw = $_POST['label'] ?? '';
        $label = $conn->real_escape_string($labelRaw);
        $sort = intval($_POST['sort_order'] ?? 0);
        // validate key: only lowercase letters, numbers and underscore
        if (!$key || !$label || !preg_match('/^[a-z0-9_]+$/', $keyRaw)) { echo json_encode(['error' => 'Invalid input: key must be lowercase letters, numbers or underscore']); break; }
        // ensure menu_key is unique
        $r = $conn->query("SELECT id FROM menus WHERE menu_key='".$key."'");
        if ($r && $r->num_rows > 0) { echo json_encode(['error' => 'Menu key already exists']); break; }
        $protected = intval($_POST['protected'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO menus (menu_key, label, sort_order, `protected`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssii', $key, $label, $sort, $protected);
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            // attempt to create target PHP page if missing
            $filename = __DIR__ . '/../' . $key . '.php';
            $created = false;
            if (!file_exists($filename)) {
                $pageContent = "<?php include 'layout_header.php'; ?>\n" .
                    "<div class=\"container-fluid mt-4\">\n" .
                    "  <h1 class=\"h3 mb-4\">" . htmlspecialchars($labelRaw, ENT_QUOTES) . "</h1>\n" .
                    "  <div class=\"card mt-4\">\n" .
                    "    <div class=\"card-body\">\n" .
                    "      <p>This page was auto-generated for the menu with key <strong>" . htmlspecialchars($keyRaw, ENT_QUOTES) . "</strong>. Edit this file to add content.</p>\n" .
                    "    </div>\n" .
                    "  </div>\n" .
                    "</div>\n" .
                    "<?php include 'layout_footer.php'; ?>\n";
                // try to write file
                if (file_put_contents($filename, $pageContent) !== false) {
                    $created = true;
                }
            }
            echo json_encode(['success' => true, 'id' => $newId, 'page_created' => $created]);
        } else echo json_encode(['error' => $conn->error]);
        break;
    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $label = $conn->real_escape_string($_POST['label'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        if ($id <= 0 || !$label) { echo json_encode(['error' => 'Invalid input']); break; }
        $protected = intval($_POST['protected'] ?? 0);
        $stmt = $conn->prepare("UPDATE menus SET label=?, sort_order=?, `protected` = ? WHERE id=?");
        $stmt->bind_param('siii', $label, $sort, $protected, $id);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['error' => $conn->error]);
        break;
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['error' => 'Invalid id']); break; }
        // Optionally remove related user_access rows that reference menu_key
        // We'll fetch the menu_key first to clean access
        $res = $conn->query("SELECT menu_key FROM menus WHERE id=".intval($id));
        $menu_key = null;
        if ($res && $row = $res->fetch_assoc()) $menu_key = $row['menu_key'];
        $stmt = $conn->prepare("DELETE FROM menus WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            if ($menu_key) $conn->query("DELETE FROM user_access WHERE menu_key='".$conn->real_escape_string($menu_key)."'");
            echo json_encode(['success' => true]);
        } else echo json_encode(['error' => $conn->error]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
