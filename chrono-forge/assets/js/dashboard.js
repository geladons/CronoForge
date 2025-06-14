/**
 * ChronoForge Dashboard JavaScript
 */

(function($) {
    'use strict';

    /**
     * Dashboard functionality
     */
    ChronoForge.Dashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.loadStats();
            this.bindEvents();
            this.initCharts();
        },

        /**
         * Bind dashboard events
         */
        bindEvents: function() {
            // Refresh stats
            $(document).on('click', '.refresh-stats', this.loadStats.bind(this));
            
            // Quick actions
            $(document).on('click', '.quick-action-card', this.handleQuickAction);
            
            // Appointment status updates
            $(document).on('change', '.appointment-status-select', this.updateAppointmentStatus);
        },

        /**
         * Load dashboard statistics
         */
        loadStats: function() {
            var self = this;
            
            ChronoForge.Admin.apiCall('dashboard/stats')
                .done(function(response) {
                    if (response.success) {
                        self.updateStats(response.data);
                    }
                })
                .fail(function() {
                    ChronoForge.Admin.showNotice('Failed to load statistics', 'error');
                });
        },

        /**
         * Update statistics display
         */
        updateStats: function(stats) {
            // Update stat cards
            $.each(stats, function(key, value) {
                var $statCard = $('.stat-' + key);
                if ($statCard.length) {
                    if (key.includes('revenue')) {
                        // Format currency
                        var symbol = chronoForge.currency_symbol || '$';
                        $statCard.text(symbol + parseFloat(value).toFixed(2));
                    } else {
                        $statCard.text(parseInt(value).toLocaleString());
                    }
                }
            });

            // Add animation effect
            $('.chrono-forge-stat-card').addClass('updated');
            setTimeout(function() {
                $('.chrono-forge-stat-card').removeClass('updated');
            }, 1000);
        },

        /**
         * Handle quick action clicks
         */
        handleQuickAction: function(e) {
            e.preventDefault();
            
            var $card = $(this);
            var action = $card.data('action');
            
            // Add loading state
            $card.addClass('loading');
            
            // Navigate to the appropriate page
            window.location.href = $card.attr('href');
        },

        /**
         * Update appointment status
         */
        updateAppointmentStatus: function() {
            var $select = $(this);
            var appointmentId = $select.data('appointment-id');
            var newStatus = $select.val();
            var $row = $select.closest('.appointment-item');
            
            // Show loading
            $row.addClass('updating');
            
            ChronoForge.Admin.apiCall('appointments/' + appointmentId + '/status', {
                status: newStatus
            })
            .done(function(response) {
                if (response.success) {
                    // Update status badge
                    var $badge = $row.find('.status-badge');
                    $badge.removeClass().addClass('status-badge status-' + newStatus);
                    $badge.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    
                    ChronoForge.Admin.showNotice('Appointment status updated', 'success', 3000);
                } else {
                    ChronoForge.Admin.showNotice('Failed to update status', 'error');
                    // Revert select value
                    $select.val($select.data('original-value'));
                }
            })
            .fail(function() {
                ChronoForge.Admin.showNotice('Failed to update status', 'error');
                $select.val($select.data('original-value'));
            })
            .always(function() {
                $row.removeClass('updating');
            });
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            // Revenue chart
            this.initRevenueChart();
            
            // Appointments chart
            this.initAppointmentsChart();
        },

        /**
         * Initialize revenue chart
         */
        initRevenueChart: function() {
            var $canvas = $('#revenue-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            ChronoForge.Admin.apiCall('dashboard/revenue-data')
                .done(function(response) {
                    if (response.success) {
                        var ctx = $canvas[0].getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: 'Revenue',
                                    data: response.data.values,
                                    borderColor: '#1788FB',
                                    backgroundColor: 'rgba(23, 136, 251, 0.1)',
                                    borderWidth: 2,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return '$' + value.toFixed(2);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
        },

        /**
         * Initialize appointments chart
         */
        initAppointmentsChart: function() {
            var $canvas = $('#appointments-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            ChronoForge.Admin.apiCall('dashboard/appointments-data')
                .done(function(response) {
                    if (response.success) {
                        var ctx = $canvas[0].getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    data: response.data.values,
                                    backgroundColor: [
                                        '#1788FB',
                                        '#28a745',
                                        '#ffc107',
                                        '#dc3545',
                                        '#6c757d'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                });
        },

        /**
         * Refresh dashboard data
         */
        refresh: function() {
            this.loadStats();
            this.loadRecentAppointments();
            this.loadUpcomingAppointments();
        },

        /**
         * Load recent appointments
         */
        loadRecentAppointments: function() {
            var $container = $('.recent-appointments-list');
            
            ChronoForge.Admin.apiCall('dashboard/recent-appointments')
                .done(function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    }
                });
        },

        /**
         * Load upcoming appointments
         */
        loadUpcomingAppointments: function() {
            var $container = $('.upcoming-appointments-list');
            
            ChronoForge.Admin.apiCall('dashboard/upcoming-appointments')
                .done(function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    }
                });
        }
    };

    // Auto-refresh dashboard every 5 minutes
    setInterval(function() {
        if ($('.chrono-forge-dashboard').length) {
            ChronoForge.Dashboard.loadStats();
        }
    }, 300000); // 5 minutes

})(jQuery);
