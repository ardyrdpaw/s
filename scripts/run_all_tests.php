<?php
// Run all unit and integration tests
echo "=== Running Full Test Suite ===\n\n";

$tests = [
    'test_login_flow.php' => 'Login Flow Test',
    'unit_test_user_username_unique.php' => 'Username Uniqueness Test',
    'unit_test_user_preferences.php' => 'User Preferences Test',
    'e2e_menu_visibility.php' => 'Menu Visibility E2E Test',
];

$passed = 0;
$failed = 0;

foreach ($tests as $script => $label) {
    echo "Running: $label...\n";
    $output = shell_exec('php ' . __DIR__ . '/' . $script . ' 2>&1');
    if (strpos($output, '[PASS]') !== false && strpos($output, '[FAIL]') === false) {
        echo "✓ PASSED\n";
        $passed++;
    } else {
        echo "✗ FAILED\n";
        echo $output . "\n";
        $failed++;
    }
    echo "\n";
}

echo "=== Test Summary ===\n";
echo "Passed: $passed / " . count($tests) . "\n";
echo "Failed: $failed / " . count($tests) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}
?>
