<?php
/**
 * ChronoForge Database Management Class
 *
 * Handles all database operations for the ChronoForge plugin
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge Database Management Class
 *
 * @since 1.0.0
 */
class ChronoForge_Database {

    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var ChronoForge_Database
     */
    private static $instance = null;

    /**
     * Database version
     *
     * @since 1.0.0
     * @var string
     */
    private $db_version = '1.0.0';

    /**
     * Table names
     *
     * @since 1.0.0
     * @var array
     */
    private $tables = array();

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return ChronoForge_Database
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_table_names();
        $this->init_hooks();
    }

    /**
     * Initialize table names
     *
     * @since 1.0.0
     * @return void
     */
    private function init_table_names() {
        global $wpdb;

        $this->tables = array(
            'services' => $wpdb->prefix . 'chrono_forge_services',
            'employees' => $wpdb->prefix . 'chrono_forge_employees',
            'schedules' => $wpdb->prefix . 'chrono_forge_schedules',
            'appointments' => $wpdb->prefix . 'chrono_forge_appointments',
            'customers' => $wpdb->prefix . 'chrono_forge_customers',
            'payments' => $wpdb->prefix . 'chrono_forge_payments'
        );
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'check_database_version'));
    }

    /**
     * Check database version and update if needed
     *
     * @since 1.0.0
     * @return void
     */
    public function check_database_version() {
        $installed_version = get_option('chrono_forge_db_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('chrono_forge_db_version', $this->db_version);
        }
    }

    /**
     * Create database tables
     *
     * @since 1.0.0
     * @return bool
     */
    public function create_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();
        $success = true;

        // Services table
        $sql = "CREATE TABLE {$this->tables['services']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            category varchar(100),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY category (category)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        // Employees table
        $sql = "CREATE TABLE {$this->tables['employees']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11),
            name varchar(255) NOT NULL,
            email varchar(255),
            phone varchar(50),
            position varchar(100),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY email (email)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        // Schedules table
        $sql = "CREATE TABLE {$this->tables['schedules']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            employee_id int(11) NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            break_start time,
            break_end time,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY employee_id (employee_id),
            KEY day_of_week (day_of_week),
            KEY status (status)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        // Appointments table
        $sql = "CREATE TABLE {$this->tables['appointments']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            customer_id int(11) NOT NULL,
            employee_id int(11) NOT NULL,
            service_id int(11) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            duration int(11) NOT NULL DEFAULT 60,
            status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            notes text,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY employee_id (employee_id),
            KEY service_id (service_id),
            KEY appointment_date (appointment_date),
            KEY status (status)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        // Customers table
        $sql = "CREATE TABLE {$this->tables['customers']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11),
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50),
            address text,
            notes text,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status),
            UNIQUE KEY email_unique (email)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        // Payments table
        $sql = "CREATE TABLE {$this->tables['payments']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            appointment_id int(11) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(255),
            status enum('pending','completed','failed','refunded') DEFAULT 'pending',
            payment_date datetime,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id),
            KEY status (status),
            KEY payment_date (payment_date),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";

        if (dbDelta($sql) === false) {
            $success = false;
        }

        return $success;
    }

    /**
     * Get table name
     *
     * @since 1.0.0
     * @param string $table Table identifier
     * @return string|null Table name or null if not found
     */
    public function get_table_name($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : null;
    }

    /**
     * Get all table names
     *
     * @since 1.0.0
     * @return array
     */
    public function get_all_table_names() {
        return $this->tables;
    }

    /**
     * Drop all plugin tables
     *
     * @since 1.0.0
     * @return bool
     */
    public function drop_tables() {
        global $wpdb;

        $success = true;
        foreach ($this->tables as $table) {
            $result = $wpdb->query("DROP TABLE IF EXISTS {$table}");
            if ($result === false) {
                $success = false;
            }
        }

        delete_option('chrono_forge_db_version');
        return $success;
    }

    /**
     * Check if tables exist
     *
     * @since 1.0.0
     * @return array
     */
    public function check_tables_exist() {
        global $wpdb;

        $results = array();
        foreach ($this->tables as $key => $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
            $results[$key] = $table_exists;
        }

        return $results;
    }

    /**
     * Get database status
     *
     * @since 1.0.0
     * @return array
     */
    public function get_database_status() {
        $tables_exist = $this->check_tables_exist();
        $all_tables_exist = !in_array(false, $tables_exist, true);

        return array(
            'version' => get_option('chrono_forge_db_version', '0.0.0'),
            'current_version' => $this->db_version,
            'tables_exist' => $tables_exist,
            'all_tables_exist' => $all_tables_exist,
            'table_names' => $this->tables
        );
    }
}
