<?php
/**
 * Simple Dependency Injection Container
 * 
 * @package ChronoForge\Infrastructure
 */

namespace ChronoForge\Infrastructure;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Container class for dependency injection
 */
class Container
{
    /**
     * Container bindings
     * 
     * @var array
     */
    private $bindings = [];

    /**
     * Container instances
     * 
     * @var array
     */
    private $instances = [];

    /**
     * Set a binding in the container
     * 
     * @param string $abstract
     * @param callable|string $concrete
     * @param bool $singleton
     */
    public function set($abstract, $concrete, $singleton = true)
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Get an instance from the container
     * 
     * @param string $abstract
     * @return mixed
     * @throws \Exception
     */
    public function get($abstract)
    {
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if binding exists
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("Service '{$abstract}' not found in container");
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];

        // Resolve the concrete implementation
        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } elseif (is_string($concrete)) {
            $instance = new $concrete();
        } else {
            $instance = $concrete;
        }

        // Store singleton instance
        if ($binding['singleton']) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a binding exists
     * 
     * @param string $abstract
     * @return bool
     */
    public function has($abstract)
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Remove a binding
     * 
     * @param string $abstract
     */
    public function remove($abstract)
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    /**
     * Get all bindings
     * 
     * @return array
     */
    public function getBindings()
    {
        return array_keys($this->bindings);
    }

    /**
     * Clear all bindings and instances
     */
    public function clear()
    {
        $this->bindings = [];
        $this->instances = [];
    }

    /**
     * Magic method to get service
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Magic method to check if service exists
     * 
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }
}
