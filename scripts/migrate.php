<?php
// scripts/migrate.php
// Simple migration runner for sapps
// Usage:
//   php scripts/migrate.php          # apply pending migrations
//   php scripts/migrate.php --pretend # show pending migrations without applying
//   php scripts/migrate.php --list    # list all migrations and status
//   php scripts/migrate.php --target=002_create_users.sql # apply up to and including this file
//   php scripts/migrate.php --backup  # create a mysqldump backup before applying pending migrations
//   php scripts/migrate.php --rollback [--force] # restore the most recent backuped batch and remove its migration records

require __DIR__ . '/../php/db_connect.php';

$opts = getopt('', ['pretend', 'target:', 'list', 'help', 'backup', 'rollback', 'force']);
$pretend = isset($opts['pretend']);
$listOnly = isset($opts['list']);
$target = $opts['target'] ?? null;
$doBackup = isset($opts['backup']);
$doRollback = isset($opts['rollback']);
$force = isset($opts['force']);

$migrationsDir = __DIR__ . '/../migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

// ensure migrations table exists
$createMigrationsTable = "CREATE TABLE IF NOT EXISTS migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  checksum VARCHAR(64) NOT NULL,
  applied_at DATETIME NOT NULL,
  batch INT NOT NULL,
  backup_file VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY uniq_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($createMigrationsTable)) {
    fwrite(STDERR, "Failed to create migrations table: (" . $conn->errno . ") " . $conn->error . "\n");
    exit(1);
}
// Ensure backup_file column exists for older installations
$colRes = $conn->query("SHOW COLUMNS FROM migrations LIKE 'backup_file'");
if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE migrations ADD COLUMN backup_file VARCHAR(255) DEFAULT NULL");
}

// load migration files
$files = glob($migrationsDir . '/*.{sql,php}', GLOB_BRACE);
sort($files, SORT_NATURAL);
$files = array_map(function($f){ return str_replace('\\', '/', basename($f)); }, $files);

// load applied map
$applied = [];
$res = $conn->query("SELECT filename, checksum, batch FROM migrations ORDER BY applied_at ASC, id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $applied[$r['filename']] = $r;
    }
}

// build list of migrations with status
$migs = [];
foreach ($files as $fname) {
    $path = $migrationsDir . '/' . $fname;
    $content = file_get_contents($path);
    $checksum = hash('sha256', $content ?: '');
    $isApplied = isset($applied[$fname]);
    $migs[] = ['filename' => $fname, 'path' => $path, 'checksum' => $checksum, 'applied' => $isApplied, 'applied_record' => $isApplied ? $applied[$fname] : null];
}

if ($listOnly) {
    echo "Migrations list:\n";
    foreach ($migs as $m) {
        echo ($m['applied'] ? ' [X] ' : ' [ ] ') . $m['filename'];
        if ($m['applied']) echo " (batch " . $m['applied_record']['batch'] . ", checksum " . $m['applied_record']['checksum'] . ")";
        echo "\n";
    }
    exit(0);
}

// If rollback requested, restore latest backup for the most recent batch that has a backup_file recorded
if ($doRollback) {
    $rbRes = $conn->query("SELECT batch, backup_file FROM migrations WHERE backup_file IS NOT NULL ORDER BY applied_at DESC LIMIT 1");
    if (!$rbRes || $rbRes->num_rows === 0) {
        fwrite(STDERR, "No backups found in migrations table to rollback. Aborting.\n");
        exit(1);
    }
    $rb = $rbRes->fetch_assoc();
    $batchToRollback = intval($rb['batch']);
    $backupFile = $rb['backup_file'];
    if (!file_exists($backupFile)) {
        fwrite(STDERR, "Backup file not found: $backupFile\n");
        exit(1);
    }
    if (!$force) {
        echo "About to restore backup '$backupFile' which will overwrite database '{$db}'. Proceed? (y/N): ";
        $ans = trim(fgets(STDIN));
        if (strtolower($ans) !== 'y') {
            echo "Aborted by user.\n";
            exit(0);
        }
    }
    // attempt restore using mysql CLI
    $cmd = 'mysql -h ' . escapeshellarg($host) . ' -u ' . escapeshellarg($user) . ' ' . (!empty($pass) ? ('-p' . escapeshellarg($pass)) : '') . ' ' . escapeshellarg($db) . ' < ' . escapeshellarg($backupFile) . ' 2>&1';
    echo "Restoring from backup...\n";
    exec($cmd, $output, $rv);
    if ($rv !== 0) {
        fwrite(STDERR, "Restore failed: " . implode("\n", $output) . "\n");
        exit(1);
    }
    // delete migration records for that batch
    if ($conn->query("DELETE FROM migrations WHERE batch = " . intval($batchToRollback))) {
        echo "Rollback successful. Removed migrations for batch $batchToRollback.\n";
        exit(0);
    } else {
        fwrite(STDERR, "Failed to remove migrations records for batch $batchToRollback: (" . $conn->errno . ") " . $conn->error . "\n");
        exit(1);
    }
}

// determine pending migrations up to target
$pending = [];
foreach ($migs as $m) {
    if ($m['applied']) {
        // verify checksum match
        if ($m['applied_record']['checksum'] !== $m['checksum']) {
            fwrite(STDERR, "Checksum mismatch for applied migration {$m['filename']}. Applied checksum: {$m['applied_record']['checksum']}, current checksum: {$m['checksum']}. Aborting.\n");
            exit(1);
        }
        continue;
    }
    $pending[] = $m;
    if ($target && ($m['filename'] === $target || strpos($m['filename'], $target) !== false)) break;
}

if (empty($pending)) {
    echo $pretend ? "No pending migrations.\n" : "Nothing to do. DB up-to-date.\n";
    exit(0);
}

if ($pretend) {
    echo "Pending migrations (would apply):\n";
    foreach ($pending as $p) echo " - " . $p['filename'] . "\n";
    exit(0);
}

// determine next batch number
$batchRes = $conn->query("SELECT MAX(batch) as mb FROM migrations");
$batchRow = $batchRes ? $batchRes->fetch_assoc() : null;
$nextBatch = ($batchRow && $batchRow['mb']) ? (intval($batchRow['mb']) + 1) : 1;

// create backup if requested
$backupFileForBatch = '';
if ($doBackup) {
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    $timestamp = date('Ymd_His');
    $backupFileForBatch = $backupDir . '/sapps_backup_' . $timestamp . '_batch' . $nextBatch . '.sql';
    echo "Creating backup to $backupFileForBatch...\n";
    $cmd = 'mysqldump -h ' . escapeshellarg($host) . ' -u ' . escapeshellarg($user) . ' ' . (!empty($pass) ? ('-p' . escapeshellarg($pass)) : '') . ' ' . escapeshellarg($db) . ' > ' . escapeshellarg($backupFileForBatch) . ' 2>&1';
    exec($cmd, $output, $rv);
    if ($rv !== 0) {
        fwrite(STDERR, "Backup failed: " . implode("\n", $output) . "\n");
        exit(1);
    }
    echo "Backup created successfully.\n";
}

foreach ($pending as $m) {
    echo "Applying {$m['filename']}... ";
    $ext = pathinfo($m['filename'], PATHINFO_EXTENSION);
    $path = $m['path'];
    $content = file_get_contents($path);

    if ($ext === 'sql') {
        if (!$conn->multi_query($content)) {
            fwrite(STDERR, "FAILED: (" . $conn->errno . ") " . $conn->error . "\n");
            exit(1);
        }
        // flush results
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } elseif ($ext === 'php') {
        // include file; expect it to either execute on include or define a function up(
        $ret = null;
        try {
            $ret = include $path;
            // also support quoted return as callable
            if (is_callable($ret)) {
                $call = $ret;
                $call($conn);
            }
        } catch (Throwable $e) {
            fwrite(STDERR, "FAILED: PHP migration threw exception: " . $e->getMessage() . "\n");
            exit(1);
        }
    } else {
        fwrite(STDERR, "Skipping unknown migration file type: {$m['filename']}\n");
        continue;
    }

    // record applied (include backup file info if present)
    $stmt = $conn->prepare("INSERT INTO migrations (filename, checksum, applied_at, batch, backup_file) VALUES (?, ?, NOW(), ?, ?)");
    $backupVal = $backupFileForBatch ?: null;
    $stmt->bind_param('ssis', $m['filename'], $m['checksum'], $nextBatch, $backupVal);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to record migration {$m['filename']}: (" . $stmt->errno . ") " . $stmt->error . "\n");
        exit(1);
    }
    echo "OK\n";
}

echo "All done. Applied batch {$nextBatch} with " . count($pending) . " migration(s).\n";
exit(0);
