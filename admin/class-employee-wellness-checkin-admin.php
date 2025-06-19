<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Add AJAX handler for admin
        add_action('wp_ajax_ewc_get_user_history', array($this, 'get_user_history'));
        
        // Schedule weekly report
        add_action('admin_init', array($this, 'schedule_weekly_report'));
        add_action('ewc_weekly_report', array($this, 'send_weekly_report'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, EWC_PLUGIN_URL . 'admin/css/employee-wellness-checkin-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Add Chart.js for both admin page and shortcode
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        wp_enqueue_style('dashicons'); // Ensure dashicons are loaded for the icons
        
        wp_enqueue_script($this->plugin_name, EWC_PLUGIN_URL . 'admin/js/employee-wellness-checkin-admin.js', array('jquery', 'chartjs'), $this->version, false);
        
        // Add AJAX data for admin
        wp_localize_script($this->plugin_name, 'ewc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewc_admin_nonce'),
            'messages' => array(
                'loading' => __('Loading history...', 'employee-wellness-checkin'),
                'error' => __('Error loading history', 'employee-wellness-checkin'),
                'no_data' => __('No history available', 'employee-wellness-checkin')
            )
        ));
    }

    /**
     * Add menu items to the admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Employee Wellness', 'employee-wellness-checkin'),
            __('Employee Wellness', 'employee-wellness-checkin'),
            'manage_options',
            'employee-wellness-checkin',
            array($this, 'display_settings_page'),
            'dashicons-heart',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'employee-wellness-checkin',
            __('Settings', 'employee-wellness-checkin'),
            __('Settings', 'employee-wellness-checkin'),
            'manage_options',
            'employee-wellness-checkin',
            array($this, 'display_settings_page')
        );
        
        // Dashboard submenu (only visible to users with the right capability)
        add_submenu_page(
            'employee-wellness-checkin',
            __('Wellness Dashboard', 'employee-wellness-checkin'),
            __('Wellness Dashboard', 'employee-wellness-checkin'),
            'view_wellness_reports',
            'employee-wellness-dashboard',
            array($this, 'display_dashboard_page')
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings
        register_setting('ewc_settings', 'ewc_notification_emails');
        register_setting('ewc_settings', 'ewc_alert_emails');
        register_setting('ewc_settings', 'ewc_alert_threshold', array('default' => 10));
        register_setting('ewc_settings', 'ewc_dashboard_roles');
        register_setting('ewc_settings', 'ewc_weekly_report_emails');
        
        // Add settings section
        add_settings_section(
            'ewc_settings_section',
            __('Wellness Check-in Settings', 'employee-wellness-checkin'),
            array($this, 'settings_section_callback'),
            'ewc_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'ewc_notification_emails',
            __('Notification Emails', 'employee-wellness-checkin'),
            array($this, 'notification_emails_callback'),
            'ewc_settings',
            'ewc_settings_section'
        );
        
        add_settings_field(
            'ewc_alert_emails',
            __('Alert Emails', 'employee-wellness-checkin'),
            array($this, 'alert_emails_callback'),
            'ewc_settings',
            'ewc_settings_section'
        );
        
        add_settings_field(
            'ewc_alert_threshold',
            __('Alert Threshold (Days)', 'employee-wellness-checkin'),
            array($this, 'alert_threshold_callback'),
            'ewc_settings',
            'ewc_settings_section'
        );
        
        add_settings_field(
            'ewc_dashboard_roles',
            __('Dashboard Access Roles', 'employee-wellness-checkin'),
            array($this, 'dashboard_roles_callback'),
            'ewc_settings',
            'ewc_settings_section'
        );
        
        add_settings_field(
            'ewc_weekly_report_emails',
            __('Weekly Report Emails', 'employee-wellness-checkin'),
            array($this, 'weekly_report_emails_callback'),
            'ewc_settings',
            'ewc_settings_section'
        );

        // Add new settings for email templates
        register_setting('ewc_settings', 'ewc_notification_template', array(
            'sanitize_callback' => array($this, 'sanitize_email_template')
        ));
        register_setting('ewc_settings', 'ewc_alert_template', array(
            'sanitize_callback' => array($this, 'sanitize_email_template')
        ));
        register_setting('ewc_settings', 'ewc_weekly_template', array(
            'sanitize_callback' => array($this, 'sanitize_email_template')
        ));
        
        // Add new settings section for email templates
        add_settings_section(
            'ewc_email_templates_section',
            __('Email Templates', 'employee-wellness-checkin'),
            array($this, 'email_templates_section_callback'),
            'ewc_settings'
        );
        
        // Add settings fields for email templates
        add_settings_field(
            'ewc_notification_template',
            __('Daily Notification Template', 'employee-wellness-checkin'),
            array($this, 'notification_template_callback'),
            'ewc_settings',
            'ewc_email_templates_section'
        );
        
        add_settings_field(
            'ewc_alert_template',
            __('Alert Notification Template', 'employee-wellness-checkin'),
            array($this, 'alert_template_callback'),
            'ewc_settings',
            'ewc_email_templates_section'
        );
        
        add_settings_field(
            'ewc_weekly_template',
            __('Weekly Report Template', 'employee-wellness-checkin'),
            array($this, 'weekly_template_callback'),
            'ewc_settings',
            'ewc_email_templates_section'
        );

        // Add popup settings
        register_setting('ewc_settings', 'ewc_popup_enabled', array(
            'type' => 'boolean',
            'default' => true
        ));
        register_setting('ewc_settings', 'ewc_popup_trigger', array(
            'default' => 'login'
        ));
        register_setting('ewc_settings', 'ewc_popup_delay', array(
            'type' => 'integer',
            'default' => 10
        ));
        
        // Add popup settings section
        add_settings_section(
            'ewc_popup_section',
            __('Popup Settings', 'employee-wellness-checkin'),
            array($this, 'popup_section_callback'),
            'ewc_settings'
        );
        
        // Add popup settings fields
        add_settings_field(
            'ewc_popup_enabled',
            __('Enable Popup', 'employee-wellness-checkin'),
            array($this, 'popup_enabled_callback'),
            'ewc_settings',
            'ewc_popup_section'
        );
        
        add_settings_field(
            'ewc_popup_trigger',
            __('Popup Trigger', 'employee-wellness-checkin'),
            array($this, 'popup_trigger_callback'),
            'ewc_settings',
            'ewc_popup_section'
        );
        
        add_settings_field(
            'ewc_popup_delay',
            __('Popup Delay (minutes)', 'employee-wellness-checkin'),
            array($this, 'popup_delay_callback'),
            'ewc_settings',
            'ewc_popup_section'
        );
    }

    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure the Employee Wellness Check-in plugin settings.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Email templates section callback
     */
    public function email_templates_section_callback() {
        echo '<p>' . __('Customize email templates using available placeholders:', 'employee-wellness-checkin') . '</p>';
        echo '<ul class="ewc-placeholders-list">';
        echo '<li><code>[name]</code> - ' . __('Employee name', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[mood]</code> - ' . __('Submitted mood', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[date_time]</code> - ' . __('Submission date/time', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[days]</code> - ' . __('Number of consecutive days (for alerts)', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[total_employees]</code> - ' . __('Total number of employees', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[happy_percentage]</code> - ' . __('Percentage of happy employees', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[stressed_percentage]</code> - ' . __('Percentage of stressed employees', 'employee-wellness-checkin') . '</li>';
        echo '<li><code>[top_motifs]</code> - ' . __('List of top stress reasons', 'employee-wellness-checkin') . '</li>';
        echo '</ul>';
    }

    /**
     * Popup section callback.
     */
    public function popup_section_callback() {
        echo '<p>' . __('Configure how and when the wellness check-in popup appears.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Notification emails field callback.
     *
     * @since    1.0.0
     */
    public function notification_emails_callback() {
        $value = get_option('ewc_notification_emails', '');
        echo '<input type="text" id="ewc_notification_emails" name="ewc_notification_emails" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Comma-separated list of email addresses to receive notifications when an employee submits their wellness check-in.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Alert emails field callback.
     *
     * @since    1.0.0
     */
    public function alert_emails_callback() {
        $value = get_option('ewc_alert_emails', '');
        echo '<input type="text" id="ewc_alert_emails" name="ewc_alert_emails" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Comma-separated list of email addresses to receive alerts when an employee reports stress or tiredness for consecutive days.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Alert threshold field callback.
     *
     * @since    1.0.0
     */
    public function alert_threshold_callback() {
        $value = get_option('ewc_alert_threshold', 10);
        echo '<input type="number" id="ewc_alert_threshold" name="ewc_alert_threshold" value="' . esc_attr($value) . '" class="small-text" min="1" max="30" />';
        echo '<p class="description">' . __('Number of consecutive days an employee must report stress or tiredness before an alert is sent.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Dashboard roles field callback.
     *
     * @since    1.0.0
     */
    public function dashboard_roles_callback() {
        $selected_roles = get_option('ewc_dashboard_roles', array('administrator', 'hr_manager', 'company_doctor'));
        $roles = get_editable_roles();
        
        foreach ($roles as $role_id => $role_data) {
            $checked = in_array($role_id, $selected_roles) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ewc_dashboard_roles[]" value="' . esc_attr($role_id) . '" ' . $checked . ' /> ' . esc_html($role_data['name']) . '</label><br />';
        }
        
        echo '<p class="description">' . __('Select which user roles can access the wellness dashboard.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Weekly report emails field callback.
     *
     * @since    1.0.0
     */
    public function weekly_report_emails_callback() {
        $value = get_option('ewc_weekly_report_emails', '');
        echo '<input type="text" id="ewc_weekly_report_emails" name="ewc_weekly_report_emails" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Comma-separated list of email addresses to receive weekly wellness reports every Monday.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Daily notification template callback
     */
    public function notification_template_callback() {
        $default_template = "Employee [name] submitted: [mood] on [date_time].";
        $value = get_option('ewc_notification_template', $default_template);
        
        wp_editor($value, 'ewc_notification_template', array(
            'textarea_name' => 'ewc_notification_template',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => array('buttons' => 'strong,em,link')
        ));
        
        echo '<p class="description">' . __('Template for daily mood submission notifications.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Alert template callback
     */
    public function alert_template_callback() {
        $default_template = "ALERT: Employee [name] has reported [mood] for [days] consecutive days.";
        $value = get_option('ewc_alert_template', $default_template);
        
        wp_editor($value, 'ewc_alert_template', array(
            'textarea_name' => 'ewc_alert_template',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => array('buttons' => 'strong,em,link')
        ));
        
        // Add custom CSS class input
        $css_class = get_option('ewc_alert_template_css', 'ewc-alert-email');
        echo '<input type="text" name="ewc_alert_template_css" value="' . esc_attr($css_class) . '" class="regular-text" />';
        echo '<p class="description">' . __('CSS class for email styling', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Weekly report template callback
     */
    public function weekly_template_callback() {
        $default_template = "<h2>Weekly Wellness Report</h2>
<p>Report for week starting [date]</p>
<h3>Summary</h3>
<ul>
    <li>Total Employees: [total_employees]</li>
    <li>Happy Employees: [happy_percentage]%</li>
    <li>Stressed/Tired Employees: [stressed_percentage]%</li>
</ul>
<h3>Top Stress Reasons</h3>
[top_motifs]";
        
        $value = get_option('ewc_weekly_template', $default_template);
        
        wp_editor($value, 'ewc_weekly_template', array(
            'textarea_name' => 'ewc_weekly_template',
            'textarea_rows' => 15,
            'media_buttons' => true,
            'teeny' => false
        ));
        
        echo '<p class="description">' . __('Template for weekly summary report emails.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Popup enabled callback.
     */
    public function popup_enabled_callback() {
        $enabled = get_option('ewc_popup_enabled', true);
        echo '<input type="checkbox" id="ewc_popup_enabled" name="ewc_popup_enabled" value="1" ' . checked($enabled, true, false) . '/>';
        echo '<p class="description">' . __('Show popup reminder for wellness check-in.', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Popup trigger callback.
     */
    public function popup_trigger_callback() {
        $trigger = get_option('ewc_popup_trigger', 'login');
        ?>
        <select name="ewc_popup_trigger" id="ewc_popup_trigger">
            <option value="login" <?php selected($trigger, 'login'); ?>><?php _e('On Login', 'employee-wellness-checkin'); ?></option>
            <option value="delay" <?php selected($trigger, 'delay'); ?>><?php _e('After Delay', 'employee-wellness-checkin'); ?></option>
        </select>
        <?php
    }

    /**
     * Popup delay callback.
     */
    public function popup_delay_callback() {
        $delay = get_option('ewc_popup_delay', 10);
        echo '<input type="number" id="ewc_popup_delay" name="ewc_popup_delay" value="' . esc_attr($delay) . '" min="1" max="60" />';
        echo '<p class="description">' . __('Minutes to wait before showing popup (if "After Delay" is selected).', 'employee-wellness-checkin') . '</p>';
    }

    /**
     * Sanitize email template
     */
    public function sanitize_email_template($input) {
        return wp_kses_post($input);
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once EWC_PLUGIN_DIR . 'admin/partials/employee-wellness-checkin-admin-settings.php';
    }

    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        // Check if user has permission to view the dashboard
        if (!current_user_can('view_wellness_reports')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'employee-wellness-checkin'));
        }
        
        include_once EWC_PLUGIN_DIR . 'admin/partials/employee-wellness-checkin-admin-dashboard.php';
    }

    /**
     * Schedule the daily alert check.
     *
     * @since    1.0.0
     */
    public function schedule_alert_check() {
        if (!wp_next_scheduled('ewc_check_alerts')) {
            wp_schedule_event(time(), 'daily', 'ewc_check_alerts');
        }
    }

    /**
     * Schedule the weekly report.
     *
     * @since    1.0.0
     */
    public function schedule_weekly_report() {
        if (!wp_next_scheduled('ewc_weekly_report')) {
            // Schedule for every Monday at 8 AM
            wp_schedule_event(strtotime('next monday 8am'), 'weekly', 'ewc_weekly_report');
        }
    }

    /**
     * Check for users with consecutive stress/tired days and send alerts.
     *
     * @since    1.0.0
     */
    public function check_for_alerts() {
        $db = new Employee_Wellness_Checkin_DB();
        $threshold = get_option('ewc_alert_threshold', 10);
        $mood_values = array('stressed', 'tired');
        
        $users_with_alerts = $db->get_users_with_consecutive_moods($threshold, $mood_values);
        
        foreach ($users_with_alerts as $user_id) {
            // Get the user's latest mood
            $latest_mood = $db->get_latest_mood($user_id);
            
            if ($latest_mood && in_array($latest_mood->mood_value, $mood_values)) {
                // Get consecutive days count
                $consecutive_days = $db->get_consecutive_mood_days($user_id, $mood_values);
                
                // Send alert notification
                Employee_Wellness_Checkin_Notifications::send_stress_alert_notification(
                    $user_id,
                    $latest_mood->mood_value,
                    $consecutive_days
                );
            }
        }
    }

    /**
     * Send weekly wellness report.
     *
     * @since    1.0.0
     */
    public function send_weekly_report() {
        $db = new Employee_Wellness_Checkin_DB();
        
        // Get report data
        $total_employees = count(get_users());
        $happy_percentage = $db->get_mood_percentage('happy', 7); // Last 7 days
        $stressed_percentage = $db->get_mood_percentage(['stressed', 'tired'], 7);
        $top_reasons = $db->get_top_stress_reasons(7, 5); // Top 5 reasons from last 7 days
        
        // Build email content
        $subject = sprintf(__('[%s] Weekly Wellness Report', 'employee-wellness-checkin'), get_bloginfo('name'));
        
        $content = '<h2>' . __('Weekly Employee Wellness Report', 'employee-wellness-checkin') . '</h2>';
        $content .= '<p>' . sprintf(__('Report for week starting %s', 'employee-wellness-checkin'), date_i18n(get_option('date_format'))) . '</p>';
        
        $content .= '<h3>' . __('Summary', 'employee-wellness-checkin') . '</h3>';
        $content .= '<ul>';
        $content .= '<li>' . sprintf(__('Total Employees: %d', 'employee-wellness-checkin'), $total_employees) . '</li>';
        $content .= '<li>' . sprintf(__('Happy Employees: %.1f%%', 'employee-wellness-checkin'), $happy_percentage) . '</li>';
        $content .= '<li>' . sprintf(__('Stressed/Tired Employees: %.1f%%', 'employee-wellness-checkin'), $stressed_percentage) . '</li>';
        $content .= '</ul>';
        
        if (!empty($top_reasons)) {
            $content .= '<h3>' . __('Top 5 Stress/Tiredness Reasons', 'employee-wellness-checkin') . '</h3>';
            $content .= '<ol>';
            foreach ($top_reasons as $reason) {
                $content .= '<li>' . esc_html($reason->reason) . ' (' . $reason->count . ' times)</li>';
            }
            $content .= '</ol>';
        }
        
        // Send email to configured recipients
        $recipients = get_option('ewc_weekly_report_emails', '');
        if (!empty($recipients)) {
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $emails = array_map('trim', explode(',', $recipients));
            
            foreach ($emails as $email) {
                if (is_email($email)) {
                    wp_mail($email, $subject, $content, $headers);
                }
            }
        }
    }

    /**
     * Render the report dashboard via shortcode.
     *
     * @since    1.0.0
     * @return   string    The HTML output for the dashboard.
     */
    public function render_report_dashboard() {
        // Check if user has permission to view the dashboard
        if (!current_user_can('view_wellness_reports')) {
            return '<p>' . __('You do not have permission to view this dashboard.', 'employee-wellness-checkin') . '</p>';
        }
        
        ob_start();
        include EWC_PLUGIN_DIR . 'admin/partials/employee-wellness-checkin-dashboard-shortcode.php';
        return ob_get_clean();
    }

    /**
     * Get user history via AJAX.
     *
     * @since    1.0.0
     */
    public function get_user_history() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ewc_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Check permissions
        if (!current_user_can('view_wellness_reports')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $db = new Employee_Wellness_Checkin_DB();
        $history = $db->get_user_mood_history($user_id, 30);
        $user = get_userdata($user_id);

        if (!$user || !$history) {
            wp_send_json_error(array('message' => 'No history found'));
        }

        // Format the history data
        $formatted_history = array();
        foreach ($history as $entry) {
            $formatted_history[] = array(
                'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->submission_timestamp)),
                'mood' => ucfirst($entry->mood_value),
                'reason' => $entry->mood_reason,
                'color' => $this->get_mood_color($entry->mood_value),
                'icon' => $this->get_mood_icon($entry->mood_value)
            );
        }

        wp_send_json_success(array(
            'user_name' => $user->display_name,
            'history' => $formatted_history
        ));
    }

    /**
     * Get mood color.
     *
     * @since    1.0.0
     * @param    string    $mood    The mood value.
     * @return   string    The color associated with the mood.
     */
    private function get_mood_color($mood) {
        $colors = array(
            'happy' => '#4CAF50',
            'okay' => '#2196F3',
            'tired' => '#FF9800',
            'stressed' => '#F44336',
            'anxious' => '#9C27B0'
        );
        return isset($colors[$mood]) ? $colors[$mood] : '#999999';
    }

    /**
     * Get mood icon.
     *
     * @since    1.0.0
     * @param    string    $mood    The mood value.
     * @return   string    The icon associated with the mood.
     */
    private function get_mood_icon($mood) {
        $icons = array(
            'happy' => '😊',
            'okay' => '😐',
            'tired' => '😴',
            'stressed' => '😓',
            'anxious' => '😰'
        );
        return isset($icons[$mood]) ? $icons[$mood] : '❓';
    }
}