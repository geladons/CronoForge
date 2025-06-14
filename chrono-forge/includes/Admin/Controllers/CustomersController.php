<?php
/**
 * Customers Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customers Controller class
 */
class CustomersController extends BaseController
{
    /**
     * Customers index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'customers' => 'customers.js'
        ], [
            'customers' => 'customers.css'
        ]);

        $page = $this->getInput('paged', 'int', 1);
        $per_page = 20;
        $search = $this->getInput('s', 'text', '');

        $customers_data = $this->getCustomersData($page, $per_page, $search);

        $data = [
            'customers' => $customers_data['customers'],
            'pagination' => $customers_data['pagination'],
            'search' => $search
        ];

        $this->render('customers/index', $data);
    }

    /**
     * Get customers list (API)
     */
    public function list()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        $page = $this->getInput('page', 'int', 1);
        $per_page = $this->getInput('per_page', 'int', 20);
        $search = $this->getInput('search', 'text', '');

        $data = $this->getCustomersData($page, $per_page, $search);
        $this->sendJson($data);
    }

    /**
     * Create new customer
     */
    public function create()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('customer_create')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $customer_data = $this->getCustomerDataFromInput();
            $errors = $this->validateCustomerData($customer_data);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $customer_id = $database->insert('customers', $customer_data);

            if ($customer_id) {
                $customer = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('customers') . " WHERE id = %d",
                        $customer_id
                    )
                );

                do_action('chrono_forge_customer_created', $customer);
                $this->sendJson(['message' => 'Customer created successfully', 'customer' => $customer]);
            } else {
                $this->sendJson(['message' => 'Failed to create customer'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Update customer
     */
    public function update($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('customer_update')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $customer_data = $this->getCustomerDataFromInput();
            $errors = $this->validateCustomerData($customer_data, $id);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $updated = $database->update('customers', $customer_data, ['id' => $id]);

            if ($updated !== false) {
                $customer = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('customers') . " WHERE id = %d",
                        $id
                    )
                );

                do_action('chrono_forge_customer_updated', $customer);
                $this->sendJson(['message' => 'Customer updated successfully', 'customer' => $customer]);
            } else {
                $this->sendJson(['message' => 'Failed to update customer'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Delete customer
     */
    public function delete($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('customer_delete')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $database = $this->container->get('database');
            
            // Check if customer has appointments
            $appointment_count = $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " WHERE customer_id = %d",
                    $id
                )
            );

            if ($appointment_count > 0) {
                $this->sendJson(['message' => 'Cannot delete customer with existing appointments'], false);
            }

            $deleted = $database->delete('customers', ['id' => $id]);

            if ($deleted) {
                do_action('chrono_forge_customer_deleted', $id);
                $this->sendJson(['message' => 'Customer deleted successfully']);
            } else {
                $this->sendJson(['message' => 'Failed to delete customer'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Get customers data
     * 
     * @param int $page
     * @param int $per_page
     * @param string $search
     * @return array
     */
    private function getCustomersData($page, $per_page, $search = '')
    {
        $database = $this->container->get('database');
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $database->getWpdb()->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM " . $database->getTable('customers') . " " . $where;
        $total_items = (int) $database->getVar(
            empty($params) ? $count_query : $database->getWpdb()->prepare($count_query, $params)
        );

        // Get customers with appointment count
        $query = "SELECT c.*, 
                         COUNT(a.id) as appointment_count,
                         MAX(a.start_datetime) as last_appointment
                  FROM " . $database->getTable('customers') . " c
                  LEFT JOIN " . $database->getTable('appointments') . " a ON c.id = a.customer_id
                  " . $where . "
                  GROUP BY c.id
                  ORDER BY c.first_name, c.last_name 
                  LIMIT %d OFFSET %d";
        
        $params[] = $per_page;
        $params[] = $offset;

        $customers = $database->getResults(
            $database->getWpdb()->prepare($query, $params)
        );

        // Get pagination data
        $pagination = $this->getPagination($total_items, $per_page, $page);

        return [
            'customers' => $customers,
            'pagination' => $pagination
        ];
    }

    /**
     * Get customer data from input
     * 
     * @return array
     */
    private function getCustomerDataFromInput()
    {
        return [
            'wp_user_id' => $this->getInput('wp_user_id', 'int'),
            'first_name' => $this->getInput('first_name', 'text'),
            'last_name' => $this->getInput('last_name', 'text'),
            'email' => $this->getInput('email', 'email'),
            'phone' => $this->getInput('phone', 'text'),
            'birthday' => $this->getInput('birthday', 'text'),
            'gender' => $this->getInput('gender', 'text'),
            'notes' => $this->getInput('notes', 'text'),
            'status' => $this->getInput('status', 'text', 'active')
        ];
    }

    /**
     * Validate customer data
     * 
     * @param array $data
     * @param int|null $customer_id
     * @return array
     */
    private function validateCustomerData($data, $customer_id = null)
    {
        $errors = [];

        // Required fields
        $required_fields = [
            'first_name' => __('First Name', 'chrono-forge'),
            'last_name' => __('Last Name', 'chrono-forge'),
            'email' => __('Email', 'chrono-forge')
        ];

        $errors = array_merge($errors, $this->validateRequired($required_fields, $data));

        // Validate email format
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = __('Invalid email format.', 'chrono-forge');
        }

        // Validate birthday format
        if (!empty($data['birthday']) && !strtotime($data['birthday'])) {
            $errors[] = __('Invalid birthday format.', 'chrono-forge');
        }

        // Validate gender
        if (!empty($data['gender']) && !in_array($data['gender'], ['male', 'female', 'other'])) {
            $errors[] = __('Invalid gender.', 'chrono-forge');
        }

        // Validate status
        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors[] = __('Invalid status.', 'chrono-forge');
        }

        // Check for duplicate email
        if (!empty($data['email'])) {
            $database = $this->container->get('database');
            $existing_query = "SELECT id FROM " . $database->getTable('customers') . " WHERE email = %s";
            
            if ($customer_id) {
                $existing_query .= " AND id != %d";
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['email'], $customer_id)
                );
            } else {
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['email'])
                );
            }

            if ($existing) {
                $errors[] = __('A customer with this email already exists.', 'chrono-forge');
            }
        }

        return $errors;
    }
}
