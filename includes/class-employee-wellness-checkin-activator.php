<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_Activator {

    /**
     * Create the necessary database tables on plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create the database table
        $db = new Employee_Wellness_Checkin_DB();
        $db->create_table();
        
        // Add custom capability for HR/Doctor role
        self::add_hr_capability();
        
        // Schedule the daily alert check
        if (!wp_next_scheduled('ewc_check_alerts')) {
            wp_schedule_event(time(), 'daily', 'ewc_check_alerts');
        }
    }
    
    /**
     * Add custom capability for HR/Doctor role.
     *
     * @since    1.0.0
     */
    private static function add_hr_capability() {
        // Add custom capability to administrator role
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('view_wellness_reports');
        }
        
        // Create HR Manager role if it doesn't exist
        if (!get_role('hr_manager')) {
            add_role(
                'hr_manager',
                __('HR Manager', 'employee-wellness-checkin'),
                array(
                    'read' => true,
                    'view_wellness_reports' => true
                )
            );
        }
        
        // Create Company Doctor role if it doesn't exist
        if (!get_role('company_doctor')) {
            add_role(
                'company_doctor',
                __('Company Doctor', 'employee-wellness-checkin'),
                array(
                    'read' => true,
                    'view_wellness_reports' => true
                )
            );
        }
    }
}