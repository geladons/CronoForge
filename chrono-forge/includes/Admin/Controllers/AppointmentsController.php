<?php
/**
 * Appointments Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Appointments Controller class
 */
class AppointmentsController extends BaseController
{
    /**
     * Appointments index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'appointments' => 'appointments.js'
        ], [
            'appointments' => 'appointments.css'
        ]);

        $page = $this->getInput('paged', 'int', 1);
        $per_page = 20;
        $search = $this->getInput('s', 'text', '');
        $status = $this->getInput('status', 'text', '');

        $appointments_data = $this->getAppointmentsData($page, $per_page, $search, $status);

        $data = [
            'appointments' => $appointments_data['appointments'],
            'pagination' => $appointments_data['pagination'],
            'search' => $search,
            'status_filter' => $status,
            'statuses' => $this->getAppointmentStatuses()
        ];

        $this->render('appointments/index', $data);
    }

    /**
     * Get appointments list (API)
     */
    public function list()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        $page = $this->getInput('page', 'int', 1);
        $per_page = $this->getInput('per_page', 'int', 20);
        $search = $this->getInput('search', 'text', '');
        $status = $this->getInput('status', 'text', '');

        $data = $this->getAppointmentsData($page, $per_page, $search, $status);
        $this->sendJson($data);
    }

    /**
     * Create new appointment
     */
    public function create()
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('appointment_create')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $appointment_data = $this->getAppointmentDataFromInput();
            $errors = $this->validateAppointmentData($appointment_data);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $appointment_id = $database->insert('appointments', $appointment_data);

            if ($appointment_id) {
                $appointment = $this->getAppointmentWithDetails($appointment_id);

                do_action('chrono_forge_appointment_created', $appointment);
                $this->sendJson(['message' => 'Appointment created successfully', 'appointment' => $appointment]);
            } else {
                $this->sendJson(['message' => 'Failed to create appointment'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Update appointment
     */
    public function update($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('appointment_update')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $appointment_data = $this->getAppointmentDataFromInput();
            $errors = $this->validateAppointmentData($appointment_data, $id);

            if (!empty($errors)) {
                $this->sendJson(['message' => implode(', ', $errors)], false);
            }

            $database = $this->container->get('database');
            $updated = $database->update('appointments', $appointment_data, ['id' => $id]);

            if ($updated !== false) {
                $appointment = $this->getAppointmentWithDetails($id);

                do_action('chrono_forge_appointment_updated', $appointment);
                $this->sendJson(['message' => 'Appointment updated successfully', 'appointment' => $appointment]);
            } else {
                $this->sendJson(['message' => 'Failed to update appointment'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Update appointment status
     */
    public function updateStatus($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('appointment_status')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $status = $this->getInput('status', 'text');
            
            if (!in_array($status, array_keys($this->getAppointmentStatuses()))) {
                $this->sendJson(['message' => 'Invalid status'], false);
            }

            $database = $this->container->get('database');
            $updated = $database->update('appointments', ['status' => $status], ['id' => $id]);

            if ($updated !== false) {
                $appointment = $this->getAppointmentWithDetails($id);

                do_action('chrono_forge_appointment_status_changed', $appointment, $status);
                $this->sendJson(['message' => 'Status updated successfully', 'appointment' => $appointment]);
            } else {
                $this->sendJson(['message' => 'Failed to update status'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Delete appointment
     */
    public function delete($id)
    {
        if (!$this->userCan()) {
            $this->sendJson(['message' => 'Insufficient permissions'], false);
        }

        if (!$this->verifyNonce('appointment_delete')) {
            $this->sendJson(['message' => 'Security check failed'], false);
        }

        try {
            $database = $this->container->get('database');
            $deleted = $database->delete('appointments', ['id' => $id]);

            if ($deleted) {
                do_action('chrono_forge_appointment_deleted', $id);
                $this->sendJson(['message' => 'Appointment deleted successfully']);
            } else {
                $this->sendJson(['message' => 'Failed to delete appointment'], false);
            }

        } catch (\Exception $e) {
            $this->sendJson(['message' => $e->getMessage()], false);
        }
    }

    /**
     * Get appointments data
     * 
     * @param int $page
     * @param int $per_page
     * @param string $search
     * @param string $status
     * @return array
     */
    private function getAppointmentsData($page, $per_page, $search = '', $status = '')
    {
        $database = $this->container->get('database');
        $offset = ($page - 1) * $per_page;

        // Build query
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s OR s.name LIKE %s)";
            $search_term = '%' . $database->getWpdb()->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if (!empty($status)) {
            $where .= " AND a.status = %s";
            $params[] = $status;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " a " . $where;
        $total_items = (int) $database->getVar(
            empty($params) ? $count_query : $database->getWpdb()->prepare($count_query, $params)
        );

        // Get appointments with details
        $query = "SELECT a.*, s.name as service_name, s.color as service_color,
                         CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                         CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                         c.email as customer_email, c.phone as customer_phone
                  FROM " . $database->getTable('appointments') . " a
                  LEFT JOIN " . $database->getTable('services') . " s ON a.service_id = s.id
                  LEFT JOIN " . $database->getTable('employees') . " e ON a.employee_id = e.id
                  LEFT JOIN " . $database->getTable('customers') . " c ON a.customer_id = c.id
                  " . $where . "
                  ORDER BY a.start_datetime DESC
                  LIMIT %d OFFSET %d";
        
        $params[] = $per_page;
        $params[] = $offset;

        $appointments = $database->getResults(
            $database->getWpdb()->prepare($query, $params)
        );

        // Get pagination data
        $pagination = $this->getPagination($total_items, $per_page, $page);

        return [
            'appointments' => $appointments,
            'pagination' => $pagination
        ];
    }

    /**
     * Get appointment data from input
     * 
     * @return array
     */
    private function getAppointmentDataFromInput()
    {
        $start_datetime = $this->getInput('start_date', 'text') . ' ' . $this->getInput('start_time', 'text');
        $duration = $this->getInput('duration', 'int', 60);
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + ($duration * 60));

        return [
            'service_id' => $this->getInput('service_id', 'int'),
            'employee_id' => $this->getInput('employee_id', 'int'),
            'customer_id' => $this->getInput('customer_id', 'int'),
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'status' => $this->getInput('status', 'text', 'pending'),
            'notes' => $this->getInput('notes', 'text'),
            'internal_notes' => $this->getInput('internal_notes', 'text'),
            'price' => $this->getInput('price', 'float', 0.00)
        ];
    }

    /**
     * Get appointment with full details
     * 
     * @param int $id
     * @return object|null
     */
    private function getAppointmentWithDetails($id)
    {
        $database = $this->container->get('database');
        
        return $database->getRow(
            $database->getWpdb()->prepare(
                "SELECT a.*, s.name as service_name, s.color as service_color,
                        CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                        c.email as customer_email, c.phone as customer_phone
                 FROM " . $database->getTable('appointments') . " a
                 LEFT JOIN " . $database->getTable('services') . " s ON a.service_id = s.id
                 LEFT JOIN " . $database->getTable('employees') . " e ON a.employee_id = e.id
                 LEFT JOIN " . $database->getTable('customers') . " c ON a.customer_id = c.id
                 WHERE a.id = %d",
                $id
            )
        );
    }

    /**
     * Get appointment statuses
     * 
     * @return array
     */
    private function getAppointmentStatuses()
    {
        return [
            'pending' => __('Pending', 'chrono-forge'),
            'confirmed' => __('Confirmed', 'chrono-forge'),
            'cancelled' => __('Cancelled', 'chrono-forge'),
            'completed' => __('Completed', 'chrono-forge'),
            'no_show' => __('No Show', 'chrono-forge')
        ];
    }

    /**
     * Validate appointment data
     * 
     * @param array $data
     * @param int|null $appointment_id
     * @return array
     */
    private function validateAppointmentData($data, $appointment_id = null)
    {
        $errors = [];

        // Required fields
        $required_fields = [
            'service_id' => __('Service', 'chrono-forge'),
            'employee_id' => __('Employee', 'chrono-forge'),
            'customer_id' => __('Customer', 'chrono-forge'),
            'start_datetime' => __('Start Date/Time', 'chrono-forge')
        ];

        $errors = array_merge($errors, $this->validateRequired($required_fields, $data));

        // Validate datetime
        if (!empty($data['start_datetime']) && !strtotime($data['start_datetime'])) {
            $errors[] = __('Invalid start date/time format.', 'chrono-forge');
        }

        // Check for conflicts
        if (!empty($data['employee_id']) && !empty($data['start_datetime']) && !empty($data['end_datetime'])) {
            $database = $this->container->get('database');
            $conflict_query = "SELECT id FROM " . $database->getTable('appointments') . " 
                              WHERE employee_id = %d 
                              AND status NOT IN ('cancelled', 'no_show')
                              AND (
                                  (start_datetime <= %s AND end_datetime > %s) OR
                                  (start_datetime < %s AND end_datetime >= %s) OR
                                  (start_datetime >= %s AND end_datetime <= %s)
                              )";
            
            $params = [
                $data['employee_id'],
                $data['start_datetime'], $data['start_datetime'],
                $data['end_datetime'], $data['end_datetime'],
                $data['start_datetime'], $data['end_datetime']
            ];

            if ($appointment_id) {
                $conflict_query .= " AND id != %d";
                $params[] = $appointment_id;
            }

            $conflict = $database->getVar(
                $database->getWpdb()->prepare($conflict_query, $params)
            );

            if ($conflict) {
                $errors[] = __('Employee has a conflicting appointment at this time.', 'chrono-forge');
            }
        }

        return $errors;
    }
}
