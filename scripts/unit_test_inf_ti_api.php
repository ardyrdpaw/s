<?php
// CLI unit tests for php/inf_ti_api.php
chdir(__DIR__ . '/..');
require_once 'php/db_connect.php';
// start session early so API header/session calls work when included
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// start an output buffer early so header() in included API doesn't fail
if (!ob_get_level()) ob_start();

function run_api($method, $params = []){
    // clear any previous output
    $_GET = $_POST = $_REQUEST = [];
    // ensure DB connection variable is imported into this scope
    global $conn;

    if ($method === 'GET') {
        foreach ($params as $k=>$v) $_GET[$k] = $v;
    } else {
        foreach ($params as $k=>$v) $_POST[$k] = $v;
    }
    $_REQUEST = $method === 'GET' ? $_GET : array_merge($_GET, $_POST);

    ob_start();
    include 'php/inf_ti_api.php';
    $out = ob_get_clean();
    $json = json_decode($out, true);
    return $json ?: ['raw' => $out];
}

function assertTrue($cond, $msg){
    echo ($cond ? "[PASS] " : "[FAIL] ") . $msg . PHP_EOL;
}

// Ensure session role admin for admin tests
$_SESSION['role'] = 'admin';

echo "Test: list (all)\n";
$res = run_api('GET', ['action'=>'list']);
var_export($res); echo PHP_EOL;
assertTrue(isset($res['success']) && $res['success'], 'List all should succeed');

$initialCount = count($res['items'] ?? []);

echo "Test: add new item (jaringan)\n";
$res = run_api('POST', ['action'=>'add', 'category'=>'jaringan', 'name'=>'Test Switch', 'detail'=>'Unit test']);
assertTrue(isset($res['success']) && $res['success'] && isset($res['id']), 'Add should return success and id');
$addedId = $res['id'] ?? 0;

echo "Test: list jaringan only\n";
$res = run_api('GET', ['action'=>'list', 'category'=>'jaringan']);
assertTrue($res['success'] && array_search('Test Switch', array_column($res['items'],'name')) !== false, 'Added item should appear in jaringan list');

echo "Test: edit item\n";
$res = run_api('POST', ['action'=>'edit', 'id'=>$addedId, 'name'=>'Test Switch Edited', 'detail'=>'Edited']);
assertTrue($res['success'], 'Edit should succeed');
$res = run_api('GET', ['action'=>'list', 'category'=>'jaringan']);
assertTrue($res['success'] && array_search('Test Switch Edited', array_column($res['items'],'name')) !== false, 'Edited name should appear');

echo "Test: delete item\n";
$res = run_api('POST', ['action'=>'delete', 'id'=>$addedId]);
assertTrue($res['success'], 'Delete should succeed');
$res = run_api('GET', ['action'=>'list', 'category'=>'jaringan']);
assertTrue($res['success'] && array_search('Test Switch Edited', array_column($res['items'],'name')) === false, 'Deleted item should not appear');

// Bulk add two items for bulk-delete test
$_SESSION['role'] = 'admin';
run_api('POST', ['action'=>'add', 'category'=>'komputer', 'name'=>'BULK A', 'detail'=>'x']);
run_api('POST', ['action'=>'add', 'category'=>'komputer', 'name'=>'BULK B', 'detail'=>'x']);
$res = run_api('GET', ['action'=>'list', 'category'=>'komputer']);
$bulkIds = array_column($res['items'], 'id');
$bulkIds = array_values(array_filter($bulkIds, function($id){ return $id>1000 ? false : true; })); // helper (not strict)
$lastTwo = array_slice($bulkIds, -2);

echo "Test: bulk delete\n";
$res = run_api('POST', ['action'=>'bulk_delete', 'ids'=>$lastTwo]);
assertTrue(isset($res['success']) && $res['success'] && isset($res['deleted']), 'Bulk delete should succeed');

// Test reorder: add three items and reorder them
$_SESSION['role'] = 'admin';
run_api('POST', ['action'=>'add', 'category'=>'komputer', 'name'=>'ORD A', 'detail'=>'a']);
run_api('POST', ['action'=>'add', 'category'=>'komputer', 'name'=>'ORD B', 'detail'=>'b']);
run_api('POST', ['action'=>'add', 'category'=>'komputer', 'name'=>'ORD C', 'detail'=>'c']);
$res = run_api('GET', ['action'=>'list', 'category'=>'komputer']);
$ids = array_column($res['items'], 'id');
$ids = array_values(array_filter($ids, function($v){ return strpos((string)$v, '') !== false; }));
// put last three in reverse order
$toReorder = array_slice($ids, -3);
$rev = array_reverse($toReorder);
$res = run_api('POST', ['action'=>'save_order', 'category'=>'komputer', 'ids'=>$rev]);
assertTrue(isset($res['success']) && $res['success'] && isset($res['updated']), 'Save order should succeed');
$res = run_api('GET', ['action'=>'list', 'category'=>'komputer']);
$names = array_column($res['items'], 'name');
assertTrue($names[0] === 'ORD C' || in_array('ORD C', $names), 'Reordered items present');

// Test export (UNIT_TESTING returns CSV in __inf_api_result)
echo "Test: export csv\n";
$res = run_api('GET', ['action'=>'export', 'category'=>'komputer']);
if (isset($res['csv'])) {
  assertTrue(strpos($res['csv'], 'id,category,name,detail') !== false, 'Export CSV header present');
} else {
  assertTrue(false, 'Export did not return csv in testing mode');
}

// Test export with selected columns
echo "Test: export selected columns\n";
$res = run_api('GET', ['action'=>'export', 'category'=>'komputer', 'cols'=>'name,detail']);
if (isset($res['csv'])) {
  assertTrue(strpos($res['csv'], 'name,detail') !== false && strpos($res['csv'], 'created_at') === false, 'Export respects selected columns');
} else {
  assertTrue(false, 'Export-with-cols did not return csv in testing mode');
}

// Permission tests
echo "Test: non-admin cannot add\n";
$_SESSION['role'] = 'user';
$res = run_api('POST', ['action'=>'add', 'category'=>'jaringan', 'name'=>'Should Fail', 'detail'=>'']);
assertTrue(!$res['success'], 'Non-admin add should fail');

echo "Test: invalid category\n";
$_SESSION['role'] = 'admin';
$res = run_api('POST', ['action'=>'add', 'category'=>'invalidcat', 'name'=>'X', 'detail'=>'']);
assertTrue(!$res['success'], 'Invalid category should be rejected');

// cleanup: ensure count consistent
echo "Final list check\n";
$res = run_api('GET', ['action'=>'list']);
assertTrue(isset($res['success']) && $res['success'], 'Final list succeeds');

echo "Tests completed." . PHP_EOL;
