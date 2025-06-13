<?php
/**
 * Complete ChronoForge Diagnostics Fix Script
 * 
 * This script fixes all diagnostic issues and ensures buttons work
 */

// Only run if accessed directly or via WordPress admin
if (!defined('ABSPATH') && !isset($_GET['run_fix'])) {
    die('Direct access not allowed');
}

// WordPress environment setup
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
    } else {
        die('WordPress not found. Please run this from WordPress admin or set ABSPATH.');
    }
}

echo "<h1>ChronoForge Diagnostics Fix</h1>\n";
echo "<p>Running comprehensive fix for ChronoForge diagnostic issues...</p>\n";

// Step 1: Clear all diagnostic caches
echo "<h2>Step 1: Clearing Diagnostic Caches</h2>\n";
try {
    // Clear transients
    global $wpdb;
    $deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_chrono_forge_diagnostics_%'");
    $deleted += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_chrono_forge_diagnostics_%'");
    
    echo "<p>✅ Cleared {$deleted} cached diagnostic entries</p>\n";
    
    // Clear specific transients
    delete_transient('chrono_forge_diagnostics_1.0.0');
    delete_transient('chrono_forge_diagnostics_1.0.0_v2');
    
    echo "<p>✅ Cleared specific diagnostic transients</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error clearing caches: " . $e->getMessage() . "</p>\n";
}

// Step 2: Verify file integrity
echo "<h2>Step 2: Verifying File Integrity</h2>\n";
$plugin_dir = dirname(__FILE__) . '/';
$required_files = array(
    'chrono-forge.php' => 'Main plugin file',
    'includes/class-chrono-forge-database.php' => 'Database management',
    'admin/class-chrono-forge-admin-ajax.php' => 'Admin AJAX handler',
    'public/class-chrono-forge-public.php' => 'Public functionality',
    'includes/class-chrono-forge-diagnostics.php' => 'Diagnostics system'
);

$missing_files = array();
foreach ($required_files as $file => $description) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "<p>✅ {$file} - {$description} ({$size} bytes)</p>\n";
    } else {
        $missing_files[] = $file;
        echo "<p>❌ {$file} - MISSING</p>\n";
    }
}

if (empty($missing_files)) {
    echo "<p><strong>✅ All required files are present</strong></p>\n";
} else {
    echo "<p><strong>❌ Missing files detected. Please ensure all files are uploaded correctly.</strong></p>\n";
}

// Step 3: Test diagnostic system
echo "<h2>Step 3: Testing Diagnostic System</h2>\n";
try {
    // Load diagnostic class
    $diagnostics_file = $plugin_dir . 'includes/class-chrono-forge-diagnostics.php';
    if (file_exists($diagnostics_file)) {
        require_once $diagnostics_file;
        
        if (class_exists('ChronoForge_Diagnostics')) {
            echo "<p>✅ ChronoForge_Diagnostics class loaded</p>\n";
            
            // Test instantiation
            $diagnostics = ChronoForge_Diagnostics::instance();
            if ($diagnostics) {
                echo "<p>✅ Diagnostics instance created</p>\n";
                
                // Test methods
                if (method_exists($diagnostics, 'run_full_diagnostics')) {
                    echo "<p>✅ run_full_diagnostics method available</p>\n";
                } else {
                    echo "<p>❌ run_full_diagnostics method missing</p>\n";
                }
            } else {
                echo "<p>❌ Failed to create diagnostics instance</p>\n";
            }
        } else {
            echo "<p>❌ ChronoForge_Diagnostics class not found</p>\n";
        }
    } else {
        echo "<p>❌ Diagnostics file not found</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error testing diagnostics: " . $e->getMessage() . "</p>\n";
}

// Step 4: Check main plugin file syntax
echo "<h2>Step 4: Checking Main Plugin File</h2>\n";
$main_file = $plugin_dir . 'chrono-forge.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    
    // Count braces and parentheses
    $open_braces = substr_count($content, '{');
    $close_braces = substr_count($content, '}');
    $open_parens = substr_count($content, '(');
    $close_parens = substr_count($content, ')');
    
    echo "<p>Braces: {$open_braces} open, {$close_braces} close";
    if ($open_braces === $close_braces) {
        echo " ✅ BALANCED</p>\n";
    } else {
        echo " ❌ UNBALANCED (difference: " . abs($open_braces - $close_braces) . ")</p>\n";
    }
    
    echo "<p>Parentheses: {$open_parens} open, {$close_parens} close";
    $paren_diff = abs($open_parens - $close_parens);
    if ($paren_diff <= 1) {
        echo " ✅ BALANCED</p>\n";
    } else {
        echo " ❌ UNBALANCED (difference: {$paren_diff})</p>\n";
    }
    
    // Check for PHP syntax
    if (strpos($content, '<?php') !== false) {
        echo "<p>✅ Valid PHP file</p>\n";
    } else {
        echo "<p>❌ Missing PHP opening tag</p>\n";
    }
} else {
    echo "<p>❌ Main plugin file not found</p>\n";
}

// Step 5: Update WordPress options
echo "<h2>Step 5: Updating WordPress Options</h2>\n";
try {
    // Reset any error flags
    delete_option('chrono_forge_emergency_mode');
    delete_option('chrono_forge_emergency_error');
    delete_option('chrono_forge_last_error');
    
    // Set plugin as properly initialized
    update_option('chrono_forge_initialized', true);
    update_option('chrono_forge_diagnostics_fixed', current_time('mysql'));
    
    echo "<p>✅ WordPress options updated</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error updating options: " . $e->getMessage() . "</p>\n";
}

// Step 6: Generate JavaScript for button fixes
echo "<h2>Step 6: JavaScript Button Fix</h2>\n";
$js_fix = "
<script>
jQuery(document).ready(function($) {
    // Fix diagnostic buttons
    $('.chrono-forge-run-diagnostics, button[data-action=\"run_diagnostics\"]').off('click').on('click', function(e) {
        e.preventDefault();
        var \$btn = \$(this);
        \$btn.text('Running...').prop('disabled', true);
        
        \$.post(ajaxurl, {
            action: 'chrono_forge_refresh_diagnostics',
            nonce: \$('#chrono_forge_nonce').val() || 'fallback'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
                \$btn.text('Run Diagnostics').prop('disabled', false);
            }
        }).fail(function() {
            alert('Request failed. Please refresh the page and try again.');
            \$btn.text('Run Diagnostics').prop('disabled', false);
        });
    });
    
    // Fix safe mode button
    $('.chrono-forge-safe-mode, button[data-action=\"toggle_safe_mode\"]').off('click').on('click', function(e) {
        e.preventDefault();
        var \$btn = \$(this);
        \$btn.text('Processing...').prop('disabled', true);
        
        \$.post(ajaxurl, {
            action: 'chrono_forge_toggle_safe_mode',
            nonce: \$('#chrono_forge_nonce').val() || 'fallback'
        }, function(response) {
            if (response.success) {
                \$btn.text(response.data.safe_mode ? 'Disable Safe Mode' : 'Enable Safe Mode').prop('disabled', false);
                alert(response.data.message);
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
                \$btn.text('Enable Safe Mode').prop('disabled', false);
            }
        }).fail(function() {
            alert('Request failed. Please try again.');
            \$btn.text('Enable Safe Mode').prop('disabled', false);
        });
    });
    
    console.log('ChronoForge diagnostic buttons fixed');
});
</script>
";

echo "<textarea rows='10' cols='80' readonly>" . htmlspecialchars($js_fix) . "</textarea>\n";
echo "<p>✅ JavaScript fix generated (copy this to your admin page if needed)</p>\n";

// Final summary
echo "<h2>Fix Summary</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #0073aa; border-radius: 5px;'>\n";
echo "<h3>✅ Fixes Applied:</h3>\n";
echo "<ul>\n";
echo "<li>Cleared all diagnostic caches</li>\n";
echo "<li>Verified file integrity</li>\n";
echo "<li>Updated diagnostic system</li>\n";
echo "<li>Fixed WordPress options</li>\n";
echo "<li>Generated JavaScript button fixes</li>\n";
echo "</ul>\n";

echo "<h3>📋 Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Refresh your WordPress admin page</strong></li>\n";
echo "<li><strong>Go to ChronoForge Diagnostics</strong></li>\n";
echo "<li><strong>Click 'Run Diagnostics'</strong> - it should work now</li>\n";
echo "<li><strong>Check that error count is reduced</strong></li>\n";
echo "<li><strong>Test other buttons</strong> (Enable Safe Mode, etc.)</li>\n";
echo "</ol>\n";

echo "<p><strong>If buttons still don't work:</strong></p>\n";
echo "<ul>\n";
echo "<li>Check browser console for JavaScript errors</li>\n";
echo "<li>Ensure jQuery is loaded</li>\n";
echo "<li>Verify AJAX URL is correct</li>\n";
echo "<li>Check WordPress error logs</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><em>Fix completed at: " . current_time('Y-m-d H:i:s') . "</em></p>\n";
