<?php
/**
 * Admin settings page template.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        // Output security fields
        settings_fields('ewc_settings');
        
        // Output setting sections and their fields
        do_settings_sections('ewc_settings');
        
        // Output save settings button
        submit_button(__('Save Settings', 'employee-wellness-checkin'));
        ?>
    </form>
    
    <div class="ewc-shortcode-info">
        <h2><?php _e('Shortcodes', 'employee-wellness-checkin'); ?></h2>
        <p><?php _e('Use the following shortcodes to display the wellness check-in form and dashboard:', 'employee-wellness-checkin'); ?></p>
        <ul>
            <li><code>[employee_wellness_checkin_form]</code> - <?php _e('Displays the mood submission form for employees.', 'employee-wellness-checkin'); ?></li>
            <li><code>[employee_wellness_report_dashboard]</code> - <?php _e('Displays the wellness report dashboard (only visible to authorized roles).', 'employee-wellness-checkin'); ?></li>
        </ul>
    </div>
</div>