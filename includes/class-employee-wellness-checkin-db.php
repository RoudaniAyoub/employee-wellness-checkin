<?php
/**
 * Database operations for the Employee Wellness Check-in plugin.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

class Employee_Wellness_Checkin_DB {

    /**
     * The table name for mood submissions.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    The name of the database table for mood submissions.
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ewc_mood_submissions';
    }

    /**
     * Create the database table on plugin activation.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            mood_value varchar(50) NOT NULL,
            mood_reason text DEFAULT NULL,
            submission_timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY mood_value (mood_value),
            KEY submission_timestamp (submission_timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($sql);
    }

    /**
     * Submit or update a mood for a user.
     *
     * @since    1.0.0
     * @param    int       $user_id      The user ID.
     * @param    string    $mood_value   The mood value (e.g., 'happy', 'stressed').
     * @param    string    $mood_reason  Optional. The reason for the mood.
     * @return   int|false               The number of rows inserted, or false on error.
     */
    public function submit_mood($user_id, $mood_value, $mood_reason = '') {
        global $wpdb;
        
        // Check if user already submitted a mood today
        $today = date('Y-m-d');
        $existing = $this->get_mood_by_date($user_id, $today);
        
        if ($existing) {
            // Update existing submission for today
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'mood_value' => sanitize_text_field($mood_value),
                    'mood_reason' => sanitize_textarea_field($mood_reason)
                ),
                array('id' => $existing->id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new submission
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'mood_value' => sanitize_text_field($mood_value),
                    'mood_reason' => sanitize_textarea_field($mood_reason),
                    'submission_timestamp' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
        
        return $result;
    }

    /**
     * Get a user's mood submission for a specific date.
     *
     * @since    1.0.0
     * @param    int       $user_id   The user ID.
     * @param    string    $date      The date in Y-m-d format.
     * @return   object|null          The mood submission object or null if not found.
     */
    public function get_mood_by_date($user_id, $date) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            AND DATE(submission_timestamp) = %s
            LIMIT 1",
            $user_id,
            $date
        );
        
        return $wpdb->get_row($sql);
    }

    /**
     * Get a user's latest mood submission.
     *
     * @since    1.0.0
     * @param    int       $user_id   The user ID.
     * @return   object|null          The mood submission object or null if not found.
     */
    public function get_latest_mood($user_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY submission_timestamp DESC
            LIMIT 1",
            $user_id
        );
        
        return $wpdb->get_row($sql);
    }

    /**
     * Get all mood submissions for a user.
     *
     * @since    1.0.0
     * @param    int       $user_id   The user ID.
     * @param    int       $limit     Optional. Number of records to return. Default 30.
     * @return   array                Array of mood submission objects.
     */
    public function get_user_mood_history($user_id, $limit = 30) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY submission_timestamp DESC
            LIMIT %d",
            $user_id,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get all users who have reported stress or tiredness for consecutive days.
     *
     * @since    1.0.0
     * @param    int       $days_threshold   The number of consecutive days to check for.
     * @param    array     $mood_values      The mood values to check (e.g., ['stressed', 'tired']).
     * @return   array                       Array of user IDs with consecutive stress/tired days.
     */
    public function get_users_with_consecutive_moods($days_threshold, $mood_values) {
        global $wpdb;
        
        $users_with_alerts = array();
        
        // Get all WordPress users
        $users = get_users(array('fields' => 'ID'));
        
        foreach ($users as $user_id) {
            // Get recent submissions for this user
            $sql = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE user_id = %d 
                ORDER BY submission_timestamp DESC
                LIMIT %d",
                $user_id,
                $days_threshold + 5 // Get a few extra to account for missing days
            );
            
            $submissions = $wpdb->get_results($sql);
            
            if (count($submissions) < $days_threshold) {
                continue; // Not enough submissions to check
            }
            
            // Check for consecutive days with stress/tired moods
            $consecutive_count = 0;
            $last_date = null;
            
            foreach ($submissions as $submission) {
                $submission_date = date('Y-m-d', strtotime($submission->submission_timestamp));
                
                // If this is not the first submission, check if it's consecutive
                if ($last_date !== null) {
                    $days_diff = (strtotime($last_date) - strtotime($submission_date)) / (60 * 60 * 24);
                    
                    // If not consecutive, reset counter
                    if ($days_diff > 1) {
                        $consecutive_count = 0;
                    }
                }
                
                // Check if this submission is a stress/tired mood
                if (in_array($submission->mood_value, $mood_values)) {
                    $consecutive_count++;
                } else {
                    break; // Break the streak
                }
                
                $last_date = $submission_date;
                
                // If we've reached the threshold, add to alert list
                if ($consecutive_count >= $days_threshold) {
                    $users_with_alerts[] = $user_id;
                    break;
                }
            }
        }
        
        return $users_with_alerts;
    }

    /**
     * Get all mood submissions for all users.
     *
     * @since    1.0.0
     * @return   array    Array of mood submission objects with user data.
     */
    public function get_all_user_submissions() {
        global $wpdb;
        
        $sql = "SELECT s.*, u.display_name 
                FROM {$this->table_name} s
                JOIN {$wpdb->users} u ON s.user_id = u.ID
                ORDER BY s.submission_timestamp DESC";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get the consecutive days count for a specific mood.
     *
     * @since    1.0.0
     * @param    int       $user_id       The user ID.
     * @param    array     $mood_values   The mood values to check.
     * @return   int                      The number of consecutive days.
     */
    public function get_consecutive_mood_days($user_id, $mood_values) {
        global $wpdb;
        
        // Get recent submissions for this user
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY submission_timestamp DESC
            LIMIT 30", // Reasonable limit to check
            $user_id
        );
        
        $submissions = $wpdb->get_results($sql);
        
        if (empty($submissions)) {
            return 0;
        }
        
        // Check for consecutive days
        $consecutive_count = 0;
        $last_date = null;
        
        foreach ($submissions as $submission) {
            $submission_date = date('Y-m-d', strtotime($submission->submission_timestamp));
            
            // If this is not the first submission, check if it's consecutive
            if ($last_date !== null) {
                $days_diff = (strtotime($last_date) - strtotime($submission_date)) / (60 * 60 * 24);
                
                // If not consecutive, break the count
                if ($days_diff > 1) {
                    break;
                }
            }
            
            // Check if this submission is a stress/tired mood
            if (in_array($submission->mood_value, $mood_values)) {
                $consecutive_count++;
            } else {
                break; // Break the streak
            }
            
            $last_date = $submission_date;
        }
        
        return $consecutive_count;
    }

    /**
     * Get mood distribution statistics
     */
    public function get_mood_distribution() {
        global $wpdb;
        
        $distribution = array_fill_keys(array('happy', 'okay', 'tired', 'stressed', 'anxious'), 0);
        
        $sql = $wpdb->prepare(
            "SELECT mood_value, COUNT(*) as count
             FROM {$this->table_name}
             WHERE DATE(submission_timestamp) = %s
             GROUP BY mood_value",
            date('Y-m-d')
        );
        
        $results = $wpdb->get_results($sql);
        
        foreach ($results as $result) {
            $distribution[$result->mood_value] = (int)$result->count;
        }
        
        return $distribution;
    }

    /**
     * Get count of specific mood(s) for today
     */
    public function get_mood_count($moods) {
        global $wpdb;
        
        $moods = (array)$moods;
        $placeholders = array_fill(0, count($moods), '%s');
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) as count
             FROM {$this->table_name}
             WHERE DATE(submission_timestamp) = %s
             AND mood_value IN (" . implode(',', $placeholders) . ")",
            array_merge([date('Y-m-d')], $moods)
        );
        
        return (int)$wpdb->get_var($sql);
    }

    /**
     * Get mood percentage for the last X days
     */
    public function get_mood_percentage($moods, $days = 7) {
        global $wpdb;
        
        $moods = (array)$moods;
        $placeholders = array_fill(0, count($moods), '%s');
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) as count
             FROM {$this->table_name}
             WHERE submission_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)
             AND mood_value IN (" . implode(',', $placeholders) . ")",
            array_merge([$days], $moods)
        );
        
        $mood_count = (int)$wpdb->get_var($sql);
        
        // Get total submissions for the period
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) as total
             FROM {$this->table_name}
             WHERE submission_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)",
            $days
        );
        
        $total = (int)$wpdb->get_var($sql);
        
        return $total > 0 ? ($mood_count / $total) * 100 : 0;
    }

    /**
     * Get top reasons for stress/tiredness
     */
    public function get_top_stress_reasons($days = 7, $limit = 5) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT mood_reason as reason, COUNT(*) as count
             FROM {$this->table_name}
             WHERE submission_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)
             AND mood_value IN ('stressed', 'tired')
             AND mood_reason != ''
             GROUP BY mood_reason
             ORDER BY count DESC
             LIMIT %d",
            $days,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
}