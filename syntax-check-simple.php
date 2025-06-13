<?php
/**
 * Simple syntax checker for ChronoForge files
 */

echo "ChronoForge Syntax Checker\n";
echo "==========================\n\n";

$plugin_dir = __DIR__ . '/chrono-forge';
$files_to_check = array(
    'chrono-forge.php',
    'includes/class-chrono-forge-core.php',
    'includes/class-chrono-forge-activator.php',
    'includes/class-chrono-forge-database.php',
    'includes/class-chrono-forge-shortcodes.php',
    'includes/utils/functions.php',
    'admin/class-chrono-forge-admin-ajax.php',
    'public/class-chrono-forge-public.php',
    'includes/class-chrono-forge-diagnostics.php'
);

$errors_found = false;

foreach ($files_to_check as $file) {
    $file_path = $plugin_dir . '/' . $file;
    
    if (!file_exists($file_path)) {
        echo "❌ File not found: {$file}\n";
        $errors_found = true;
        continue;
    }
    
    echo "Checking: {$file}... ";
    
    // Read file content
    $content = file_get_contents($file_path);
    if ($content === false) {
        echo "❌ CANNOT READ\n";
        $errors_found = true;
        continue;
    }
    
    // Basic syntax checks
    $syntax_errors = array();
    
    // Check brace matching
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    if ($open_braces !== $close_braces) {
        $syntax_errors[] = "Unmatched braces (open: {$open_braces}, close: {$close_braces})";
    }
    
    // Check parentheses matching
    $open_parens = substr_count($content, '(');
    $close_parens = substr_count($content, ')');
    if (abs($open_parens - $close_parens) > 3) { // Allow some tolerance
        $syntax_errors[] = "Unmatched parentheses (open: {$open_parens}, close: {$close_parens})";
    }
    
    // Check for unclosed strings (basic check)
    $lines = explode("\n", $content);
    foreach ($lines as $line_num => $line) {
        $line_num++; // 1-based line numbers
        
        // Skip comments
        if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\/\*/', $line) || preg_match('/^\s*\*/', $line)) {
            continue;
        }
        
        // Check for unclosed single quotes (basic check)
        $single_quotes = substr_count($line, "'");
        if ($single_quotes % 2 !== 0 && !preg_match('/\\\\\'/', $line)) {
            // Check if it's not an escaped quote or inside a comment
            if (!preg_match('/\/\/.*\'/', $line) && !preg_match('/\/\*.*\'.*\*\//', $line)) {
                $syntax_errors[] = "Possible unclosed single quote at line {$line_num}";
            }
        }
        
        // Check for unclosed double quotes (basic check)
        $double_quotes = substr_count($line, '"');
        if ($double_quotes % 2 !== 0 && !preg_match('/\\"/', $line)) {
            // Check if it's not an escaped quote or inside a comment
            if (!preg_match('/\/\/.*"/', $line) && !preg_match('/\/\*.*".*\*\//', $line)) {
                $syntax_errors[] = "Possible unclosed double quote at line {$line_num}";
            }
        }
    }
    
    if (empty($syntax_errors)) {
        echo "✅ OK\n";
    } else {
        echo "❌ ERRORS FOUND:\n";
        foreach ($syntax_errors as $error) {
            echo "   - {$error}\n";
        }
        $errors_found = true;
    }
}

echo "\n";

if ($errors_found) {
    echo "❌ Syntax errors found! Please fix the errors above.\n";
    exit(1);
} else {
    echo "✅ All files have valid syntax!\n";
    exit(0);
}
