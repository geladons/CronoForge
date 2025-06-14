<?php
/**
 * Services Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Services Controller class
 */
class ServicesController extends BaseController
{
    /**
     * Services index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'services' => 'services.js'
        ], [
            'services' => 'services.css'
        ]);

        // Get pagination parameters
        $page = $this->getInput('paged', 'int', 1);
        $per_page = 20;
        $search = $this->getInput('s', 'text', '');

        // Get services data
        $services_data = $this->getServicesData($page, $per_page, $search);

        $data = [
            'services' => $services_data['services'],
            'pagination' => $services_data['pagination'],
            'search' => $search
        ];

        $this->render('services/index', $data);
    }

    /**
     * Get services list (API)
     */
    public function list()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        $page = $this->getInput('page', 'int', 1);
        $per_page = $this->getInput('per_page', 'int', 20);
        $search = $this->getInput('search', 'text', '');

        $data = $this->getServicesData($page, $per_page, $search);
        $this->sendJson($data);
    }

    /**
     * Create new service
     */
    public function create()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('service_create')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $service_data = $this->getServiceDataFromInput();
            $errors = $this->validateServiceData($service_data);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $service_id = $database->insert('services', $service_data);

            if ($service_id) {
                $service = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('services') . " WHERE id = %d",
                        $service_id
                    )
                );

                do_action('chrono_forge_service_created', $service);
                $this->sendJson(['message' => 'Service created successfully', 'service' => $service]);
            } else {
                $this->sendJson(['message' => 'Failed to create service'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Update service
     */
    public function update($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('service_update')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $service_data = $this->getServiceDataFromInput();
            $errors = $this->validateServiceData($service_data, $id);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $updated = $database->update('services', $service_data, ['id' => $id]);

            if ($updated !== false) {
                $service = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('services') . " WHERE id = %d",
                        $id
                    )
                );

                do_action('chrono_forge_service_updated', $service);
                $this->sendJson(['message' => 'Service updated successfully', 'service' => $service]);
            } else {
                $this->sendJson(['message' => 'Failed to update service'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Delete service
     */
    public function delete($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('service_delete')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $database = $this->container->get('database');
            
            // Check if service has appointments
            $appointment_count = $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " WHERE service_id = %d",
                    $id
                )
            );

            if ($appointment_count > 0) {
                $this->sendJson(['message' => 'Cannot delete service with existing appointments'], false);
            }

            $deleted = $database->delete('services', ['id' => $id]);

            if ($deleted) {
                do_action('chrono_forge_service_deleted', $id);
                $this->sendJson(['message' => 'Service deleted successfully']);
            } else {
                $this->sendJson(['message' => 'Failed to delete service'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Get services data
     * 
     * @param int $page
     * @param int $per_page
     * @param string $search
     * @return array
     */
    private function getServicesData($page, $per_page, $search = '')
    {
        $database = $this->container->get('database');
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (name LIKE %s OR description LIKE %s OR category LIKE %s)";
            $search_term = '%' . $database->getWpdb()->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM " . $database->getTable('services') . " " . $where;
        $total_items = (int) $database->getVar(
            empty($params) ? $count_query : $database->getWpdb()->prepare($count_query, $params)
        );

        // Get services
        $query = "SELECT * FROM " . $database->getTable('services') . " " . $where . " ORDER BY name LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $services = $database->getResults(
            $database->getWpdb()->prepare($query, $params)
        );

        // Get pagination data
        $pagination = $this->getPagination($total_items, $per_page, $page);

        return [
            'services' => $services,
            'pagination' => $pagination
        ];
    }

    /**
     * Get service data from input
     * 
     * @return array
     */
    private function getServiceDataFromInput()
    {
        return [
            'name' => $this->getInput('name', 'text'),
            'description' => $this->getInput('description', 'text'),
            'duration' => $this->getInput('duration', 'int', 60),
            'price' => $this->getInput('price', 'float', 0.00),
            'category' => $this->getInput('category', 'text'),
            'color' => $this->getInput('color', 'text', '#1788FB'),
            'capacity' => $this->getInput('capacity', 'int', 1),
            'status' => $this->getInput('status', 'text', 'active')
        ];
    }

    /**
     * Validate service data
     * 
     * @param array $data
     * @param int|null $service_id
     * @return array
     */
    private function validateServiceData($data, $service_id = null)
    {
        $errors = [];

        // Required fields
        $required_fields = [
            'name' => __('Service Name', 'chrono-forge'),
            'duration' => __('Duration', 'chrono-forge'),
            'price' => __('Price', 'chrono-forge')
        ];

        $errors = array_merge($errors, $this->validateRequired($required_fields, $data));

        // Validate specific fields
        if (!empty($data['duration']) && ($data['duration'] < 15 || $data['duration'] > 480)) {
            $errors[] = __('Duration must be between 15 and 480 minutes.', 'chrono-forge');
        }

        if (!empty($data['price']) && $data['price'] < 0) {
            $errors[] = __('Price cannot be negative.', 'chrono-forge');
        }

        if (!empty($data['capacity']) && ($data['capacity'] < 1 || $data['capacity'] > 100)) {
            $errors[] = __('Capacity must be between 1 and 100.', 'chrono-forge');
        }

        if (!empty($data['color']) && !preg_match('/^#[a-fA-F0-9]{6}$/', $data['color'])) {
            $errors[] = __('Invalid color format.', 'chrono-forge');
        }

        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors[] = __('Invalid status.', 'chrono-forge');
        }

        // Check for duplicate names
        if (!empty($data['name'])) {
            $database = $this->container->get('database');
            $existing_query = "SELECT id FROM " . $database->getTable('services') . " WHERE name = %s";
            
            if ($service_id) {
                $existing_query .= " AND id != %d";
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['name'], $service_id)
                );
            } else {
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['name'])
                );
            }

            if ($existing) {
                $errors[] = __('A service with this name already exists.', 'chrono-forge');
            }
        }

        return $errors;
    }
}
