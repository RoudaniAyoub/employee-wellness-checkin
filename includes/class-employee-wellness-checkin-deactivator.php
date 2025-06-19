<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_Deactivator {

    /**
     * Clean up when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear the scheduled alert check
        $timestamp = wp_next_scheduled('ewc_check_alerts');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ewc_check_alerts');
        }
        
        // Clear weekly report schedule
        $timestamp = wp_next_scheduled('ewc_weekly_report');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ewc_weekly_report');
        }
        
        // Note: We don't remove the database table or custom roles on deactivation
        // This ensures that data is preserved if the plugin is reactivated
    }
}