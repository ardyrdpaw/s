<?php
session_start(); include_once __DIR__ . '/auth.php'; require_login(); require_menu_access('inf_ti');
header('Content-Type: application/json');
include_once __DIR__ . '/db_connect.php';

// helper to return or echo API responses; when UNIT_TESTING is defined, store result in a global var
function inf_api_output($data){
  if (defined('UNIT_TESTING')){
    $GLOBALS['__inf_api_result'] = $data;
    return;
  }
  echo json_encode($data);
  exit;
}

// ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS inf_ti_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(32) NOT NULL,
  name VARCHAR(255) NOT NULL,
  detail TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// seed sample rows if empty; desired order: komputer, printer, jaringan
$res = $conn->query("SELECT COUNT(*) as c FROM inf_ti_items");
$row = $res->fetch_assoc();
if ((int)$row['c'] === 0) {
  $stmt = $conn->prepare("INSERT INTO inf_ti_items (category, name, detail) VALUES (?, ?, ?)");
  $samples = [
    ['komputer', 'PC Ruang Admin', 'i5, 8GB, 256GB SSD'],
    ['printer', 'HP LaserJet', 'Pro M404'],
    ['jaringan', 'Switch Lantai 1', '24 port Gigabit']
  ];
  foreach ($samples as $s) {
    $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
    $stmt->execute();
  }
  $stmt->close();
} else {
  // If the table contains exactly the original three sample rows, migrate them to the new order
  $sampleNames = ['Switch Lantai 1','PC Ruang Admin','HP LaserJet'];
  $in = "'" . implode("','", $sampleNames) . "'";
  $check = $conn->query("SELECT COUNT(*) as c FROM inf_ti_items WHERE name IN ($in)");
  $c = $check->fetch_assoc();
  if ((int)$c['c'] === 3) {
    // delete the old sample rows and re-insert in the new desired order
    $conn->query("DELETE FROM inf_ti_items WHERE name IN ($in)");
    $stmt = $conn->prepare("INSERT INTO inf_ti_items (category, name, detail) VALUES (?, ?, ?)");
    $samples = [
      ['komputer', 'PC Ruang Admin', 'i5, 8GB, 256GB SSD'],
      ['printer', 'HP LaserJet', 'Pro M404'],
      ['jaringan', 'Switch Lantai 1', '24 port Gigabit']
    ];
    foreach ($samples as $s) {
      $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
      $stmt->execute();
    }
    $stmt->close();
  }
}

$action = $_REQUEST['action'] ?? 'list';
$allowedCategories = ['jaringan','komputer','printer'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Use sort_order for explicit ordering if present
$hasSortOrderRes = $conn->query("SHOW COLUMNS FROM inf_ti_items LIKE 'sort_order'");
$hasSortOrder = ($hasSortOrderRes && $hasSortOrderRes->num_rows > 0);

if ($action === 'list') {
  $category = $_GET['category'] ?? null;
  if ($category && !in_array($category, $allowedCategories)) {
    inf_api_output(['success' => false, 'msg' => 'Invalid category']);
  }
  if ($category) {
    if ($hasSortOrder) {
      $stmt = $conn->prepare('SELECT id, category, name, detail, created_at, updated_at FROM inf_ti_items WHERE category = ? ORDER BY sort_order ASC, id ASC');
    } else {
      $stmt = $conn->prepare('SELECT id, category, name, detail, created_at, updated_at FROM inf_ti_items WHERE category = ? ORDER BY id ASC');
    }
    $stmt->bind_param('s', $category);
  } else {
    if ($hasSortOrder) {
      $stmt = $conn->prepare('SELECT id, category, name, detail, created_at, updated_at FROM inf_ti_items ORDER BY sort_order ASC, id ASC');
    } else {
      $stmt = $conn->prepare('SELECT id, category, name, detail, created_at, updated_at FROM inf_ti_items ORDER BY category, id ASC');
    }
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $items = [];
  while ($r = $res->fetch_assoc()) $items[] = $r;
  echo json_encode(['success' => true, 'items' => $items]);
  if (!defined('UNIT_TESTING')) exit; 
}

if (!$isAdmin) {
  echo json_encode(['success' => false, 'msg' => 'Admin privileges required for this action']);
  if (!defined('UNIT_TESTING')) exit;
} 

if ($action === 'add') {
  $category = $_POST['category'] ?? '';
  $name = trim($_POST['name'] ?? '');
  $detail = trim($_POST['detail'] ?? '');
  if (!in_array($category, $allowedCategories) || $name === '') {
    echo json_encode(['success' => false, 'msg' => 'Invalid input']);
    if (!defined('UNIT_TESTING')) exit;
  }
  // compute next sort_order for the category if column exists
  if ($hasSortOrder) {
    // compute next sort for category using safe escaping
    $catEsc = $conn->real_escape_string($category);
    $mx = $conn->query("SELECT COALESCE(MAX(sort_order), 0) as m FROM inf_ti_items WHERE category = '" . $catEsc . "'");
    $mrow = $mx->fetch_assoc();
    $nextSort = (int)$mrow['m'] + 1;
    $stmt = $conn->prepare('INSERT INTO inf_ti_items (category, name, detail, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $category, $name, $detail, $nextSort);
  } else {
    $stmt = $conn->prepare('INSERT INTO inf_ti_items (category, name, detail) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $category, $name, $detail);
  }
  $ok = $stmt->execute();
  $id = $conn->insert_id;
  $stmt->close();
  echo json_encode(['success' => (bool)$ok, 'id' => $id]);
  if (!defined('UNIT_TESTING')) exit; 
}

if ($action === 'edit') {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $detail = trim($_POST['detail'] ?? '');
  if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'msg' => 'Invalid input']);
    if (!defined('UNIT_TESTING')) exit;
  }
  $stmt = $conn->prepare('UPDATE inf_ti_items SET name = ?, detail = ? WHERE id = ?');
  $stmt->bind_param('ssi', $name, $detail, $id);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => (bool)$ok]);
  if (!defined('UNIT_TESTING')) exit; 
}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) { echo json_encode(['success' => false, 'msg' => 'Invalid id']); if (!defined('UNIT_TESTING')) exit; }
  $stmt = $conn->prepare('DELETE FROM inf_ti_items WHERE id = ?');
  $stmt->bind_param('i', $id);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => (bool)$ok]);
  if (!defined('UNIT_TESTING')) exit; 
}

// Export CSV
if ($action === 'export') {
  $ids = $_GET['ids'] ?? null; // comma-separated or absent for all
  $cat = $_GET['category'] ?? null;
  $query = 'SELECT id, category, name, detail, created_at, updated_at FROM inf_ti_items';
  $conds = [];
  if ($ids) {
    $allowed = array_filter(array_map('intval', explode(',', $ids)));
    if (count($allowed) === 0) { inf_api_output(['success'=>false,'msg'=>'No ids']); if (!defined('UNIT_TESTING')) exit; }
    $query .= ' WHERE id IN (' . implode(',', $allowed) . ')';
  } elseif ($cat) {
    $catEsc = $conn->real_escape_string($cat);
    $query .= " WHERE category = '" . $catEsc . "'";
  }
  if ($hasSortOrder) $query .= ' ORDER BY sort_order ASC, id ASC'; else $query .= ' ORDER BY id ASC';
  $res = $conn->query($query);
  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  // build CSV with selected columns
  $colsParam = $_GET['cols'] ?? $_GET['columns'] ?? null;
  $allowedCols = ['id','category','name','detail','created_at','updated_at'];
  if ($colsParam) {
    $cols = array_filter(array_map('trim', explode(',', $colsParam)));
    $cols = array_values(array_intersect($allowedCols, $cols));
    if (count($cols) === 0) $cols = $allowedCols;
  } else {
    $cols = $allowedCols;
  }
  // header
  $csv = implode(',', $cols) . "\n";
  foreach ($rows as $r) {
    $line = [];
    foreach ($cols as $c) {
      $val = $r[$c] ?? '';
      $val = str_replace('"','""', $val);
      $line[] = $val;
    }
    $csv .= '"' . implode('","', $line) . '"' . "\n";
  }
  if (defined('UNIT_TESTING')) { inf_api_output(['success'=>true,'csv'=>$csv,'count'=>count($rows),'cols'=>$cols]); if (!defined('UNIT_TESTING')) exit; }
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="inf_ti_export.csv"');
  echo $csv;
  exit;
}

// Bulk delete
if ($action === 'bulk_delete') {
  $ids = $_POST['ids'] ?? null; // array
  if (!is_array($ids) || count($ids) === 0) { echo json_encode(['success'=>false,'msg'=>'No ids']); if (!defined('UNIT_TESTING')) exit; }
  $allowed = array_filter(array_map('intval',$ids));
  if (count($allowed) === 0) { echo json_encode(['success'=>false,'msg'=>'No valid ids']); if (!defined('UNIT_TESTING')) exit; }
  $in = implode(',', $allowed);
  $ok = $conn->query("DELETE FROM inf_ti_items WHERE id IN ($in)");
  echo json_encode(['success' => (bool)$ok, 'deleted' => $conn->affected_rows]);
  if (!defined('UNIT_TESTING')) exit;
}

// Save order (admin only)
if ($action === 'save_order') {
  $category = $_POST['category'] ?? '';
  $ids = $_POST['ids'] ?? null; // array expected
  if (!in_array($category, $allowedCategories)) { inf_api_output(['success'=>false,'msg'=>'Invalid category']); }
  if (!$isAdmin) { inf_api_output(['success'=>false,'msg'=>'Admin required']); }
  if (!is_array($ids) || count($ids) === 0) { inf_api_output(['success'=>false,'msg'=>'No ids provided']); }
  $allowed = array_map('intval', $ids);
  // update sort_order sequentially for provided ids
  $stmt = $conn->prepare('UPDATE inf_ti_items SET sort_order = ? WHERE id = ? AND category = ?');
  $i = 1;
  $updated = 0;
  foreach ($allowed as $id) {
    $stmt->bind_param('iis', $i, $id, $category);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $updated++;
    $i++;
  }
  $stmt->close();
  inf_api_output(['success'=>true,'updated'=>$updated]);
  if (!defined('UNIT_TESTING')) exit;
}

echo json_encode(['success' => false, 'msg' => 'Unknown action']);
