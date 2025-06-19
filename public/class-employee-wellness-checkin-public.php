<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_Public {

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
        
        // Add AJAX handler for history
        add_action('wp_ajax_ewc_get_user_history', array($this, 'handle_get_user_history'));

        // Add admin bar notification
        add_action('admin_bar_menu', array($this, 'add_reminder_notification'), 500);
        add_action('wp_head', array($this, 'add_reminder_styles'));
        add_action('admin_head', array($this, 'add_reminder_styles'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, EWC_PLUGIN_URL . 'public/css/employee-wellness-checkin-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, EWC_PLUGIN_URL . 'public/js/employee-wellness-checkin-public.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'ewc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewc_mood_submission_nonce'),
            'messages' => array(
                'success' => __('Thanks for sharing!', 'employee-wellness-checkin'),
                'error' => __('Something went wrong. Please try again.', 'employee-wellness-checkin'),
                'loading' => __('Loading history...', 'employee-wellness-checkin'),
                'no_data' => __('No history records found.', 'employee-wellness-checkin')
            )
        ));

        // Add popup functionality only for logged-in users who haven't submitted today
        if (is_user_logged_in()) {
            $db = new Employee_Wellness_Checkin_DB();
            $user_id = get_current_user_id();
            $today_mood = $db->get_mood_by_date($user_id, date('Y-m-d'));
            
            if (!$today_mood && get_option('ewc_popup_enabled', true)) {
                wp_enqueue_style($this->plugin_name . '-popup', EWC_PLUGIN_URL . 'public/css/employee-wellness-checkin-popup.css', array(), $this->version);
                wp_enqueue_script($this->plugin_name . '-popup', EWC_PLUGIN_URL . 'public/js/employee-wellness-checkin-popup.js', array('jquery'), $this->version, true);
                
                // Pass popup settings to JavaScript
                wp_localize_script($this->plugin_name . '-popup', 'ewc_popup', array(
                    'enabled' => true,
                    'trigger' => get_option('ewc_popup_trigger', 'login'),
                    'delay' => absint(get_option('ewc_popup_delay', 10)),
                    'form_url' => $this->get_checkin_page_url()
                ));
                
                // Add popup template to footer
                add_action('wp_footer', array($this, 'render_popup'));
            }
        }

        // Add dashboard-specific scripts when shortcode is present
        if (has_shortcode(get_post()->post_content, 'employee_wellness_report_dashboard')) {
            // Chart.js
            wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
            
            // Admin dashboard scripts - needed for frontend too
            wp_enqueue_script($this->plugin_name . '-dashboard', 
                EWC_PLUGIN_URL . 'admin/js/employee-wellness-checkin-admin.js', 
                array('jquery', 'chartjs'), 
                $this->version, 
                true
            );
            
            // Add admin styles for dashboard
            wp_enqueue_style($this->plugin_name . '-admin', 
                EWC_PLUGIN_URL . 'admin/css/employee-wellness-checkin-admin.css', 
                array(), 
                $this->version
            );
            
            // Add AJAX data for frontend dashboard
            wp_localize_script($this->plugin_name . '-dashboard', 'ewc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ewc_frontend_nonce'),
                'messages' => array(
                    'loading' => __('Loading history...', 'employee-wellness-checkin'),
                    'error' => __('Error loading history', 'employee-wellness-checkin'),
                    'no_data' => __('No history available', 'employee-wellness-checkin')
                )
            ));
        }
    }

    /**
     * Handle the mood submission AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_mood_submission() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ewc_mood_submission_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'employee-wellness-checkin')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit your wellness check-in.', 'employee-wellness-checkin')));
        }
        
        // Get and validate the mood value
        $mood_value = isset($_POST['mood']) ? sanitize_text_field($_POST['mood']) : '';
        $valid_moods = array('happy', 'okay', 'tired', 'stressed', 'anxious');
        
        if (!in_array($mood_value, $valid_moods)) {
            wp_send_json_error(array('message' => __('Invalid mood selection.', 'employee-wellness-checkin')));
        }
        
        // Get the optional reason
        $mood_reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Submit the mood to the database
        $db = new Employee_Wellness_Checkin_DB();
        $result = $db->submit_mood($user_id, $mood_value, $mood_reason);
        
        if ($result !== false) {
            // Send notification
            Employee_Wellness_Checkin_Notifications::send_mood_submission_notification($user_id, $mood_value);
            
            wp_send_json_success(array(
                'message' => __('Thanks for sharing!', 'employee-wellness-checkin'),
                'mood' => $mood_value
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save your submission. Please try again.', 'employee-wellness-checkin')));
        }
    }

    /**
     * Handle the AJAX request for user history
     */
    public function handle_get_user_history() {
        // Verify nonce for both admin and frontend
        $admin_nonce_valid = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ewc_admin_nonce');
        $frontend_nonce_valid = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ewc_frontend_nonce');

        if (!$admin_nonce_valid && !$frontend_nonce_valid) {
            wp_send_json_error(array('message' => __('Security check failed.', 'employee-wellness-checkin')));
        }

        // Check if user has permission
        if (!current_user_can('view_wellness_reports')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'employee-wellness-checkin')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view history.', 'employee-wellness-checkin')));
        }

        // Get user ID from request
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        // Verify user has permission to view this history
        if (!current_user_can('view_wellness_reports') && get_current_user_id() !== $user_id) {
            wp_send_json_error(array('message' => __('You do not have permission to view this history.', 'employee-wellness-checkin')));
        }

        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'employee-wellness-checkin')));
        }

        // Get mood history
        $db = new Employee_Wellness_Checkin_DB();
        $history = $db->get_user_mood_history($user_id);

        // Format the response
        $formatted_history = array();
        foreach ($history as $entry) {
            $formatted_history[] = array(
                'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->submission_timestamp)),
                'mood' => ucfirst($entry->mood_value),
                'reason' => $entry->mood_reason,
                'icon' => $this->get_mood_icon($entry->mood_value),
                'color' => $this->get_mood_color($entry->mood_value)
            );
        }

        wp_send_json_success(array(
            'user_name' => $user->display_name,
            'history' => $formatted_history
        ));
    }

    /**
     * Render the check-in form via shortcode.
     *
     * @since    1.0.0
     * @return   string    The HTML output for the form.
     */
    public function render_checkin_form() {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to submit your wellness check-in.', 'employee-wellness-checkin') . '</p>';
        }
        
        // Get current user's mood for today
        $user_id = get_current_user_id();
        $db = new Employee_Wellness_Checkin_DB();
        $today_mood = $db->get_mood_by_date($user_id, date('Y-m-d'));
        
        // Pass submission status to JavaScript
        wp_localize_script($this->plugin_name, 'ewc_form_status', array(
            'has_submitted_today' => !empty($today_mood),
            'today_mood' => !empty($today_mood) ? $today_mood->mood_value : '',
            'today_reason' => !empty($today_mood) ? $today_mood->mood_reason : '',
            'messages' => array(
                'already_submitted' => __('You have already submitted your wellness check-in for today. You can submit again tomorrow.', 'employee-wellness-checkin')
            )
        ));
        
        ob_start();
        include EWC_PLUGIN_DIR . 'public/partials/employee-wellness-checkin-form.php';
        return ob_get_clean();
    }

    /**
     * Add reminder notification to admin bar
     */
    public function add_reminder_notification($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }

        // Check if user has submitted today
        $db = new Employee_Wellness_Checkin_DB();
        $user_id = get_current_user_id();
        $today_mood = $db->get_mood_by_date($user_id, date('Y-m-d'));

        // Only show notification if user hasn't submitted today
        if (!$today_mood) {
            $wp_admin_bar->add_node(array(
                'id'    => 'ewc-reminder',
                'title' => '<span class="ab-icon dashicons dashicons-bell"></span>' .
                          '<span class="ewc-reminder-badge">1</span>' .
                          '<span class="ab-label">' . __('Wellness Check-in', 'employee-wellness-checkin') . '</span>',
                'href'  => $this->get_checkin_page_url(),
                'meta'  => array(
                    'title' => __('Submit your daily wellness check-in', 'employee-wellness-checkin'),
                    'class' => 'ewc-reminder-notification'
                )
            ));
        }
    }

    /**
     * Add styles for reminder notification
     */
    public function add_reminder_styles() {
        ?>
        <style>
            #wpadminbar .ewc-reminder-notification .ab-icon {
                margin-right: 6px !important;
                color: #f0c36d;
            }
            
            #wpadminbar .ewc-reminder-notification .ab-icon:before {
                font-size: 20px;
                margin-top: -1px;
            }
            
            .ewc-reminder-badge {
                display: inline-block;
                background: #d63638;
                color: #fff;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                text-align: center;
                line-height: 18px;
                font-size: 11px;
                position: absolute;
                top: 5px;
                right: -5px;
                animation: ewc-pulse 2s infinite;
            }
            
            @keyframes ewc-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            #wpadminbar .ewc-reminder-notification:hover .ab-icon {
                color: #fff;
            }
            
            #wpadminbar .ewc-reminder-notification .ab-label {
                display: inline;
                margin-left: 10px;
            }
        </style>
        <?php
    }

    /**
     * Get the URL of the check-in page
     */
    private function get_checkin_page_url() {
        // Try to get the page with the shortcode
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            's' => '[employee_wellness_checkin_form]'
        ));

        if (!empty($pages)) {
            return get_permalink($pages[0]->ID);
        }

        // Fallback to home URL if page not found
        return home_url();
    }

    /**
     * Render the popup template
     */
    public function render_popup() {
        include EWC_PLUGIN_DIR . 'public/partials/employee-wellness-checkin-popup.php';
    }
}