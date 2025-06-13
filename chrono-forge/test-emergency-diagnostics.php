<?php
/**
 * ChronoForge Emergency Diagnostics Test
 *
 * This file can be accessed directly to test the emergency diagnostics system
 * Access via: /wp-content/plugins/chrono-forge/test-emergency-diagnostics.php
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Try to load WordPress
$wp_load_paths = array(
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress not found. Please run this script from the plugin directory.');
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this test.');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ChronoForge Emergency Diagnostics Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .test-section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 3px; }
        .test-pass { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .test-warn { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>ChronoForge Emergency Diagnostics Test</h1>
    <p><strong>Purpose:</strong> This test verifies that the emergency diagnostic system is working correctly.</p>

    <div class="test-section">
        <h2>1. WordPress Environment Test</h2>
        <table>
            <tr><td><strong>WordPress Loaded</strong></td><td class="success">✓ Yes</td></tr>
            <tr><td><strong>WordPress Version</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
            <tr><td><strong>Current User</strong></td><td><?php echo wp_get_current_user()->user_login; ?> (ID: <?php echo get_current_user_id(); ?>)</td></tr>
            <tr><td><strong>Has manage_options</strong></td><td class="<?php echo current_user_can('manage_options') ? 'success' : 'error'; ?>"><?php echo current_user_can('manage_options') ? '✓ Yes' : '✗ No'; ?></td></tr>
            <tr><td><strong>Is Admin Area</strong></td><td class="<?php echo is_admin() ? 'success' : 'info'; ?>"><?php echo is_admin() ? '✓ Yes' : 'No (frontend)'; ?></td></tr>
        </table>
    </div>

    <div class="test-section">
        <h2>2. Plugin Status Test</h2>
        <table>
            <tr><td><strong>Plugin Active</strong></td><td class="<?php echo is_plugin_active('chrono-forge/chrono-forge.php') ? 'success' : 'error'; ?>"><?php echo is_plugin_active('chrono-forge/chrono-forge.php') ? '✓ Yes' : '✗ No'; ?></td></tr>
            <tr><td><strong>Plugin Directory Defined</strong></td><td class="<?php echo defined('CHRONO_FORGE_PLUGIN_DIR') ? 'success' : 'error'; ?>"><?php echo defined('CHRONO_FORGE_PLUGIN_DIR') ? '✓ Yes' : '✗ No'; ?></td></tr>
            <tr><td><strong>Plugin Directory Path</strong></td><td><?php echo defined('CHRONO_FORGE_PLUGIN_DIR') ? CHRONO_FORGE_PLUGIN_DIR : 'Not defined'; ?></td></tr>
            <tr><td><strong>Plugin Version Defined</strong></td><td class="<?php echo defined('CHRONO_FORGE_VERSION') ? 'success' : 'warning'; ?>"><?php echo defined('CHRONO_FORGE_VERSION') ? '✓ Yes' : '⚠ No'; ?></td></tr>
            <tr><td><strong>Plugin Version</strong></td><td><?php echo defined('CHRONO_FORGE_VERSION') ? CHRONO_FORGE_VERSION : 'Not defined'; ?></td></tr>
        </table>
    </div>

    <div class="test-section">
        <h2>3. Function Availability Test</h2>
        <?php
        $functions_to_test = array(
            'chrono_forge_standalone_diagnostics_page' => 'Standalone diagnostics page function',
            'chrono_forge_render_basic_diagnostics_page' => 'Basic diagnostics page function',
            'chrono_forge_render_emergency_diagnostics_page' => 'Emergency diagnostics page function',
            'chrono_forge_register_emergency_diagnostics_menu' => 'Emergency menu registration function',
            'chrono_forge_syntax_error_notice' => 'Syntax error notice function',
            'chrono_forge_critical_error_notice' => 'Critical error notice function'
        );
        ?>
        <table>
            <?php foreach ($functions_to_test as $function => $description): ?>
                <tr>
                    <td><strong><?php echo esc_html($function); ?></strong><br><small><?php echo esc_html($description); ?></small></td>
                    <td class="<?php echo function_exists($function) ? 'success' : 'error'; ?>">
                        <?php echo function_exists($function) ? '✓ Available' : '✗ Not Available'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="test-section">
        <h2>4. Class Availability Test</h2>
        <?php
        $classes_to_test = array(
            'ChronoForge_Core' => 'Main plugin core class',
            'ChronoForge_Diagnostics' => 'Diagnostics engine class',
            'ChronoForge_Admin_Diagnostics' => 'Admin diagnostics interface class',
            'ChronoForge_Admin_Menu' => 'Admin menu class',
            'ChronoForge_Database' => 'Database manager class'
        );
        ?>
        <table>
            <?php foreach ($classes_to_test as $class => $description): ?>
                <tr>
                    <td><strong><?php echo esc_html($class); ?></strong><br><small><?php echo esc_html($description); ?></small></td>
                    <td class="<?php echo class_exists($class) ? 'success' : 'warning'; ?>">
                        <?php echo class_exists($class) ? '✓ Available' : '⚠ Not Available'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="test-section">
        <h2>5. File Integrity Test</h2>
        <?php
        $files_to_test = array(
            'chrono-forge.php' => 'Main plugin file',
            'includes/class-chrono-forge-core.php' => 'Core class file',
            'includes/utils/functions.php' => 'Utility functions file',
            'admin/class-chrono-forge-admin-menu.php' => 'Admin menu class file',
            'includes/class-chrono-forge-diagnostics.php' => 'Diagnostics class file',
            'admin/class-chrono-forge-admin-diagnostics.php' => 'Admin diagnostics class file',
            'admin/views/view-diagnostics.php' => 'Diagnostics view file'
        );
        
        $plugin_dir = defined('CHRONO_FORGE_PLUGIN_DIR') ? CHRONO_FORGE_PLUGIN_DIR : dirname(__FILE__) . '/';
        ?>
        <table>
            <?php foreach ($files_to_test as $file => $description): ?>
                <?php
                $file_path = $plugin_dir . $file;
                $exists = file_exists($file_path);
                $readable = $exists && is_readable($file_path);
                $size = $exists ? filesize($file_path) : 0;
                ?>
                <tr>
                    <td><strong><?php echo esc_html($file); ?></strong><br><small><?php echo esc_html($description); ?></small></td>
                    <td class="<?php echo $exists && $readable ? 'success' : ($exists ? 'warning' : 'error'); ?>">
                        <?php 
                        if ($exists && $readable) {
                            echo '✓ OK (' . size_format($size) . ')';
                        } elseif ($exists) {
                            echo '⚠ Not readable';
                        } else {
                            echo '✗ Missing';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="test-section">
        <h2>6. Menu Registration Test</h2>
        <?php
        global $menu, $submenu;
        $chrono_forge_menus = array();
        
        // Check main menu
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && strpos($menu_item[2], 'chrono-forge') !== false) {
                    $chrono_forge_menus[] = array(
                        'type' => 'Main Menu',
                        'title' => $menu_item[0],
                        'slug' => $menu_item[2],
                        'capability' => $menu_item[1]
                    );
                }
            }
        }
        
        // Check submenus
        if (is_array($submenu)) {
            foreach ($submenu as $parent => $items) {
                if (strpos($parent, 'chrono-forge') !== false) {
                    foreach ($items as $item) {
                        $chrono_forge_menus[] = array(
                            'type' => 'Submenu',
                            'title' => $item[0],
                            'slug' => $item[2],
                            'capability' => $item[1],
                            'parent' => $parent
                        );
                    }
                }
            }
        }
        ?>
        
        <?php if (!empty($chrono_forge_menus)): ?>
            <div class="test-result test-pass">✓ Found <?php echo count($chrono_forge_menus); ?> ChronoForge menu item(s)</div>
            <table>
                <tr><th>Type</th><th>Title</th><th>Slug</th><th>Capability</th><th>Parent</th></tr>
                <?php foreach ($chrono_forge_menus as $menu_item): ?>
                    <tr>
                        <td><?php echo esc_html($menu_item['type']); ?></td>
                        <td><?php echo esc_html(strip_tags($menu_item['title'])); ?></td>
                        <td><?php echo esc_html($menu_item['slug']); ?></td>
                        <td><?php echo esc_html($menu_item['capability']); ?></td>
                        <td><?php echo isset($menu_item['parent']) ? esc_html($menu_item['parent']) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="test-result test-fail">✗ No ChronoForge menu items found</div>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>7. Function Execution Test</h2>
        <?php if (function_exists('chrono_forge_standalone_diagnostics_page')): ?>
            <div class="test-result test-pass">✓ Standalone diagnostics function is available</div>
            <p><strong>Testing function execution:</strong></p>
            <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9; max-height: 400px; overflow: auto;">
                <?php
                try {
                    ob_start();
                    chrono_forge_standalone_diagnostics_page();
                    $output = ob_get_clean();
                    
                    if (!empty($output)) {
                        echo '<div class="test-result test-pass">✓ Function executed successfully and produced output</div>';
                        echo '<details><summary>Function Output (click to expand)</summary>';
                        echo '<pre>' . esc_html(substr($output, 0, 2000)) . (strlen($output) > 2000 ? '...[truncated]' : '') . '</pre>';
                        echo '</details>';
                    } else {
                        echo '<div class="test-result test-warn">⚠ Function executed but produced no output</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="test-result test-fail">✗ Function execution failed: ' . esc_html($e->getMessage()) . '</div>';
                } catch (Error $e) {
                    echo '<div class="test-result test-fail">✗ Fatal error during function execution: ' . esc_html($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        <?php else: ?>
            <div class="test-result test-fail">✗ Standalone diagnostics function is not available</div>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>8. Direct Access Links</h2>
        <p>Try accessing the diagnostic pages directly:</p>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=chrono-forge-diagnostics'); ?>" target="_blank">Main Diagnostics Page</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=chrono-forge-emergency'); ?>" target="_blank">Emergency Diagnostics Page</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=chrono-forge-emergency-diagnostics'); ?>" target="_blank">Emergency Diagnostics Submenu</a></li>
        </ul>
    </div>

    <div class="test-section">
        <h2>9. Recommendations</h2>
        <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3>Next Steps:</h3>
            <ol>
                <li><strong>Green (✓) items:</strong> Working correctly</li>
                <li><strong>Orange (⚠) items:</strong> May need attention but not critical</li>
                <li><strong>Red (✗) items:</strong> Need to be fixed</li>
                <li>Try clicking the direct access links above to test the diagnostic pages</li>
                <li>If you get "access denied" errors, check user permissions</li>
                <li>If pages are blank, check WordPress error logs</li>
            </ol>
        </div>
    </div>

    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>
