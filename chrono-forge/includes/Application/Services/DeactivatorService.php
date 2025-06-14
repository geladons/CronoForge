<?php
/**
 * Plugin Deactivator Service
 * 
 * @package ChronoForge\Application\Services
 */

namespace ChronoForge\Application\Services;

use ChronoForge\Infrastructure\Database\DatabaseManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deactivator Service class
 */
class DeactivatorService
{
    /**
     * Database manager
     * 
     * @var DatabaseManager
     */
    private $database;

    /**
     * Constructor
     * 
     * @param DatabaseManager $database
     */
    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    /**
     * Deactivate the plugin
     */
    public function deactivate()
    {
        // Clear scheduled events
        $this->clearScheduledEvents();

        // Clear transients
        $this->clearTransients();

        // Log deactivation
        \ChronoForge\safe_log('Plugin deactivated successfully');

        // Update deactivation flag
        update_option('chrono_forge_deactivated', time());
    }

    /**
     * Clear scheduled events
     */
    private function clearScheduledEvents()
    {
        $scheduled_events = [
            'chrono_forge_daily_cleanup',
            'chrono_forge_send_reminders',
            'chrono_forge_process_payments'
        ];

        foreach ($scheduled_events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }

        \ChronoForge\safe_log('Scheduled events cleared');
    }

    /**
     * Clear plugin transients
     */
    private function clearTransients()
    {
        global $wpdb;

        // Delete all transients that start with chrono_forge_
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_chrono_forge_%' 
             OR option_name LIKE '_transient_timeout_chrono_forge_%'"
        );

        \ChronoForge\safe_log('Transients cleared');
    }

    /**
     * Complete uninstall (only called during plugin deletion)
     */
    public function uninstall()
    {
        // Check if user wants to keep data
        $keep_data = get_option('chrono_forge_keep_data_on_uninstall', false);

        if (!$keep_data) {
            // Drop database tables
            $this->database->dropTables();
            \ChronoForge\safe_log('Database tables dropped');

            // Delete all plugin options
            $this->deletePluginOptions();
            \ChronoForge\safe_log('Plugin options deleted');

            // Delete user meta
            $this->deleteUserMeta();
            \ChronoForge\safe_log('User meta deleted');
        }

        // Clear any remaining scheduled events
        $this->clearScheduledEvents();

        // Clear transients
        $this->clearTransients();

        \ChronoForge\safe_log('Plugin uninstalled successfully');
    }

    /**
     * Delete all plugin options
     */
    private function deletePluginOptions()
    {
        global $wpdb;

        // Delete all options that start with chrono_forge_
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE 'chrono_forge_%'"
        );
    }

    /**
     * Delete plugin-related user meta
     */
    private function deleteUserMeta()
    {
        global $wpdb;

        // Delete all user meta that starts with chrono_forge_
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'chrono_forge_%'"
        );
    }
}
