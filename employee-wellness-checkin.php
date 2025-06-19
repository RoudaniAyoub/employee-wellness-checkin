<?php
/**
 * Plugin Name: Employee Wellness Check-in
 * Plugin URI: https://ayoubfatihi.pro
 * Description: A plugin that allows employees to report their well-being and provides HR with insights and alerts.
 * Version: 1.0.1
 * Author: FATIHI Ayoub
 * Author URI: https://ayoubfatihi.pro
 * Text Domain: employee-wellness-checkin
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('EWC_VERSION', '1.0.0');
define('EWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EWC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_employee_wellness_checkin() {
    require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-activator.php';
    Employee_Wellness_Checkin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_employee_wellness_checkin() {
    require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin-deactivator.php';
    Employee_Wellness_Checkin_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_employee_wellness_checkin');
register_deactivation_hook(__FILE__, 'deactivate_employee_wellness_checkin');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once EWC_PLUGIN_DIR . 'includes/class-employee-wellness-checkin.php';

/**
 * Begins execution of the plugin.
 */
function run_employee_wellness_checkin() {
    $plugin = new Employee_Wellness_Checkin();
    $plugin->run();

    // Ensure necessary assets are loaded for frontend
    add_action('wp_enqueue_scripts', function() {
        if (is_user_logged_in()) {
            wp_enqueue_style('dashicons');
        }
        
        // Check if current page has our shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'employee_wellness_report_dashboard')) {
            wp_enqueue_style('wp-admin'); // Load admin styles for tables
        }
    });
}
run_employee_wellness_checkin();