<?php
/**
 * Emergency fix for ChronoForge critical error
 */

// Temporarily disable the ChronoForge plugin
$plugin_file = __DIR__ . '/chrono-forge/chrono-forge.php';
$backup_file = __DIR__ . '/chrono-forge/chrono-forge.php.backup';

if (file_exists($plugin_file)) {
    // Create backup
    copy($plugin_file, $backup_file);
    
    // Rename plugin file to disable it
    rename($plugin_file, __DIR__ . '/chrono-forge/chrono-forge.php.disabled');
    
    echo "ChronoForge plugin has been temporarily disabled.\n";
    echo "Backup created: chrono-forge.php.backup\n";
    echo "Plugin file renamed to: chrono-forge.php.disabled\n";
    echo "\nYour WordPress site should now be accessible.\n";
    echo "\nTo re-enable the plugin after fixing:\n";
    echo "1. Fix the syntax errors\n";
    echo "2. Rename chrono-forge.php.disabled back to chrono-forge.php\n";
} else {
    echo "Plugin file not found. Checking for syntax errors...\n";
}

// Check for syntax errors in the disabled file
$disabled_file = __DIR__ . '/chrono-forge/chrono-forge.php.disabled';
if (file_exists($disabled_file)) {
    echo "\nChecking syntax of disabled plugin file...\n";
    
    // Simple syntax check
    $content = file_get_contents($disabled_file);
    
    // Count braces
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    
    echo "Braces: {$open_braces} open, {$close_braces} close\n";
    
    if ($open_braces !== $close_braces) {
        echo "❌ BRACE MISMATCH DETECTED!\n";
        echo "Difference: " . abs($open_braces - $close_braces) . "\n";
    } else {
        echo "✅ Braces are balanced\n";
    }
    
    // Check for obvious PHP syntax issues
    if (strpos($content, '<?php') === false) {
        echo "❌ Missing PHP opening tag\n";
    } else {
        echo "✅ PHP opening tag found\n";
    }
    
    // Look for unclosed strings (basic check)
    $lines = explode("\n", $content);
    $errors = 0;
    
    foreach ($lines as $line_num => $line) {
        $line_num++; // 1-based
        
        // Skip comments
        if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\/\*/', $line)) {
            continue;
        }
        
        // Very basic quote check
        $single_quotes = substr_count($line, "'");
        $double_quotes = substr_count($line, '"');
        
        if ($single_quotes % 2 !== 0 && strlen(trim($line)) > 0 && strlen(trim($line)) < 200) {
            echo "⚠️  Possible unclosed single quote at line {$line_num}: " . substr(trim($line), 0, 50) . "...\n";
            $errors++;
            if ($errors > 5) break; // Limit output
        }
        
        if ($double_quotes % 2 !== 0 && strlen(trim($line)) > 0 && strlen(trim($line)) < 200) {
            echo "⚠️  Possible unclosed double quote at line {$line_num}: " . substr(trim($line), 0, 50) . "...\n";
            $errors++;
            if ($errors > 5) break; // Limit output
        }
    }
    
    if ($errors === 0) {
        echo "✅ No obvious quote issues found\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "EMERGENCY ACTIONS COMPLETED\n";
echo str_repeat("=", 50) . "\n";
echo "1. WordPress site should now be accessible\n";
echo "2. ChronoForge plugin is temporarily disabled\n";
echo "3. Check the syntax issues above\n";
echo "4. Fix the issues and re-enable the plugin\n";
echo "\nTo re-enable: rename chrono-forge.php.disabled to chrono-forge.php\n";
?>
