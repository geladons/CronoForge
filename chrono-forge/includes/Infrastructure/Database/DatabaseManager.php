<?php
/**
 * Database Manager
 * 
 * @package ChronoForge\Infrastructure\Database
 */

namespace ChronoForge\Infrastructure\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Manager class
 */
class DatabaseManager
{
    /**
     * WordPress database instance
     * 
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Table prefix
     * 
     * @var string
     */
    private $prefix;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'chrono_forge_';
    }

    /**
     * Get table name with prefix
     *
     * @param string $table
     * @return string
     */
    public function getTable($table)
    {
        return $this->prefix . $table;
    }

    /**
     * Get charset collate for table creation
     *
     * @return string
     */
    private function getCharsetCollate()
    {
        if (method_exists($this->wpdb, 'get_charset_collate')) {
            return $this->wpdb->get_charset_collate();
        }

        // Fallback for testing environments
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    /**
     * Execute dbDelta with fallback for testing environments
     *
     * @param string $sql
     */
    private function executeDbDelta($sql)
    {
        if (!function_exists('dbDelta')) {
            // Only try to load WordPress upgrade.php if we're in a real WordPress environment
            // Check for multiple WordPress indicators to ensure we're not in a test environment
            if (defined('WP_CONTENT_DIR') &&
                function_exists('get_locale') &&
                function_exists('wp_get_current_user') &&
                file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            }
        }

        if (function_exists('dbDelta')) {
            dbDelta($sql);
        } else {
            // Fallback for testing environments - just execute the SQL directly
            if (is_object($this->wpdb)) {
                if (method_exists($this->wpdb, 'query')) {
                    $this->wpdb->query($sql);
                } elseif (is_callable([$this->wpdb, 'query'])) {
                    call_user_func([$this->wpdb, 'query'], $sql);
                }
            }
        }
    }

    /**
     * Create database tables
     */
    public function createTables()
    {
        $this->createServicesTable();
        $this->createEmployeesTable();
        $this->createCustomersTable();
        $this->createAppointmentsTable();
        $this->createPaymentsTable();
    }

    /**
     * Create services table
     */
    private function createServicesTable()
    {
        $table_name = $this->getTable('services');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 60,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            category varchar(100),
            color varchar(7) DEFAULT '#1788FB',
            capacity int(11) DEFAULT 1,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY category (category)
        ) " . $this->getCharsetCollate() . ";";

        $this->executeDbDelta($sql);
    }

    /**
     * Create employees table
     */
    private function createEmployeesTable()
    {
        $table_name = $this->getTable('employees');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id int(11),
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(20),
            description text,
            avatar varchar(255),
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY wp_user_id (wp_user_id),
            KEY status (status)
        ) " . $this->getCharsetCollate() . ";";

        $this->executeDbDelta($sql);
    }

    /**
     * Create customers table
     */
    private function createCustomersTable()
    {
        $table_name = $this->getTable('customers');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id int(11),
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(20),
            birthday date,
            gender enum('male','female','other'),
            notes text,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY wp_user_id (wp_user_id),
            KEY status (status)
        ) " . $this->getCharsetCollate() . ";";

        $this->executeDbDelta($sql);
    }

    /**
     * Create appointments table
     */
    private function createAppointmentsTable()
    {
        $table_name = $this->getTable('appointments');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            service_id int(11) NOT NULL,
            employee_id int(11) NOT NULL,
            customer_id int(11) NOT NULL,
            start_datetime datetime NOT NULL,
            end_datetime datetime NOT NULL,
            status enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
            notes text,
            internal_notes text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY employee_id (employee_id),
            KEY customer_id (customer_id),
            KEY start_datetime (start_datetime),
            KEY status (status)
        ) " . $this->getCharsetCollate() . ";";

        $this->executeDbDelta($sql);
    }

    /**
     * Create payments table
     */
    private function createPaymentsTable()
    {
        $table_name = $this->getTable('payments');

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            appointment_id int(11) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            method enum('cash','card','paypal','stripe','bank_transfer') DEFAULT 'cash',
            status enum('pending','completed','failed','refunded') DEFAULT 'pending',
            transaction_id varchar(255),
            gateway_response text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) " . $this->getCharsetCollate() . ";";

        $this->executeDbDelta($sql);
    }

    /**
     * Drop all plugin tables
     */
    public function dropTables()
    {
        $tables = [
            'payments',
            'appointments', 
            'customers',
            'employees',
            'services'
        ];

        foreach ($tables as $table) {
            $table_name = $this->getTable($table);
            $this->wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }
    }

    /**
     * Get WordPress database instance
     * 
     * @return \wpdb
     */
    public function getWpdb()
    {
        return $this->wpdb;
    }

    /**
     * Execute a query
     * 
     * @param string $query
     * @return mixed
     */
    public function query($query)
    {
        return $this->wpdb->query($query);
    }

    /**
     * Get results from query
     * 
     * @param string $query
     * @return array
     */
    public function getResults($query)
    {
        return $this->wpdb->get_results($query);
    }

    /**
     * Get single row from query
     * 
     * @param string $query
     * @return object|null
     */
    public function getRow($query)
    {
        return $this->wpdb->get_row($query);
    }

    /**
     * Get single variable from query
     * 
     * @param string $query
     * @return mixed
     */
    public function getVar($query)
    {
        return $this->wpdb->get_var($query);
    }

    /**
     * Insert data into table
     * 
     * @param string $table
     * @param array $data
     * @return int|false
     */
    public function insert($table, $data)
    {
        $table_name = $this->getTable($table);
        $result = $this->wpdb->insert($table_name, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }

    /**
     * Update data in table
     * 
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int|false
     */
    public function update($table, $data, $where)
    {
        $table_name = $this->getTable($table);
        return $this->wpdb->update($table_name, $data, $where);
    }

    /**
     * Delete data from table
     * 
     * @param string $table
     * @param array $where
     * @return int|false
     */
    public function delete($table, $where)
    {
        $table_name = $this->getTable($table);
        return $this->wpdb->delete($table_name, $where);
    }
}
