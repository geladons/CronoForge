<?php
/**
 * Employees Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Employees Controller class
 */
class EmployeesController extends BaseController
{
    /**
     * Employees index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'employees' => 'employees.js'
        ], [
            'employees' => 'employees.css'
        ]);

        $page = $this->getInput('paged', 'int', 1);
        $per_page = 20;
        $search = $this->getInput('s', 'text', '');

        $employees_data = $this->getEmployeesData($page, $per_page, $search);

        $data = [
            'employees' => $employees_data['employees'],
            'pagination' => $employees_data['pagination'],
            'search' => $search
        ];

        $this->render('employees/index', $data);
    }

    /**
     * Get employees list (API)
     */
    public function list()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        $page = $this->getInput('page', 'int', 1);
        $per_page = $this->getInput('per_page', 'int', 20);
        $search = $this->getInput('search', 'text', '');

        $data = $this->getEmployeesData($page, $per_page, $search);
        $this->sendJson($data);
    }

    /**
     * Create new employee
     */
    public function create()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('employee_create')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $employee_data = $this->getEmployeeDataFromInput();
            $errors = $this->validateEmployeeData($employee_data);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $employee_id = $database->insert('employees', $employee_data);

            if ($employee_id) {
                $employee = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('employees') . " WHERE id = %d",
                        $employee_id
                    )
                );

                do_action('chrono_forge_employee_created', $employee);
                $this->sendJson(['message' => 'Employee created successfully', 'employee' => $employee]);
            } else {
                $this->sendJson(['message' => 'Failed to create employee'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Update employee
     */
    public function update($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('employee_update')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $employee_data = $this->getEmployeeDataFromInput();
            $errors = $this->validateEmployeeData($employee_data, $id);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $updated = $database->update('employees', $employee_data, ['id' => $id]);

            if ($updated !== false) {
                $employee = $database->getRow(
                    $database->getWpdb()->prepare(
                        "SELECT * FROM " . $database->getTable('employees') . " WHERE id = %d",
                        $id
                    )
                );

                do_action('chrono_forge_employee_updated', $employee);
                $this->sendJson(['message' => 'Employee updated successfully', 'employee' => $employee]);
            } else {
                $this->sendJson(['message' => 'Failed to update employee'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Delete employee
     */
    public function delete($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('employee_delete')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $database = $this->container->get('database');
            
            // Check if employee has appointments
            $appointment_count = $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " WHERE employee_id = %d",
                    $id
                )
            );

            if ($appointment_count > 0) {
                $this->sendJson(['message' => 'Cannot delete employee with existing appointments'], false);
            }

            $deleted = $database->delete('employees', ['id' => $id]);

            if ($deleted) {
                do_action('chrono_forge_employee_deleted', $id);
                $this->sendJson(['message' => 'Employee deleted successfully']);
            } else {
                $this->sendJson(['message' => 'Failed to delete employee'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Get employees data
     * 
     * @param int $page
     * @param int $per_page
     * @param string $search
     * @return array
     */
    private function getEmployeesData($page, $per_page, $search = '')
    {
        $database = $this->container->get('database');
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $search_term = '%' . $database->getWpdb()->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM " . $database->getTable('employees') . " " . $where;
        $total_items = (int) $database->getVar(
            empty($params) ? $count_query : $database->getWpdb()->prepare($count_query, $params)
        );

        // Get employees
        $query = "SELECT * FROM " . $database->getTable('employees') . " " . $where . " ORDER BY first_name, last_name LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $employees = $database->getResults(
            $database->getWpdb()->prepare($query, $params)
        );

        // Get pagination data
        $pagination = $this->getPagination($total_items, $per_page, $page);

        return [
            'employees' => $employees,
            'pagination' => $pagination
        ];
    }

    /**
     * Get employee data from input
     * 
     * @return array
     */
    private function getEmployeeDataFromInput()
    {
        return [
            'wp_user_id' => $this->getInput('wp_user_id', 'int'),
            'first_name' => $this->getInput('first_name', 'text'),
            'last_name' => $this->getInput('last_name', 'text'),
            'email' => $this->getInput('email', 'email'),
            'phone' => $this->getInput('phone', 'text'),
            'description' => $this->getInput('description', 'text'),
            'avatar' => $this->getInput('avatar', 'url'),
            'status' => $this->getInput('status', 'text', 'active')
        ];
    }

    /**
     * Validate employee data
     * 
     * @param array $data
     * @param int|null $employee_id
     * @return array
     */
    private function validateEmployeeData($data, $employee_id = null)
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

        // Validate status
        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors[] = __('Invalid status.', 'chrono-forge');
        }

        // Check for duplicate email
        if (!empty($data['email'])) {
            $database = $this->container->get('database');
            $existing_query = "SELECT id FROM " . $database->getTable('employees') . " WHERE email = %s";
            
            if ($employee_id) {
                $existing_query .= " AND id != %d";
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['email'], $employee_id)
                );
            } else {
                $existing = $database->getVar(
                    $database->getWpdb()->prepare($existing_query, $data['email'])
                );
            }

            if ($existing) {
                $errors[] = __('An employee with this email already exists.', 'chrono-forge');
            }
        }

        return $errors;
    }
}
