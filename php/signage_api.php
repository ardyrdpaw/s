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
$colSortOrder = $conn->query("SHOW COLUMNS FROM signage_items LIKE 'sort_order'");
if ($colSortOrder && $colSortOrder->num_rows === 0) {
    $conn->query("ALTER TABLE signage_items ADD COLUMN sort_order INT DEFAULT 0 AFTER muted");
}

// Ensure activities table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'activities'");
if ($tableExists && $tableExists->num_rows === 0) {
    $conn->query("CREATE TABLE activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no INT,
        kegiatan VARCHAR(255) NOT NULL,
        tempat VARCHAR(255),
        waktu VARCHAR(255),
        tahun INT,
        bulan VARCHAR(50),
        status VARCHAR(50),
        category ENUM('Kegiatan', 'Agenda') DEFAULT 'Kegiatan',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} else {
    // Migrate existing table if needed
    $hasNewCols = $conn->query("SHOW COLUMNS FROM activities LIKE 'kegiatan'");
    if ($hasNewCols && $hasNewCols->num_rows === 0) {
        $conn->query("ALTER TABLE activities 
            ADD COLUMN no INT AFTER id,
            ADD COLUMN kegiatan VARCHAR(255) AFTER no,
            ADD COLUMN tempat VARCHAR(255) AFTER kegiatan,
            ADD COLUMN waktu VARCHAR(255) AFTER tempat");
    }
    // Add tahun and bulan columns if they don't exist
    $hasTahun = $conn->query("SHOW COLUMNS FROM activities LIKE 'tahun'");
    if ($hasTahun && $hasTahun->num_rows === 0) {
        $conn->query("ALTER TABLE activities ADD COLUMN tahun INT AFTER waktu");
    }
    $hasBulan = $conn->query("SHOW COLUMNS FROM activities LIKE 'bulan'");
    if ($hasBulan && $hasBulan->num_rows === 0) {
        $conn->query("ALTER TABLE activities ADD COLUMN bulan VARCHAR(50) AFTER tahun");
    }
}

switch ($action) {
    case 'list':
        $items = [];
        $result = $conn->query('SELECT id, name, content, type, category, autoplay, `loop`, muted, sort_order FROM signage_items ORDER BY sort_order ASC, id ASC');
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
        $result = $conn->query("SELECT id, name, content, type, category, autoplay, `loop`, muted, sort_order FROM signage_items WHERE id=$id");
        $item = $result ? $result->fetch_assoc() : null;
        echo json_encode(['data' => $item]);
        break;
    case 'add':
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $content = $conn->real_escape_string($_POST['content'] ?? '');
        $type = $conn->real_escape_string($_POST['type'] ?? '');
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        $loop = isset($_POST['loop']) ? 1 : 0;
        $muted = isset($_POST['muted']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
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
        
        $result = $conn->query("INSERT INTO signage_items (name, content, type, category, autoplay, `loop`, muted, sort_order) VALUES ('$name', '$content', '$type', '$category', $autoplay, $loop, $muted, $sort_order)");
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $content = $conn->real_escape_string($_POST['content'] ?? '');
        $type = $conn->real_escape_string($_POST['type'] ?? '');
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        $loop = isset($_POST['loop']) ? 1 : 0;
        $muted = isset($_POST['muted']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
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
        
        $result = $conn->query("UPDATE signage_items SET name='$name', content='$content', type='$type', category='$category', autoplay=$autoplay, `loop`=$loop, muted=$muted, sort_order=$sort_order WHERE id=$id");
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
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
    case 'get_slideshow_settings':
        // Get slideshow settings (stored as JSON in a special signage item)
        $res = $conn->query("SELECT content FROM signage_items WHERE name='SlideshowSettings' LIMIT 1");
        $defaultSettings = [
            'timeout' => 5000,
            'transition' => 'fade'
        ];
        if ($res && $row = $res->fetch_assoc()) {
            $decoded = json_decode($row['content'], true);
            if (is_array($decoded)) {
                echo json_encode(['success' => true, 'settings' => $decoded]);
            } else {
                echo json_encode(['success' => true, 'settings' => $defaultSettings]);
            }
        } else {
            // Insert defaults
            $json = $conn->real_escape_string(json_encode($defaultSettings));
            $conn->query("INSERT INTO signage_items (name, content, type) VALUES ('SlideshowSettings', '$json', 'Setting')");
            echo json_encode(['success' => true, 'settings' => $defaultSettings]);
        }
        break;
    case 'set_slideshow_settings':
        $timeout = intval($_POST['timeout'] ?? 5000);
        $transition = $conn->real_escape_string($_POST['transition'] ?? 'fade');
        $settings = ['timeout' => $timeout, 'transition' => $transition];
        $json = $conn->real_escape_string(json_encode($settings));
        
        $res = $conn->query("SELECT id FROM signage_items WHERE name='SlideshowSettings' LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $conn->query("UPDATE signage_items SET content='$json', type='Setting' WHERE name='SlideshowSettings'");
        } else {
            $conn->query("INSERT INTO signage_items (name, content, type) VALUES ('SlideshowSettings', '$json', 'Setting')");
        }
        echo json_encode(['success' => true]);
        break;
    case 'get_gallery_images':
        // Get all images in Galeri category ordered by sort_order
        $category = $conn->real_escape_string($_GET['category'] ?? 'Galeri');
        $items = [];
        $result = $conn->query("SELECT id, name, content, type, category, sort_order FROM signage_items WHERE category='$category' AND type='Images' ORDER BY sort_order ASC, id ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['content'])) {
                    $items[] = $row;
                }
            }
        }
        echo json_encode(['success' => true, 'images' => $items]);
        break;
    case 'list_activities':
        // Get activities for current month
        $category = $conn->real_escape_string($_GET['category'] ?? 'Kegiatan');
        $items = [];
        $result = $conn->query("SELECT * FROM activities WHERE category='$category' ORDER BY no ASC, id ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        echo json_encode(['success' => true, 'activities' => $items]);
        break;
    case 'get_activity':
        $id = intval($_GET['id'] ?? 0);
        $result = $conn->query("SELECT * FROM activities WHERE id=$id");
        $item = $result ? $result->fetch_assoc() : null;
        echo json_encode(['success' => true, 'data' => $item]);
        break;
    case 'add_activity':
        $no = intval($_POST['no'] ?? 0);
        $kegiatan = $conn->real_escape_string($_POST['kegiatan'] ?? '');
        $tempat = $conn->real_escape_string($_POST['tempat'] ?? '');
        $waktu = $conn->real_escape_string($_POST['waktu'] ?? '');
        $tahun = intval($_POST['tahun'] ?? 0);
        $bulan = $conn->real_escape_string($_POST['bulan'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? '');
        $category = $conn->real_escape_string($_POST['category'] ?? 'Kegiatan');
        
        $result = $conn->query("INSERT INTO activities (no, kegiatan, tempat, waktu, tahun, bulan, status, category) VALUES ($no, '$kegiatan', '$tempat', '$waktu', $tahun, '$bulan', '$status', '$category')");
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
    case 'edit_activity':
        $id = intval($_POST['id'] ?? 0);
        $no = intval($_POST['no'] ?? 0);
        $kegiatan = $conn->real_escape_string($_POST['kegiatan'] ?? '');
        $tempat = $conn->real_escape_string($_POST['tempat'] ?? '');
        $waktu = $conn->real_escape_string($_POST['waktu'] ?? '');
        $tahun = intval($_POST['tahun'] ?? 0);
        $bulan = $conn->real_escape_string($_POST['bulan'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? '');
        $category = $conn->real_escape_string($_POST['category'] ?? 'Kegiatan');
        
        $result = $conn->query("UPDATE activities SET no=$no, kegiatan='$kegiatan', tempat='$tempat', waktu='$waktu', tahun=$tahun, bulan='$bulan', status='$status', category='$category' WHERE id=$id");
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
    case 'delete_activity':
        $id = intval($_POST['id'] ?? 0);
        $result = $conn->query("DELETE FROM activities WHERE id=$id");
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>