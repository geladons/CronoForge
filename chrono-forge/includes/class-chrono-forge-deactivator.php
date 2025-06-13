<?php
/**
 * Класс деактивации плагина ChronoForge
 * 
 * Этот класс определяет весь код, который выполняется при деактивации плагина.
 */

// Если файл вызван напрямую, прекратить выполнение
if (!defined('ABSPATH')) {
    exit;
}

class ChronoForge_Deactivator {

    /**
     * Метод деактивации плагина
     * 
     * Выполняет очистку временных данных, отключает крон-задачи
     */
    public static function deactivate() {
        // Очистка запланированных крон-задач
        wp_clear_scheduled_hook('chrono_forge_send_reminders');
        wp_clear_scheduled_hook('chrono_forge_cleanup_old_appointments');
        
        // Очистка кэша
        wp_cache_flush();
        
        // Удаление временных опций (если есть)
        delete_transient('chrono_forge_cache');
        
        // Логирование деактивации (опционально)
        error_log('ChronoForge plugin deactivated at ' . current_time('mysql'));
    }
}
