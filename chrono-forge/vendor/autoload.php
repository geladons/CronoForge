<?php
/**
 * ChronoForge Autoloader
 *
 * Manual PSR-4 autoloader for ChronoForge plugin
 * This file provides autoloading functionality when Composer is not available
 *
 * @package ChronoForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ChronoForge PSR-4 Autoloader
 */
class ChronoForge_Autoloader {

    /**
     * Namespace prefix to directory mapping
     *
     * @var array
     */
    private static $prefixes = [];

    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register() {
        try {
            spl_autoload_register([__CLASS__, 'loadClass']);

            // Register ChronoForge namespace
            self::addNamespace('ChronoForge\\', dirname(__DIR__) . '/includes/');
        } catch (\Exception $e) {
            error_log('ChronoForge Autoloader Error: ' . $e->getMessage());
        }
    }

    /**
     * Add a namespace prefix and directory
     *
     * @param string $prefix Namespace prefix
     * @param string $base_dir Base directory for the namespace
     * @return void
     */
    public static function addNamespace($prefix, $base_dir) {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize base directory
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        
        // Initialize the namespace prefix array
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }
        
        // Add the base directory to the namespace prefix
        array_push(self::$prefixes[$prefix], $base_dir);
    }

    /**
     * Load a class file
     *
     * @param string $class Fully qualified class name
     * @return bool|string False on failure, file path on success
     */
    public static function loadClass($class) {
        try {
            // Work backwards through the namespace names to find a match
            $prefix = $class;

            while (false !== $pos = strrpos($prefix, '\\')) {
                // Retain the trailing namespace separator in the prefix
                $prefix = substr($class, 0, $pos + 1);

                // The rest is the relative class name
                $relative_class = substr($class, $pos + 1);

                // Try to load a mapped file for the prefix and relative class
                $mapped_file = self::loadMappedFile($prefix, $relative_class);
                if ($mapped_file) {
                    return $mapped_file;
                }

                // Remove the trailing namespace separator for the next iteration
                $prefix = rtrim($prefix, '\\');
            }
        } catch (\Exception $e) {
            error_log('ChronoForge Autoloader: Error loading class ' . $class . ': ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class
     *
     * @param string $prefix Namespace prefix
     * @param string $relative_class Relative class name
     * @return bool|string False on failure, file path on success
     */
    private static function loadMappedFile($prefix, $relative_class) {
        // Are there any base directories for this namespace prefix?
        if (!isset(self::$prefixes[$prefix])) {
            return false;
        }
        
        // Look through base directories for this namespace prefix
        foreach (self::$prefixes[$prefix] as $base_dir) {
            // Replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If the mapped file exists, require it
            if (self::requireFile($file)) {
                return $file;
            }
        }
        
        return false;
    }

    /**
     * Require a file if it exists
     *
     * @param string $file File path
     * @return bool True if file was loaded, false otherwise
     */
    private static function requireFile($file) {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    /**
     * Get registered prefixes
     *
     * @return array
     */
    public static function getPrefixes() {
        return self::$prefixes;
    }
}

// Register the autoloader
ChronoForge_Autoloader::register();

// Load global functions
$functions_file = dirname(__DIR__) . '/includes/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
}

// Return the autoloader instance for compatibility
return new class {
    public function add($prefix, $paths) {
        ChronoForge_Autoloader::addNamespace($prefix, $paths);
    }
    
    public function register() {
        // Already registered
        return true;
    }
    
    public function getPrefixes() {
        return ChronoForge_Autoloader::getPrefixes();
    }
};
