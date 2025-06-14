<?php
/**
 * API Router
 * 
 * @package ChronoForge\Infrastructure\Router
 */

namespace ChronoForge\Infrastructure\Router;

use ChronoForge\Infrastructure\Container;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Router class for handling API requests
 */
class Router
{
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;

    /**
     * Registered routes
     * 
     * @var array
     */
    private $routes = [];

    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerRoutes();
    }

    /**
     * Register API routes
     */
    private function registerRoutes()
    {
        // Dashboard routes
        $this->addRoute('GET', 'dashboard/stats', 'admin.controller.dashboard', 'getStats');
        $this->addRoute('GET', 'dashboard/appointments', 'admin.controller.dashboard', 'getAppointments');

        // Services routes
        $this->addRoute('GET', 'services', 'admin.controller.services', 'list');
        $this->addRoute('POST', 'services', 'admin.controller.services', 'create');
        $this->addRoute('PUT', 'services/{id}', 'admin.controller.services', 'update');
        $this->addRoute('DELETE', 'services/{id}', 'admin.controller.services', 'delete');

        // Employees routes
        $this->addRoute('GET', 'employees', 'admin.controller.employees', 'list');
        $this->addRoute('POST', 'employees', 'admin.controller.employees', 'create');
        $this->addRoute('PUT', 'employees/{id}', 'admin.controller.employees', 'update');
        $this->addRoute('DELETE', 'employees/{id}', 'admin.controller.employees', 'delete');

        // Customers routes
        $this->addRoute('GET', 'customers', 'admin.controller.customers', 'list');
        $this->addRoute('POST', 'customers', 'admin.controller.customers', 'create');
        $this->addRoute('PUT', 'customers/{id}', 'admin.controller.customers', 'update');
        $this->addRoute('DELETE', 'customers/{id}', 'admin.controller.customers', 'delete');

        // Appointments routes
        $this->addRoute('GET', 'appointments', 'admin.controller.appointments', 'list');
        $this->addRoute('POST', 'appointments', 'admin.controller.appointments', 'create');
        $this->addRoute('PUT', 'appointments/{id}', 'admin.controller.appointments', 'update');
        $this->addRoute('DELETE', 'appointments/{id}', 'admin.controller.appointments', 'delete');
        $this->addRoute('POST', 'appointments/{id}/status', 'admin.controller.appointments', 'updateStatus');

        // Settings routes
        $this->addRoute('GET', 'settings', 'admin.controller.settings', 'get');
        $this->addRoute('POST', 'settings', 'admin.controller.settings', 'update');
    }

    /**
     * Add a route
     * 
     * @param string $method
     * @param string $path
     * @param string $controller
     * @param string $action
     */
    public function addRoute($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Handle incoming request
     */
    public function handleRequest()
    {
        // Verify nonce
        if (!$this->verifyNonce()) {
            wp_send_json_error(['message' => __('Security check failed.', 'chrono-forge')], 403);
        }

        // Get request data
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getRequestPath();

        // Find matching route
        $route = $this->findRoute($method, $path);

        if (!$route) {
            wp_send_json_error(['message' => __('Route not found.', 'chrono-forge')], 404);
        }

        // Execute route
        $this->executeRoute($route);
    }

    /**
     * Get request path from POST data
     * 
     * @return string
     */
    private function getRequestPath()
    {
        return sanitize_text_field($_POST['route'] ?? $_GET['route'] ?? '');
    }

    /**
     * Find matching route
     * 
     * @param string $method
     * @param string $path
     * @return array|null
     */
    private function findRoute($method, $path)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if ($this->matchPath($route['path'], $path)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Match route path with request path
     * 
     * @param string $routePath
     * @param string $requestPath
     * @return bool
     */
    private function matchPath($routePath, $requestPath)
    {
        // Simple exact match for now
        if ($routePath === $requestPath) {
            return true;
        }

        // Handle parameter routes like services/{id}
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';

        return preg_match($routePattern, $requestPath);
    }

    /**
     * Execute route
     * 
     * @param array $route
     */
    private function executeRoute($route)
    {
        try {
            $controller = $this->container->get($route['controller']);
            $action = $route['action'];

            if (!method_exists($controller, $action)) {
                wp_send_json_error(['message' => __('Action not found.', 'chrono-forge')], 404);
            }

            // Extract parameters from path
            $params = $this->extractParameters($route['path'], $this->getRequestPath());

            // Call controller action
            $result = call_user_func_array([$controller, $action], $params);

            // Send response
            if ($result !== null) {
                wp_send_json_success($result);
            } else {
                wp_send_json_success(['message' => __('Operation completed successfully.', 'chrono-forge')]);
            }

        } catch (\Exception $e) {
            \ChronoForge\safe_log('API Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Extract parameters from path
     * 
     * @param string $routePath
     * @param string $requestPath
     * @return array
     */
    private function extractParameters($routePath, $requestPath)
    {
        $params = [];

        // Simple parameter extraction for routes like services/{id}
        if (strpos($routePath, '{') !== false) {
            $routeParts = explode('/', $routePath);
            $requestParts = explode('/', $requestPath);

            for ($i = 0; $i < count($routeParts); $i++) {
                if (isset($routeParts[$i]) && strpos($routeParts[$i], '{') === 0) {
                    $paramName = trim($routeParts[$i], '{}');
                    $params[$paramName] = $requestParts[$i] ?? null;
                }
            }
        }

        return array_values($params);
    }

    /**
     * Verify nonce
     * 
     * @return bool
     */
    private function verifyNonce()
    {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        return wp_verify_nonce($nonce, 'chrono_forge_ajax');
    }

    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
