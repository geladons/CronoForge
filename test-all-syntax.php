<?php
/**
 * Test all ChronoForge files for syntax errors
 */

echo "ChronoForge Complete Syntax Test\n";
echo "================================\n\n";

// Files to test in order of dependency
$files_to_test = array(
    'chrono-forge/chrono-forge.php' => 'Main plugin file',
    'chrono-forge/includes/utils/functions.php' => 'Utility functions',
    'chrono-forge/includes/class-chrono-forge-database.php' => 'Database class',
    'chrono-forge/includes/class-chrono-forge-activator.php' => 'Activator class',
    'chrono-forge/includes/class-chrono-forge-deactivator.php' => 'Deactivator class',
    'chrono-forge/includes/class-chrono-forge-core.php' => 'Core class',
    'chrono-forge/includes/class-chrono-forge-ajax-handler.php' => 'AJAX handler',
    'chrono-forge/includes/class-chrono-forge-shortcodes.php' => 'Shortcodes class',
    'chrono-forge/includes/class-chrono-forge-diagnostics.php' => 'Diagnostics class',
    'chrono-forge/admin/class-chrono-forge-admin-menu.php' => 'Admin menu',
    'chrono-forge/admin/class-chrono-forge-admin-ajax.php' => 'Admin AJAX',
    'chrono-forge/admin/class-chrono-forge-admin-diagnostics.php' => 'Admin diagnostics',
    'chrono-forge/public/class-chrono-forge-public.php' => 'Public class'
);

$php_path = 'C:\Program Files\php\php.exe';
$total_files = 0;
$passed_files = 0;
$failed_files = 0;
$missing_files = 0;

foreach ($files_to_test as $file => $description) {
    $total_files++;
    
    echo sprintf("%-50s ", $description . ':');
    
    if (!file_exists($file)) {
        echo "❌ MISSING\n";
        $missing_files++;
        continue;
    }
    
    // Test syntax using PHP lint
    $output = array();
    $return_var = 0;
    $command = "\"{$php_path}\" -l \"{$file}\" 2>&1";
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ OK\n";
        $passed_files++;
    } else {
        echo "❌ SYNTAX ERROR\n";
        foreach ($output as $line) {
            echo "   " . $line . "\n";
        }
        $failed_files++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total files tested: {$total_files}\n";
echo "Passed: {$passed_files}\n";
echo "Failed: {$failed_files}\n";
echo "Missing: {$missing_files}\n";

if ($failed_files > 0 || $missing_files > 0) {
    echo "\n❌ ISSUES FOUND - Plugin will not work correctly\n";
    exit(1);
} else {
    echo "\n✅ ALL FILES PASSED - Plugin should work correctly\n";
    exit(0);
}
?>
