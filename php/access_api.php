<?php
include 'db_connect.php';
session_start(); include_once __DIR__ . '/auth.php'; require_login(); require_menu_access('user_access');
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

// admin check helper
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Default menus (used to seed DB if empty)
$DEFAULT_MENUS = [
    ['key' => 'dashboard', 'label' => 'Home'],
    ['key' => 'cat', 'label' => 'CAT'],
    ['key' => 'signage', 'label' => 'Signage'],
    ['key' => 'user', 'label' => 'Users'],
    ['key' => 'nomor_surat', 'label' => 'Nomor Surat'],
    ['key' => 'pengadaan_asn', 'label' => 'Pengadaan ASN'],
    ['key' => 'data_kepegawaian', 'label' => 'Data Kepegawaian']
];

// Ensure user_access table exists
$conn->query("CREATE TABLE IF NOT EXISTS user_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_key VARCHAR(100) NOT NULL,
    `full` TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_read TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    visible TINYINT(1) DEFAULT 0,
    UNIQUE KEY ux_user_menu (user_id, menu_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add visible column if missing (upgrade path for existing installs)
$colRes = $conn->query("SHOW COLUMNS FROM user_access LIKE 'visible'");
if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE user_access ADD COLUMN visible TINYINT(1) DEFAULT 0");
} 

// Ensure menus table exists
$conn->query("CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(200) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed menus if empty
$res = $conn->query("SELECT COUNT(*) as c FROM menus");
if ($res) {
    $r = $res->fetch_assoc();
    if (intval($r['c']) === 0) {
        $stmt = $conn->prepare("INSERT INTO menus (menu_key, label, sort_order) VALUES (?, ?, ?)");
        $i = 0;
        foreach ($DEFAULT_MENUS as $m) {
            $i++;
            $stmt->bind_param('ssi', $m['key'], $m['label'], $i);
            $stmt->execute();
        }
    }
}

switch ($action) {
    case 'get_menus':
        $menus = [];
        // include protected flag so access UI can react to it
        $res = $conn->query("SELECT menu_key as `key`, label, COALESCE(`protected`,0) as `protected` FROM menus ORDER BY sort_order ASC, label ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $menus[] = $row;
            }
        }
        echo json_encode(['data' => $menus]);
        break;
    case 'list':
        $user_id = intval($_GET['user_id'] ?? 0);
        $rows = [];
        if ($user_id > 0) {
            // allow admins to list any user; non-admins can only list their own access
            if (!$isAdmin && (!isset($_SESSION['user_id']) || intval($_SESSION['user_id']) !== $user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                break;
            }
            $res = $conn->query("SELECT menu_key, `full`, can_create, can_read, can_update, can_delete, visible FROM user_access WHERE user_id=".intval($user_id));
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $rows[$r['menu_key']] = [
                        'full' => intval($r['full']),
                        'create' => intval($r['can_create']),
                        'read' => intval($r['can_read']),
                        'update' => intval($r['can_update']),
                        'delete' => intval($r['can_delete']),
                        'visible' => intval($r['visible'])
                    ];
                }
            }
        }
        echo json_encode(['data' => $rows]);
        break;
    case 'set':
        // only admins may set permissions
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        // Support form POST fallback
        if (!$input) {
            $input = $_POST;
        }
        $user_id = intval($input['user_id'] ?? 0);
        $permissions = $input['permissions'] ?? null;
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }
        if ($user_id <= 0 || !is_array($permissions)) {
            echo json_encode(['error' => 'Invalid payload']);
            break;
        }
        // Remove existing for the user
        $conn->query("DELETE FROM user_access WHERE user_id=".intval($user_id));
        // Insert new (include visible)
        $stmt = $conn->prepare("INSERT INTO user_access (user_id, menu_key, `full`, can_create, can_read, can_update, can_delete, visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($permissions as $perm) {
            $menu = $conn->real_escape_string($perm['menu'] ?? '');
            $full = intval($perm['full'] ?? 0);
            $c = intval($perm['create'] ?? 0);
            $r = intval($perm['read'] ?? 0);
            $u = intval($perm['update'] ?? 0);
            $d = intval($perm['delete'] ?? 0);
            $v = intval($perm['visible'] ?? 0);
            $stmt->bind_param('isiiiiii', $user_id, $menu, $full, $c, $r, $u, $d, $v);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>