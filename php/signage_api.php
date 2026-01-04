<?php
session_start(); include_once __DIR__ . '/auth.php'; require_login(); require_menu_access('signage');
include 'db_connect.php';
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

// Ensure new columns exist (autoplay, loop, muted, category) to avoid query errors
$colAuto = $conn->query("SHOW COLUMNS FROM signage_items LIKE 'autoplay'");
if ($colAuto && $colAuto->num_rows === 0) {
    $conn->query("ALTER TABLE signage_items ADD COLUMN autoplay TINYINT(1) DEFAULT 0 AFTER type");
}
$colLoop = $conn->query("SHOW COLUMNS FROM signage_items LIKE 'loop'");
if ($colLoop && $colLoop->num_rows === 0) {
    $conn->query("ALTER TABLE signage_items ADD COLUMN `loop` TINYINT(1) DEFAULT 0 AFTER autoplay");
}
$colMuted = $conn->query("SHOW COLUMNS FROM signage_items LIKE 'muted'");
if ($colMuted && $colMuted->num_rows === 0) {
    $conn->query("ALTER TABLE signage_items ADD COLUMN muted TINYINT(1) DEFAULT 1 AFTER `loop`");
}
$colCategory = $conn->query("SHOW COLUMNS FROM signage_items LIKE 'category'");
if ($colCategory && $colCategory->num_rows === 0) {
    $conn->query("ALTER TABLE signage_items ADD COLUMN category VARCHAR(64) DEFAULT '' AFTER type");
}

switch ($action) {
    case 'list':
        $items = [];
        $result = $conn->query('SELECT id, name, content, type, category, autoplay, `loop`, muted FROM signage_items');
        if ($result && $result->num_rows === 0) {
            $conn->query("INSERT INTO signage_items (name, content, type, category, autoplay, `loop`, muted) VALUES
                ('Welcome 1', 'Welcome to BKPSDM', 'Text', 'Text', 0, 0, 0),
                ('Welcome 2', 'Thank You for Visiting', 'Text', 'Text', 0, 0, 0),
                ('Video 1', 'assets/uploads/video1.mp4', 'Video', 'Video', 1, 1, 1),
                ('Galeri 1', 'assets/uploads/gallery1.jpg', 'Images', 'Galeri', 0, 0, 0)");
            $result = $conn->query('SELECT id, name, content, type, category, autoplay, `loop`, muted FROM signage_items');
        }
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        echo json_encode(['data' => $items]);
        break;
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        $result = $conn->query("SELECT id, name, content, type, category, autoplay, `loop`, muted FROM signage_items WHERE id=$id");
        $item = $result ? $result->fetch_assoc() : null;
        echo json_encode(['data' => $item]);
        break;
    case 'add':
        $name = $conn->real_escape_string($_POST['name']);
        $content = $conn->real_escape_string($_POST['content']);
        $type = $conn->real_escape_string($_POST['type']);
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        $loop = isset($_POST['loop']) ? 1 : 0;
        $muted = isset($_POST['muted']) ? 1 : 0;
        
        // Handle file upload
        if (!empty($_FILES['file']['name'])) {
            $uploadDir = '../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $file = $_FILES['file'];
            $fileName = time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $content = 'assets/uploads/' . $fileName;
            }
        }
        
        // fallback: if category empty, derive from type for backward compatibility
        if (empty($category)) {
            if (strtolower($type) === 'video') $category = 'Video';
            elseif (strtolower($type) === 'images') $category = 'Galeri';
            else $category = 'Text';
        }
        
        $conn->query("INSERT INTO signage_items (name, content, type, category, autoplay, `loop`, muted) VALUES ('$name', '$content', '$type', '$category', $autoplay, $loop, $muted)");
        echo json_encode(['success' => true]);
        break;
    case 'edit':
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $content = $conn->real_escape_string($_POST['content']);
        $type = $conn->real_escape_string($_POST['type']);
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        $loop = isset($_POST['loop']) ? 1 : 0;
        $muted = isset($_POST['muted']) ? 1 : 0;
        
        // Handle file upload
        if (!empty($_FILES['file']['name'])) {
            $uploadDir = '../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $file = $_FILES['file'];
            $fileName = time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $content = 'assets/uploads/' . $fileName;
            }
        }
        
        if (empty($category)) {
            if (strtolower($type) === 'video') $category = 'Video';
            elseif (strtolower($type) === 'images') $category = 'Galeri';
            else $category = 'Text';
        }
        
        $conn->query("UPDATE signage_items SET name='$name', content='$content', type='$type', category='$category', autoplay=$autoplay, `loop`=$loop, muted=$muted WHERE id=$id");
        echo json_encode(['success' => true]);
        break;
    case 'delete':
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM signage_items WHERE id=$id");
        echo json_encode(['success' => true]);
        break;
    case 'get_clock_format':
        $res = $conn->query("SELECT content FROM signage_items WHERE name='SaviewClockFormat' LIMIT 1");
        $defaultFormats = [
            ['key' => 'long', 'label' => 'Long — Rabu, 3 Januari 2026 14:05:06', 'type' => 'builtin'],
            ['key' => 'short', 'label' => 'Short — 03/01/2026 14:05', 'type' => 'builtin'],
            ['key' => 'time', 'label' => 'Time only — 14:05:06', 'type' => 'builtin'],
            ['key' => 'date', 'label' => 'Date only — 3 Januari 2026', 'type' => 'builtin'],
            ['key' => 'iso', 'label' => 'ISO — 2026-01-03 14:05:06', 'type' => 'builtin'],
            ['key' => 'weekday_short', 'label' => 'Weekday short — Rab, 3 Jan 2026 14:05', 'type' => 'builtin']
        ];
        $out = ['selected' => 'long', 'formats' => $defaultFormats];
        if ($res && $row = $res->fetch_assoc()) {
            $content = trim($row['content']);
            // stored as JSON with selected+formats or simple selected key from older versions
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['selected']) && isset($decoded['formats'])) {
                $out = $decoded;
            } elseif ($content !== '') {
                $out['selected'] = $content;
            }
        } else {
            // insert defaults
            $json = $conn->real_escape_string(json_encode($out));
            $conn->query("INSERT INTO signage_items (name, content, type, autoplay, `loop`, muted) VALUES ('SaviewClockFormat', '$json', 'Setting', 0, 0, 0)");
        }
        echo json_encode($out);
        break;
    case 'set_clock_format':
        // Support two modes:
        // - set selected format: POST 'format' => key
        // - save full formats list: POST 'formats' => JSON array and optional 'selected'
        if (isset($_POST['formats'])) {
            $formatsRaw = $_POST['formats'];
            // ensure it's valid JSON string
            $decoded = json_decode($formatsRaw, true);
            if (!is_array($decoded)) {
                // maybe it's already decoded by jQuery as array
                $decoded = $formatsRaw;
            }
            $selected = $_POST['selected'] ?? ($decoded['selected'] ?? 'long');
            $toStore = ['selected' => $selected, 'formats' => $decoded['formats'] ?? $decoded];
            $json = $conn->real_escape_string(json_encode($toStore));
            $res = $conn->query("SELECT id FROM signage_items WHERE name='SaviewClockFormat' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $conn->query("UPDATE signage_items SET content='$json', type='Setting' WHERE name='SaviewClockFormat'");
            } else {
                $conn->query("INSERT INTO signage_items (name, content, type, autoplay, `loop`, muted) VALUES ('SaviewClockFormat', '$json', 'Setting', 0, 0, 0)");
            }
            echo json_encode(['success' => true]);
            break;
        }

        $format = $conn->real_escape_string($_POST['format'] ?? 'long');
        $res = $conn->query("SELECT id, content FROM signage_items WHERE name='SaviewClockFormat' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $content = $row['content'];
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['formats'])) {
                $decoded['selected'] = $format;
                $json = $conn->real_escape_string(json_encode($decoded));
                $conn->query("UPDATE signage_items SET content='$json', type='Setting' WHERE name='SaviewClockFormat'");
            } else {
                $conn->query("UPDATE signage_items SET content='$format', type='Setting' WHERE name='SaviewClockFormat'");
            }
        } else {
            $conn->query("INSERT INTO signage_items (name, content, type, autoplay, `loop`, muted) VALUES ('SaviewClockFormat', '$format', 'Setting', 0, 0, 0)");
        }
        echo json_encode(['success' => true]);
        break;
    case 'get_server_time':
        // Return server-formatted time according to stored formats
        // Optional GET param: format=key to preview a specific format
        $previewKey = isset($_GET['format']) ? $_GET['format'] : null;
        $res = $conn->query("SELECT content FROM signage_items WHERE name='SaviewClockFormat' LIMIT 1");
        $clock = null;
        if ($res && $row = $res->fetch_assoc()) {
            $decoded = json_decode($row['content'], true);
            if (is_array($decoded) && isset($decoded['selected']) && isset($decoded['formats'])) {
                $clock = $decoded;
            } else {
                $clock = ['selected' => $row['content'], 'formats' => []];
            }
        } else {
            $clock = ['selected' => 'long', 'formats' => []];
        }
        $selected = $clock['selected'] ?? 'long';
        $formats = $clock['formats'] ?? [];
        // determine which key to render
        $keyToUse = $previewKey ?: $selected;
        $formatDef = null;
        foreach ($formats as $f) {
            if (($f['key'] ?? '') === $keyToUse) { $formatDef = $f; break; }
        }
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $outStr = '';
        if ($formatDef && isset($formatDef['type']) && $formatDef['type'] === 'custom' && !empty($formatDef['pattern'])) {
            if (class_exists('IntlDateFormatter')) {
                try {
                    $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM, 'Asia/Jakarta', IntlDateFormatter::GREGORIAN, $formatDef['pattern']);
                    $outStr = $fmt->format($now);
                } catch (Exception $e) {
                    $outStr = $now->format('Y-m-d H:i:s');
                }
            } else {
                $outStr = $now->format('Y-m-d H:i:s');
            }
        } else {
            // handle known builtins by key
            switch ($keyToUse) {
                case 'short': $outStr = $now->format('d/m/Y H:i'); break;
                case 'time': $outStr = $now->format('H:i:s'); break;
                case 'date': $outStr = $now->format('j F Y'); break;
                case 'iso': $outStr = $now->format('Y-m-d H:i:s'); break;
                case 'weekday_short': $outStr = $now->format('D, j M Y H:i'); break;
                default:
                    // long or unknown
                    if (class_exists('IntlDateFormatter')) {
                        $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM, 'Asia/Jakarta', IntlDateFormatter::GREGORIAN, "EEEE, d MMMM yyyy HH:mm:ss");
                        $outStr = $fmt->format($now);
                    } else {
                        $outStr = $now->format('l, j F Y H:i:s');
                    }
                    break;
            }
        }
        echo json_encode(['time' => $outStr]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>