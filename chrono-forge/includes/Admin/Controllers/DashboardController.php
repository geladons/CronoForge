<?php
/**
 * Dashboard Controller
 * 
 * @package ChronoForge\Admin\Controllers
 */

namespace ChronoForge\Admin\Controllers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Controller class
 */
class DashboardController extends BaseController
{
    /**
     * Dashboard index page
     */
    public function index()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'dashboard' => 'dashboard.js'
        ], [
            'dashboard' => 'dashboard.css'
        ]);

        $data = [
            'stats' => $this->getStats(),
            'recent_appointments' => $this->getRecentAppointments(),
            'upcoming_appointments' => $this->getUpcomingAppointments()
        ];

        $this->render('dashboard/index', $data);
    }

    /**
     * Calendar page
     */
    public function calendar()
    {
        if (!$this->userCan()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chrono-forge'));
        }

        $this->enqueueAssets([
            'calendar' => [
                'src' => 'calendar.js',
                'deps' => ['jquery', 'jquery-ui-datepicker']
            ]
        ], [
            'calendar' => 'calendar.css'
        ]);

        $data = [
            'services' => $this->getServices(),
            'employees' => $this->getEmployees(),
            'appointments' => $this->getCalendarAppointments()
        ];

        $this->render('dashboard/calendar', $data);
    }

    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    private function getStats()
    {
        $database = $this->container->get('database');

        $stats = [
            'total_appointments' => 0,
            'today_appointments' => 0,
            'pending_appointments' => 0,
            'total_customers' => 0,
            'total_services' => 0,
            'total_employees' => 0,
            'revenue_today' => 0,
            'revenue_month' => 0
        ];

        try {
            // Total appointments
            $stats['total_appointments'] = (int) $database->getVar(
                "SELECT COUNT(*) FROM " . $database->getTable('appointments')
            );

            // Today's appointments
            $today = date('Y-m-d');
            $stats['today_appointments'] = (int) $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " 
                     WHERE DATE(start_datetime) = %s",
                    $today
                )
            );

            // Pending appointments
            $stats['pending_appointments'] = (int) $database->getVar(
                "SELECT COUNT(*) FROM " . $database->getTable('appointments') . " 
                 WHERE status = 'pending'"
            );

            // Total customers
            $stats['total_customers'] = (int) $database->getVar(
                "SELECT COUNT(*) FROM " . $database->getTable('customers') . " 
                 WHERE status = 'active'"
            );

            // Total services
            $stats['total_services'] = (int) $database->getVar(
                "SELECT COUNT(*) FROM " . $database->getTable('services') . " 
                 WHERE status = 'active'"
            );

            // Total employees
            $stats['total_employees'] = (int) $database->getVar(
                "SELECT COUNT(*) FROM " . $database->getTable('employees') . " 
                 WHERE status = 'active'"
            );

            // Revenue today
            $stats['revenue_today'] = (float) $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT SUM(p.amount) FROM " . $database->getTable('payments') . " p
                     JOIN " . $database->getTable('appointments') . " a ON p.appointment_id = a.id
                     WHERE DATE(a.start_datetime) = %s AND p.status = 'completed'",
                    $today
                )
            ) ?: 0;

            // Revenue this month
            $month_start = date('Y-m-01');
            $stats['revenue_month'] = (float) $database->getVar(
                $database->getWpdb()->prepare(
                    "SELECT SUM(p.amount) FROM " . $database->getTable('payments') . " p
                     JOIN " . $database->getTable('appointments') . " a ON p.appointment_id = a.id
                     WHERE DATE(a.start_datetime) >= %s AND p.status = 'completed'",
                    $month_start
                )
            ) ?: 0;

        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting dashboard stats: ' . $e->getMessage(), 'error');
        }

        return $stats;
    }

    /**
     * Get recent appointments
     * 
     * @return array
     */
    private function getRecentAppointments()
    {
        $database = $this->container->get('database');

        try {
            return $database->getResults(
                "SELECT a.*, s.name as service_name, 
                        CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                        CONCAT(c.first_name, ' ', c.last_name) as customer_name
                 FROM " . $database->getTable('appointments') . " a
                 LEFT JOIN " . $database->getTable('services') . " s ON a.service_id = s.id
                 LEFT JOIN " . $database->getTable('employees') . " e ON a.employee_id = e.id
                 LEFT JOIN " . $database->getTable('customers') . " c ON a.customer_id = c.id
                 ORDER BY a.created_at DESC
                 LIMIT 10"
            );
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting recent appointments: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get upcoming appointments
     * 
     * @return array
     */
    private function getUpcomingAppointments()
    {
        $database = $this->container->get('database');

        try {
            return $database->getResults(
                $database->getWpdb()->prepare(
                    "SELECT a.*, s.name as service_name, s.color as service_color,
                            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                            CONCAT(c.first_name, ' ', c.last_name) as customer_name
                     FROM " . $database->getTable('appointments') . " a
                     LEFT JOIN " . $database->getTable('services') . " s ON a.service_id = s.id
                     LEFT JOIN " . $database->getTable('employees') . " e ON a.employee_id = e.id
                     LEFT JOIN " . $database->getTable('customers') . " c ON a.customer_id = c.id
                     WHERE a.start_datetime >= %s AND a.status IN ('pending', 'confirmed')
                     ORDER BY a.start_datetime ASC
                     LIMIT 10",
                    date('Y-m-d H:i:s')
                )
            );
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting upcoming appointments: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get services for calendar
     * 
     * @return array
     */
    private function getServices()
    {
        $database = $this->container->get('database');

        try {
            return $database->getResults(
                "SELECT * FROM " . $database->getTable('services') . " 
                 WHERE status = 'active' 
                 ORDER BY name"
            );
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting services: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get employees for calendar
     * 
     * @return array
     */
    private function getEmployees()
    {
        $database = $this->container->get('database');

        try {
            return $database->getResults(
                "SELECT * FROM " . $database->getTable('employees') . " 
                 WHERE status = 'active' 
                 ORDER BY first_name, last_name"
            );
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting employees: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get appointments for calendar
     * 
     * @return array
     */
    private function getCalendarAppointments()
    {
        $database = $this->container->get('database');

        // Get date range (current month by default)
        $start_date = $this->getInput('start', 'text', date('Y-m-01'));
        $end_date = $this->getInput('end', 'text', date('Y-m-t'));

        try {
            return $database->getResults(
                $database->getWpdb()->prepare(
                    "SELECT a.*, s.name as service_name, s.color as service_color,
                            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                            CONCAT(c.first_name, ' ', c.last_name) as customer_name
                     FROM " . $database->getTable('appointments') . " a
                     LEFT JOIN " . $database->getTable('services') . " s ON a.service_id = s.id
                     LEFT JOIN " . $database->getTable('employees') . " e ON a.employee_id = e.id
                     LEFT JOIN " . $database->getTable('customers') . " c ON a.customer_id = c.id
                     WHERE DATE(a.start_datetime) BETWEEN %s AND %s
                     ORDER BY a.start_datetime",
                    $start_date,
                    $end_date
                )
            );
        } catch (\Exception $e) {
            \ChronoForge\safe_log('Error getting calendar appointments: ' . $e->getMessage(), 'error');
            return [];
        }
    }
}
