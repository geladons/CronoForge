/**
 * ChronoForge Admin Diagnostics JavaScript
 *
 * @package ChronoForge
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var ChronoForgeDiagnostics = {
        
        /**
         * Initialize diagnostics interface
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Run diagnostics button
            $('#run-diagnostics').on('click', this.runDiagnostics);
            
            // Clear error log button
            $('#clear-error-log').on('click', this.clearErrorLog);
            
            // Toggle safe mode button
            $('#toggle-safe-mode').on('click', this.toggleSafeMode);
            
            // Refresh diagnostics every 5 minutes if page is active
            if (typeof chronoForgeDiagnostics !== 'undefined') {
                setInterval(function() {
                    if (!document.hidden) {
                        ChronoForgeDiagnostics.runDiagnostics(true); // Silent refresh
                    }
                }, 5 * 60 * 1000); // 5 minutes
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to diagnostic test cards
            $('.diagnostic-test-card').each(function() {
                var $card = $(this);
                var severity = $card.hasClass('diagnostic-critical') ? 'critical' :
                              $card.hasClass('diagnostic-error') ? 'error' :
                              $card.hasClass('diagnostic-warning') ? 'warning' : 'success';
                
                $card.attr('title', ChronoForgeDiagnostics.getSeverityDescription(severity));
            });
        },

        /**
         * Get severity description
         */
        getSeverityDescription: function(severity) {
            var descriptions = {
                'critical': 'Critical issue that requires immediate attention',
                'error': 'Error that should be addressed',
                'warning': 'Warning that should be reviewed',
                'success': 'Test passed successfully'
            };
            return descriptions[severity] || '';
        },

        /**
         * Run diagnostics
         */
        runDiagnostics: function(silent) {
            silent = silent || false;
            
            if (!silent) {
                ChronoForgeDiagnostics.showLoading(chronoForgeDiagnostics.strings.runningDiagnostics);
            }

            var data = {
                action: 'chrono_forge_run_diagnostics',
                nonce: chronoForgeDiagnostics.nonce,
                force_refresh: 'true'
            };

            $.post(chronoForgeDiagnostics.ajaxUrl, data)
                .done(function(response) {
                    if (response.success) {
                        if (!silent) {
                            ChronoForgeDiagnostics.hideLoading();
                            ChronoForgeDiagnostics.showSuccess(chronoForgeDiagnostics.strings.diagnosticsComplete);
                            // Reload page to show updated results
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            // Update status indicators silently
                            ChronoForgeDiagnostics.updateStatusIndicators(response.data);
                        }
                    } else {
                        ChronoForgeDiagnostics.hideLoading();
                        ChronoForgeDiagnostics.showError(response.data.message || chronoForgeDiagnostics.strings.error);
                    }
                })
                .fail(function() {
                    ChronoForgeDiagnostics.hideLoading();
                    ChronoForgeDiagnostics.showError(chronoForgeDiagnostics.strings.error);
                });
        },

        /**
         * Clear error log
         */
        clearErrorLog: function() {
            if (!confirm(chronoForgeDiagnostics.strings.confirmClearLog)) {
                return;
            }

            ChronoForgeDiagnostics.showLoading(chronoForgeDiagnostics.strings.clearingLog);

            var data = {
                action: 'chrono_forge_clear_error_log',
                nonce: chronoForgeDiagnostics.nonce
            };

            $.post(chronoForgeDiagnostics.ajaxUrl, data)
                .done(function(response) {
                    ChronoForgeDiagnostics.hideLoading();
                    
                    if (response.success) {
                        ChronoForgeDiagnostics.showSuccess(chronoForgeDiagnostics.strings.logCleared);
                        // Reload page to show updated log
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        ChronoForgeDiagnostics.showError(response.data.message || chronoForgeDiagnostics.strings.error);
                    }
                })
                .fail(function() {
                    ChronoForgeDiagnostics.hideLoading();
                    ChronoForgeDiagnostics.showError(chronoForgeDiagnostics.strings.error);
                });
        },

        /**
         * Toggle safe mode
         */
        toggleSafeMode: function() {
            var $button = $(this);
            var action = $button.data('action');
            var enable = action === 'enable';

            if (!confirm(chronoForgeDiagnostics.strings.confirmToggleSafeMode)) {
                return;
            }

            ChronoForgeDiagnostics.showLoading(chronoForgeDiagnostics.strings.toggleSafeMode);

            var data = {
                action: 'chrono_forge_toggle_safe_mode',
                nonce: chronoForgeDiagnostics.nonce,
                enable: enable ? 'true' : 'false'
            };

            $.post(chronoForgeDiagnostics.ajaxUrl, data)
                .done(function(response) {
                    ChronoForgeDiagnostics.hideLoading();
                    
                    if (response.success) {
                        ChronoForgeDiagnostics.showSuccess(chronoForgeDiagnostics.strings.safeModeToggled);
                        
                        // Update button
                        if (enable) {
                            $button.text('Disable Safe Mode').data('action', 'disable');
                        } else {
                            $button.text('Enable Safe Mode').data('action', 'enable');
                        }
                        
                        // Reload page to reflect changes
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        ChronoForgeDiagnostics.showError(response.data.message || chronoForgeDiagnostics.strings.error);
                    }
                })
                .fail(function() {
                    ChronoForgeDiagnostics.hideLoading();
                    ChronoForgeDiagnostics.showError(chronoForgeDiagnostics.strings.error);
                });
        },

        /**
         * Update status indicators silently
         */
        updateStatusIndicators: function(data) {
            // Update overall status
            var $statusOverview = $('.diagnostic-status-overview');
            $statusOverview.removeClass('status-healthy status-warning status-error status-critical');
            $statusOverview.addClass('status-' + data.overall_status);

            // Update summary counts
            $('.summary-item.critical').text(data.summary.critical + ' Critical');
            $('.summary-item.error').text(data.summary.error + ' Errors');
            $('.summary-item.warning').text(data.summary.warning + ' Warnings');
            $('.summary-item.info').text(data.summary.total + ' Total Tests');
        },

        /**
         * Show loading overlay
         */
        showLoading: function(message) {
            $('#loading-message').text(message || chronoForgeDiagnostics.strings.runningDiagnostics);
            $('#diagnostic-loading').show();
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#diagnostic-loading').hide();
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showNotice(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.showNotice(message, 'error');
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ChronoForgeDiagnostics.init();
    });

    // Export to global scope for external access
    window.ChronoForgeDiagnostics = ChronoForgeDiagnostics;

})(jQuery);
