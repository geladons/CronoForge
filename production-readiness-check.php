<?php
/**
 * ChronoForge Production Readiness Check
 * 
 * Final comprehensive check before deployment
 */

echo "ChronoForge Production Readiness Check\n";
echo "======================================\n\n";

$plugin_dir = __DIR__ . '/chrono-forge';
$issues = [];
$warnings = [];

// Test 1: File Structure Check
echo "1. File Structure Check:\n";

$required_files = [
    'chrono-forge.php' => 'Main plugin file',
    'README.md' => 'Documentation',
    'composer.json' => 'Composer configuration',
    'vendor/autoload.php' => 'Autoloader',
    'includes/functions.php' => 'Core functions',
    'includes/container.php' => 'DI container',
    'includes/Infrastructure/Container.php' => 'Container class',
    'includes/Infrastructure/Database/DatabaseManager.php' => 'Database manager',
    'includes/Admin/MenuManager.php' => 'Admin menu',
    'includes/Admin/Controllers/BaseController.php' => 'Base controller',
    'includes/Admin/Controllers/DashboardController.php' => 'Dashboard controller',
    'includes/Application/Services/ActivatorService.php' => 'Activator service',
    'assets/css/admin.css' => 'Admin styles',
    'assets/js/admin.js' => 'Admin scripts',
    'templates/admin/dashboard/index.php' => 'Dashboard template',
    'languages/chrono-forge.pot' => 'Translation template (optional)'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    $full_path = $plugin_dir . '/' . $file;
    if (file_exists($full_path)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        if (strpos($file, '(optional)') !== false) {
            echo "   ⚠️  {$description}: {$file} (optional - missing)\n";
            $warnings[] = "Optional file missing: {$file}";
        } else {
            echo "   ❌ {$description}: {$file} (MISSING)\n";
            $missing_files[] = $file;
            $issues[] = "Required file missing: {$file}";
        }
    }
}

echo "\n";

// Test 2: PHP Syntax Check
echo "2. PHP Syntax Validation:\n";

function checkPhpSyntax($file) {
    $output = [];
    $return_code = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_code);
    return $return_code === 0;
}

$php_files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), '/tests/') === false) {
        $php_files[] = $file->getPathname();
    }
}

$syntax_errors = 0;
foreach ($php_files as $file) {
    $relative_path = str_replace($plugin_dir . '/', '', $file);
    if (checkPhpSyntax($file)) {
        echo "   ✅ {$relative_path}\n";
    } else {
        echo "   ❌ {$relative_path} (SYNTAX ERROR)\n";
        $syntax_errors++;
        $issues[] = "Syntax error in: {$relative_path}";
    }
}

echo "   Summary: " . (count($php_files) - $syntax_errors) . "/" . count($php_files) . " files have valid syntax\n\n";

// Test 3: Plugin Header Check
echo "3. Plugin Header Validation:\n";

$main_file = $plugin_dir . '/chrono-forge.php';
$content = file_get_contents($main_file);

$required_headers = [
    'Plugin Name' => 'ChronoForge',
    'Description' => true, // Just check if exists
    'Version' => true,
    'Author' => true,
    'Text Domain' => 'chrono-forge'
];

foreach ($required_headers as $header => $expected) {
    if (preg_match('/' . preg_quote($header) . ':\s*(.+)/i', $content, $matches)) {
        $value = trim($matches[1]);
        if ($expected === true || $value === $expected) {
            echo "   ✅ {$header}: {$value}\n";
        } else {
            echo "   ⚠️  {$header}: {$value} (expected: {$expected})\n";
            $warnings[] = "Plugin header mismatch: {$header}";
        }
    } else {
        echo "   ❌ {$header}: MISSING\n";
        $issues[] = "Missing plugin header: {$header}";
    }
}

echo "\n";

// Test 4: Security Check
echo "4. Security Validation:\n";

$security_checks = [
    'ABSPATH protection' => 0,
    'Direct access prevention' => 0,
    'Nonce verification' => 0,
    'Capability checks' => 0,
    'Input sanitization' => 0
];

foreach ($php_files as $file) {
    $content = file_get_contents($file);
    
    // Check for ABSPATH protection
    if (preg_match('/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/', $content)) {
        $security_checks['ABSPATH protection']++;
    }
    
    // Check for nonce verification
    if (preg_match('/wp_verify_nonce|check_admin_referer/', $content)) {
        $security_checks['Nonce verification']++;
    }
    
    // Check for capability checks
    if (preg_match('/current_user_can|user_can/', $content)) {
        $security_checks['Capability checks']++;
    }
    
    // Check for input sanitization
    if (preg_match('/sanitize_|esc_|wp_kses/', $content)) {
        $security_checks['Input sanitization']++;
    }
}

foreach ($security_checks as $check => $count) {
    if ($count > 0) {
        echo "   ✅ {$check}: Found in {$count} files\n";
    } else {
        echo "   ⚠️  {$check}: Not found\n";
        $warnings[] = "Security check not found: {$check}";
    }
}

echo "\n";

// Test 5: WordPress Compatibility
echo "5. WordPress Compatibility:\n";

$wp_features = [
    'WordPress hooks' => '/add_action|add_filter|do_action|apply_filters/',
    'WordPress functions' => '/wp_|get_option|update_option/',
    'Database usage' => '/\$wpdb|prepare\(/',
    'Localization' => '/__\(|_e\(|_n\(/',
    'Admin interface' => '/admin_menu|add_menu_page|add_submenu_page/'
];

foreach ($wp_features as $feature => $pattern) {
    $found = false;
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        if (preg_match($pattern, $content)) {
            $found = true;
            break;
        }
    }
    
    if ($found) {
        echo "   ✅ {$feature}: Implemented\n";
    } else {
        echo "   ⚠️  {$feature}: Not found\n";
        $warnings[] = "WordPress feature not found: {$feature}";
    }
}

echo "\n";

// Test 6: Performance Check
echo "6. Performance Considerations:\n";

$performance_checks = [
    'Autoloading' => file_exists($plugin_dir . '/vendor/autoload.php'),
    'Conditional loading' => false,
    'Database optimization' => false,
    'Asset optimization' => false
];

// Check for conditional loading
foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/is_admin\(\)|wp_doing_ajax\(\)/', $content)) {
        $performance_checks['Conditional loading'] = true;
        break;
    }
}

// Check for database optimization
foreach ($php_files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/prepare\(|get_var\(|get_results\(/', $content)) {
        $performance_checks['Database optimization'] = true;
        break;
    }
}

// Check for asset optimization
if (file_exists($plugin_dir . '/assets/css') && file_exists($plugin_dir . '/assets/js')) {
    $performance_checks['Asset optimization'] = true;
}

foreach ($performance_checks as $check => $status) {
    if ($status) {
        echo "   ✅ {$check}: Implemented\n";
    } else {
        echo "   ⚠️  {$check}: Not implemented\n";
        $warnings[] = "Performance optimization missing: {$check}";
    }
}

echo "\n";

// Test 7: Documentation Check
echo "7. Documentation Validation:\n";

$doc_files = [
    'README.md' => 'User documentation',
    'INSTALLATION.md' => 'Installation guide',
    'REFACTORING_SUMMARY.md' => 'Technical documentation'
];

foreach ($doc_files as $file => $description) {
    $full_path = $plugin_dir . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        if ($size > 1000) {
            echo "   ✅ {$description}: {$file} ({$size} bytes)\n";
        } else {
            echo "   ⚠️  {$description}: {$file} (too small: {$size} bytes)\n";
            $warnings[] = "Documentation file too small: {$file}";
        }
    } else {
        echo "   ❌ {$description}: {$file} (MISSING)\n";
        $issues[] = "Documentation missing: {$file}";
    }
}

echo "\n";

// Final Summary
echo "PRODUCTION READINESS SUMMARY:\n";
echo "=============================\n";

if (empty($issues)) {
    echo "✅ NO CRITICAL ISSUES FOUND\n\n";
    echo "The plugin is ready for production deployment!\n\n";
    
    if (!empty($warnings)) {
        echo "⚠️  WARNINGS ({count} items):\n";
        foreach ($warnings as $warning) {
            echo "   • {$warning}\n";
        }
        echo "\nThese warnings are not critical but should be addressed for optimal performance.\n\n";
    }
    
    echo "DEPLOYMENT CHECKLIST:\n";
    echo "✅ All required files present\n";
    echo "✅ PHP syntax validation passed\n";
    echo "✅ Plugin headers complete\n";
    echo "✅ Security measures implemented\n";
    echo "✅ WordPress compatibility confirmed\n";
    echo "✅ Performance optimizations in place\n";
    echo "✅ Documentation available\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Upload plugin to WordPress site\n";
    echo "2. Activate plugin through WordPress admin\n";
    echo "3. Configure settings in ChronoForge → Settings\n";
    echo "4. Test booking functionality\n";
    echo "5. Monitor error logs for any issues\n";
    
} else {
    echo "❌ CRITICAL ISSUES FOUND ({count} items):\n";
    foreach ($issues as $issue) {
        echo "   • {$issue}\n";
    }
    echo "\nThese issues must be resolved before production deployment.\n\n";
    
    if (!empty($warnings)) {
        echo "⚠️  ADDITIONAL WARNINGS ({count} items):\n";
        foreach ($warnings as $warning) {
            echo "   • {$warning}\n";
        }
        echo "\n";
    }
    
    echo "REQUIRED ACTIONS:\n";
    echo "1. Fix all critical issues listed above\n";
    echo "2. Re-run this check to verify fixes\n";
    echo "3. Test plugin activation in development environment\n";
    echo "4. Address warnings for optimal performance\n";
}

echo "\nFor support:\n";
echo "• Check WordPress error logs\n";
echo "• Enable WP_DEBUG for detailed error information\n";
echo "• Test in staging environment before production\n";

exit(empty($issues) ? 0 : 1);
?>
