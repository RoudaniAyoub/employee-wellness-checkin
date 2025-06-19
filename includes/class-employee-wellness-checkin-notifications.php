<?php
/**
 * Handles notifications for the Employee Wellness Check-in plugin.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_Notifications {

    /**
     * Replace template placeholders with actual values
     */
    private static function replace_placeholders($template, $data) {
        $placeholders = array(
            '[name]' => isset($data['name']) ? $data['name'] : '',
            '[mood]' => isset($data['mood']) ? $data['mood'] : '',
            '[date_time]' => isset($data['date_time']) ? $data['date_time'] : '',
            '[days]' => isset($data['days']) ? $data['days'] : '',
            '[total_employees]' => isset($data['total_employees']) ? $data['total_employees'] : '',
            '[happy_percentage]' => isset($data['happy_percentage']) ? $data['happy_percentage'] : '',
            '[stressed_percentage]' => isset($data['stressed_percentage']) ? $data['stressed_percentage'] : '',
            '[top_motifs]' => isset($data['top_motifs']) ? $data['top_motifs'] : '',
            '[date]' => date_i18n(get_option('date_format'))
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Send a notification when an employee submits a mood.
     *
     * @since    1.0.0
     * @param    int       $user_id      The user ID.
     * @param    string    $mood_value   The mood value submitted.
     * @return   boolean                 True if email was sent, false otherwise.
     */
    public static function send_mood_submission_notification($user_id, $mood_value) {
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Get notification email addresses from settings
        $notification_emails = get_option('ewc_notification_emails', '');
        if (empty($notification_emails)) {
            return false;
        }
        
        $emails = array_map('trim', explode(',', $notification_emails));
        
        // Prepare email content using template
        $template = get_option('ewc_notification_template', '');
        if (empty($template)) {
            $template = "Employee [name] submitted: [mood] on [date_time].";
        }
        
        $data = array(
            'name' => $user->display_name,
            'mood' => ucfirst($mood_value),
            'date_time' => current_time('mysql')
        );
        
        $message = self::replace_placeholders($template, $data);
        
        $subject = sprintf(
            __('[%s] Employee Wellness Check-in Submission', 'employee-wellness-checkin'),
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Send email to each recipient
        foreach ($emails as $email) {
            if (!is_email($email)) {
                continue;
            }
            
            wp_mail($email, $subject, $message, $headers);
        }
        
        return true;
    }

    /**
     * Send an alert notification for prolonged stress or tiredness.
     *
     * @since    1.0.0
     * @param    int       $user_id           The user ID.
     * @param    string    $mood_value        The mood value (stressed or tired).
     * @param    int       $consecutive_days  The number of consecutive days with this mood.
     * @return   boolean                      True if email was sent, false otherwise.
     */
    public static function send_stress_alert_notification($user_id, $mood_value, $consecutive_days) {
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Get alert email addresses from settings
        $alert_emails = get_option('ewc_alert_emails', '');
        if (empty($alert_emails)) {
            return false;
        }
        
        $emails = array_map('trim', explode(',', $alert_emails));
        
        // Prepare email content using template
        $template = get_option('ewc_alert_template', '');
        if (empty($template)) {
            $template = "ALERT: Employee [name] has reported feeling [mood] for [days] consecutive days. Last report: [mood] on [date_time]. Please follow up.";
        }
        
        $data = array(
            'name' => $user->display_name,
            'mood' => ucfirst($mood_value),
            'days' => $consecutive_days,
            'date_time' => current_time('mysql')
        );
        
        $message = self::replace_placeholders($template, $data);
        
        $subject = sprintf(
            __('[ALERT] [%s] Employee Wellness Check-in', 'employee-wellness-checkin'),
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Send email to each recipient
        foreach ($emails as $email) {
            if (!is_email($email)) {
                continue;
            }
            
            wp_mail($email, $subject, $message, $headers);
        }
        
        return true;
    }
}