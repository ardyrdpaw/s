<?php
// Centralized authentication helpers
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /sapps/login.php');
        exit;
    }
}

function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
}

/**
 * Enforce per-menu access as managed in `user_access` and `menus` tables.
 * - If menu is protected and user is not admin -> deny
 * - If user has explicit access record, require `can_read` or `full` to view
 * - Otherwise allow (default permissive for existing apps)
 */
function require_menu_access($menu_key) {
    $uid = current_user_id();
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    // allow admins
    if ($isAdmin) return true;
    if (!$menu_key) return true;
    // ensure a global DB connection is available
    if (!isset($GLOBALS['conn'])) {
        include __DIR__ . '/db_connect.php';
        // if include created a local $conn, promote it to global
        if (isset($conn)) $GLOBALS['conn'] = $conn;
    }
    $conn = $GLOBALS['conn'];
    $mk = $conn->real_escape_string($menu_key);
    $mres = $conn->query("SELECT protected FROM menus WHERE menu_key='" . $mk . "' LIMIT 1");
    if ($mres && $m = $mres->fetch_assoc()) {
        if (isset($m['protected']) && intval($m['protected']) === 1) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
    }
    // check user_access row for this user/menu
    if ($uid) {
        $ures = $conn->query("SELECT full, can_read FROM user_access WHERE user_id=" . intval($uid) . " AND menu_key='" . $mk . "' LIMIT 1");
        if ($ures && ($ur = $ures->fetch_assoc())) {
            $full = intval($ur['full']);
            $can_read = intval($ur['can_read']);
            if ($full === 1 || $can_read === 1) return true;
            // explicitly denied
            http_response_code(403);
            echo "Access denied";
            exit;
        }
    }
    // no explicit restriction found -> allow
    return true;
}

?>
