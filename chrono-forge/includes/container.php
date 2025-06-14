<?php
/**
 * Dependency Injection Container Configuration
 * 
 * @package ChronoForge
 */

namespace ChronoForge;

use ChronoForge\Infrastructure\Container;
use ChronoForge\Infrastructure\Database\DatabaseManager;
use ChronoForge\Infrastructure\Router\Router;
use ChronoForge\Application\Services\ActivatorService;
use ChronoForge\Application\Services\DeactivatorService;
use ChronoForge\Admin\MenuManager;
use ChronoForge\Admin\Controllers\DashboardController;
use ChronoForge\Admin\Controllers\SettingsController;
use ChronoForge\Admin\Controllers\ServicesController;
use ChronoForge\Admin\Controllers\EmployeesController;
use ChronoForge\Admin\Controllers\AppointmentsController;
use ChronoForge\Admin\Controllers\CustomersController;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create container instance
$container = new Container();

// Database services
$container->set('database', function() {
    return new DatabaseManager();
});

// Core services
$container->set('activator', function($container) {
    return new ActivatorService($container->get('database'));
});

$container->set('deactivator', function($container) {
    return new DeactivatorService($container->get('database'));
});

// Router
$container->set('router', function($container) {
    return new Router($container);
});

// Admin services
$container->set('admin.menu', function($container) {
    return new MenuManager($container);
});

// Admin controllers
$container->set('admin.controller.dashboard', function($container) {
    return new DashboardController($container);
});

$container->set('admin.controller.settings', function($container) {
    return new SettingsController($container);
});

$container->set('admin.controller.services', function($container) {
    return new ServicesController($container);
});

$container->set('admin.controller.employees', function($container) {
    return new EmployeesController($container);
});

$container->set('admin.controller.appointments', function($container) {
    return new AppointmentsController($container);
});

$container->set('admin.controller.customers', function($container) {
    return new CustomersController($container);
});

// Note: Domain services and repositories can be added here when implemented
// Example:
// $container->set('service.booking', function($container) {
//     return new \ChronoForge\Domain\Services\BookingService($container->get('database'));
// });

return $container;
