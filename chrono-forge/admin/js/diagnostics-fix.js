/**
 * ChronoForge Diagnostics JavaScript Fix
 * 
 * This script ensures diagnostic buttons work properly
 */

jQuery(document).ready(function($) {
    
    // Fix for Run Diagnostics button
    $(document).on('click', '.chrono-forge-run-diagnostics, button[data-action="run_diagnostics"]', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Show loading state
        $button.text('Running...').prop('disabled', true);
        
        // Run diagnostics via AJAX
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'chrono_forge_refresh_diagnostics',
                nonce: chronoForgeAdmin.nonce || $('#chrono_forge_nonce').val() || 'fallback_nonce'
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to show updated results
                    location.reload();
                } else {
                    alert('Error running diagnostics: ' + (response.data || 'Unknown error'));
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Failed to run diagnostics. Please try refreshing the page.');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Fix for Enable Safe Mode button
    $(document).on('click', '.chrono-forge-safe-mode, button[data-action="toggle_safe_mode"]', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Show loading state
        $button.text('Processing...').prop('disabled', true);
        
        // Toggle safe mode via AJAX
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'chrono_forge_toggle_safe_mode',
                nonce: chronoForgeAdmin.nonce || $('#chrono_forge_nonce').val() || 'fallback_nonce'
            },
            success: function(response) {
                if (response.success) {
                    // Update button text based on new state
                    var newText = response.data.safe_mode ? 'Disable Safe Mode' : 'Enable Safe Mode';
                    $button.text(newText).prop('disabled', false);
                    
                    // Show success message
                    if (response.data.message) {
                        alert(response.data.message);
                    }
                } else {
                    alert('Error toggling safe mode: ' + (response.data || 'Unknown error'));
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Failed to toggle safe mode. Please try again.');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Fix for Clear Error Log button
    $(document).on('click', '.chrono-forge-clear-log, button[data-action="clear_error_log"]', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear the error log?')) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        // Show loading state
        $button.text('Clearing...').prop('disabled', true);
        
        // Clear error log via AJAX
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'chrono_forge_clear_error_log',
                nonce: chronoForgeAdmin.nonce || $('#chrono_forge_nonce').val() || 'fallback_nonce'
            },
            success: function(response) {
                if (response.success) {
                    alert('Error log cleared successfully');
                    // Reload the page to show updated results
                    location.reload();
                } else {
                    alert('Error clearing log: ' + (response.data || 'Unknown error'));
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Failed to clear error log. Please try again.');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Generic button handler for any diagnostic action
    $(document).on('click', '[data-diagnostic-action]', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var action = $button.data('diagnostic-action');
        var originalText = $button.text();
        
        if (!action) {
            console.error('No diagnostic action specified');
            return;
        }
        
        // Show loading state
        $button.text('Processing...').prop('disabled', true);
        
        // Execute action via AJAX
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'chrono_forge_' + action,
                nonce: chronoForgeAdmin.nonce || $('#chrono_forge_nonce').val() || 'fallback_nonce'
            },
            success: function(response) {
                if (response.success) {
                    if (response.data && response.data.message) {
                        alert(response.data.message);
                    }
                    
                    // Reload page for most actions
                    if (action !== 'toggle_safe_mode') {
                        location.reload();
                    } else {
                        $button.text(originalText).prop('disabled', false);
                    }
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Request failed. Please try again.');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Auto-refresh diagnostics every 30 seconds if on diagnostics page
    if (window.location.href.indexOf('chrono-forge') !== -1) {
        var autoRefreshInterval = setInterval(function() {
            // Only auto-refresh if no buttons are currently processing
            if ($('button:disabled').length === 0) {
                console.log('Auto-refreshing diagnostics...');
                
                $.ajax({
                    url: ajaxurl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'chrono_forge_refresh_diagnostics',
                        nonce: chronoForgeAdmin.nonce || $('#chrono_forge_nonce').val() || 'fallback_nonce'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update diagnostic results without full page reload
                            console.log('Diagnostics updated');
                        }
                    },
                    error: function() {
                        console.log('Auto-refresh failed');
                    }
                });
            }
        }, 30000); // 30 seconds
        
        // Clear interval when leaving page
        $(window).on('beforeunload', function() {
            clearInterval(autoRefreshInterval);
        });
    }
    
    // Initialize nonce if not available
    if (typeof chronoForgeAdmin === 'undefined') {
        window.chronoForgeAdmin = {
            nonce: $('#chrono_forge_nonce').val() || 'fallback_nonce'
        };
    }
    
    console.log('ChronoForge Diagnostics JavaScript loaded');
});
