<?php
/**
 * Admin dashboard page template.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get database instance
$db = new Employee_Wellness_Checkin_DB();

// Get all WordPress users
$users = get_users(array('fields' => array('ID', 'display_name', 'user_email')));

// Get alert threshold
$alert_threshold = get_option('ewc_alert_threshold', 10);

// Define mood icons and labels
$mood_icons = array(
    'happy' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
    'okay' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
    'tired' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M21 12.79A9 9 0 1 1 11.21 3 A7 7 0 0 0 21 12.79z"></path></svg>',
    'stressed' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path></svg>',
    'anxious' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 16s-1.5-2-4-2-4 2-4 2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>'
);

$mood_labels = array(
    'happy' => __('Happy', 'employee-wellness-checkin'),
    'okay' => __('Okay', 'employee-wellness-checkin'),
    'tired' => __('Tired', 'employee-wellness-checkin'),
    'stressed' => __('Stressed', 'employee-wellness-checkin'),
    'anxious' => __('Anxious', 'employee-wellness-checkin')
);

// Define mood colors for visual indicators
$mood_colors = array(
    'happy' => '#4CAF50',
    'okay' => '#2196F3',
    'tired' => '#FF9800',
    'stressed' => '#F44336',
    'anxious' => '#9C27B0'
);

// Pagination settings
$items_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10;
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$total_users = count($users);
$total_pages = ceil($total_users / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;

// Slice the users array for current page
$paged_users = array_slice($users, $offset, $items_per_page);

// Move this code before the pagination calculation
$filtered_users = $users;

// Apply search filter if present
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
if (!empty($search_term)) {
    $filtered_users = array_filter($filtered_users, function($user) use ($search_term) {
        return (stripos($user->display_name, $search_term) !== false || 
                stripos($user->user_email, $search_term) !== false);
    });
}

// Apply mood filter if present
if (isset($_GET['mood_filter']) && !empty($_GET['mood_filter'])) {
    $mood_filter = sanitize_text_field($_GET['mood_filter']);
    $filtered_users = array_filter($filtered_users, function($user) use ($db, $mood_filter) {
        $latest_mood = $db->get_latest_mood($user->ID);
        return ($latest_mood && $latest_mood->mood_value === $mood_filter);
    });
}

// Update pagination calculations with filtered users
$total_users = count($filtered_users);
$total_pages = ceil($total_users / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;
$paged_users = array_slice($filtered_users, $offset, $items_per_page);

// Replace the search bar HTML with this form
?>


<div class="wrap ewc-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Add Search Bar -->
    <div class="ewc-search-bar">
    <form method="get" action="" id="ewc-search-form">
        <input type="hidden" name="page" value="employee-wellness-dashboard">
        <?php if (isset($_GET['per_page'])): ?>
            <input type="hidden" name="per_page" value="<?php echo esc_attr($_GET['per_page']); ?>">
        <?php endif; ?>
        <?php if (isset($_GET['mood_filter'])): ?>
            <input type="hidden" name="mood_filter" value="<?php echo esc_attr($_GET['mood_filter']); ?>">
        <?php endif; ?>
        <input type="text" 
               name="search" 
               id="ewc-employee-search" 
               value="<?php echo esc_attr($search_term); ?>"
               placeholder="<?php _e('Search employees...', 'employee-wellness-checkin'); ?>">
        <button type="submit" class="button">
            <?php _e('Search', 'employee-wellness-checkin'); ?>
        </button>
        <?php if (!empty($search_term)): ?>
            <a href="<?php echo esc_url(remove_query_arg('search')); ?>" class="button">
                <?php _e('Clear Search', 'employee-wellness-checkin'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
    
    <!-- Add Statistics Summary -->
    <div class="ewc-statistics-summary">
        <?php
        // Calculate statistics
        $total_employees = count($users);
        $happy_employees = 0;
        $stressed_tired_employees = 0;
        $mood_distribution = array_fill_keys(array_keys($mood_labels), 0);
        
        foreach ($users as $user) {
            $latest_mood = $db->get_latest_mood($user->ID);
            if ($latest_mood) {
                if ($latest_mood->mood_value === 'happy') {
                    $happy_employees++;
                } elseif (in_array($latest_mood->mood_value, array('stressed', 'tired'))) {
                    $stressed_tired_employees++;
                }
                $mood_distribution[$latest_mood->mood_value]++;
            }
        }
        ?>
        <div class="ewc-stat-box">
            <span class="dashicons dashicons-groups"></span>
            <div class="ewc-stat-content">
                <span class="ewc-stat-number"><?php echo esc_html($total_employees); ?></span>
                <span class="ewc-stat-label"><?php _e('Total Employees', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
        
        <div class="ewc-stat-box">
            <span class="dashicons dashicons-smiley" style="color: #4CAF50;"></span>
            <div class="ewc-stat-content">
                <span class="ewc-stat-number"><?php echo esc_html($happy_employees); ?></span>
                <span class="ewc-stat-label"><?php _e('Happy Employees', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
        
        <div class="ewc-stat-box">
            <span class="dashicons dashicons-warning" style="color: #F44336;"></span>
            <div class="ewc-stat-content">
                <span class="ewc-stat-number"><?php echo esc_html($stressed_tired_employees); ?></span>
                <span class="ewc-stat-label"><?php _e('Stressed/Tired', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Add Mood Distribution Chart -->
    <div class="ewc-chart-container">
        <canvas id="ewc-mood-distribution"></canvas>
    </div>
    
    <div class="ewc-dashboard-filters">
        <form method="get">
            <input type="hidden" name="page" value="employee-wellness-dashboard">
            
            <select name="mood_filter">
                <option value=""><?php _e('All Moods', 'employee-wellness-checkin'); ?></option>
                <?php foreach ($mood_labels as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($_GET['mood_filter']) ? $_GET['mood_filter'] : '', $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="days_filter">
                <option value=""><?php _e('All Days', 'employee-wellness-checkin'); ?></option>
                <option value="7" <?php selected(isset($_GET['days_filter']) ? $_GET['days_filter'] : '', '7'); ?>>
                    <?php _e('Last 7 Days', 'employee-wellness-checkin'); ?>
                </option>
                <option value="30" <?php selected(isset($_GET['days_filter']) ? $_GET['days_filter'] : '', '30'); ?>>
                    <?php _e('Last 30 Days', 'employee-wellness-checkin'); ?>
                </option>
            </select>
            
            <button type="submit" class="button"><?php _e('Filter', 'employee-wellness-checkin'); ?></button>
        </form>
    </div>
    
    <!-- Add per page selector -->
    <div class="ewc-table-controls">
        <div class="ewc-items-per-page">
            <form method="get" action="">
                <input type="hidden" name="page" value="employee-wellness-dashboard">
                <label for="ewc-per-page"><?php _e('Show:', 'employee-wellness-checkin'); ?></label>
                <select id="ewc-per-page" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $per_page) : ?>
                        <option value="<?php echo $per_page; ?>" <?php selected($items_per_page, $per_page); ?>>
                            <?php echo $per_page; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span><?php _e('entries', 'employee-wellness-checkin'); ?></span>
            </form>
        </div>
        
        <div class="ewc-showing-entries">
            <?php
            $showing_start = $offset + 1;
            $showing_end = min($offset + $items_per_page, $total_users);
            printf(
                __('Showing %d to %d of %d entries', 'employee-wellness-checkin'),
                $showing_start,
                $showing_end,
                $total_users
            );
            ?>
        </div>
    </div>
    
    <div class="ewc-dashboard-table-container">
        <table class="wp-list-table widefat fixed striped ewc-dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Employee', 'employee-wellness-checkin'); ?></th>
                    <th><?php _e('Last Mood', 'employee-wellness-checkin'); ?></th>
                    <th><?php _e('Submission Date', 'employee-wellness-checkin'); ?></th>
                    <th><?php _e('Consecutive Stress/Tired Days', 'employee-wellness-checkin'); ?></th>
                    <th><?php _e('Mood History', 'employee-wellness-checkin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paged_users as $user) : 
                    // Get user's latest mood
                    $latest_mood = $db->get_latest_mood($user->ID);
                    
                    // Get consecutive stress/tired days
                    $consecutive_days = $db->get_consecutive_mood_days($user->ID, array('stressed', 'tired'));
                    
                    // Apply filters if set
                    if (isset($_GET['mood_filter']) && !empty($_GET['mood_filter']) && 
                        ($latest_mood === null || $latest_mood->mood_value !== $_GET['mood_filter'])) {
                        continue;
                    }
                    
                    // Get mood history for sparkline
                    $mood_history = $db->get_user_mood_history($user->ID, 10);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($user->display_name); ?></strong><br>
                        <small><?php echo esc_html($user->user_email); ?></small>
                    </td>
                    <td>
                        <?php if ($latest_mood) : ?>
                            <div class="ewc-mood-indicator" style="color: <?php echo esc_attr($mood_colors[$latest_mood->mood_value]); ?>">
                                <span class="ewc-mood-icon"><?php echo $mood_icons[$latest_mood->mood_value]; ?></span>
                                <span class="ewc-mood-label"><?php echo esc_html($mood_labels[$latest_mood->mood_value]); ?></span>
                            </div>
                        <?php else : ?>
                            <em><?php _e('No submissions yet', 'employee-wellness-checkin'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($latest_mood) : ?>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($latest_mood->submission_timestamp))); ?>
                        <?php else : ?>
                            <em><?php _e('N/A', 'employee-wellness-checkin'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($consecutive_days > 0) : ?>
                            <span class="ewc-consecutive-days <?php echo ($consecutive_days >= $alert_threshold) ? 'ewc-alert' : ''; ?>">
                                <?php echo esc_html($consecutive_days); ?>
                                <?php if ($consecutive_days >= $alert_threshold) : ?>
                                    <span class="dashicons dashicons-warning"></span>
                                <?php endif; ?>
                            </span>
                        <?php else : ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($mood_history)) : ?>
                            <div class="ewc-mood-history">
                                <?php foreach ($mood_history as $mood) : ?>
                                    <span class="ewc-mood-dot" 
                                          title="<?php echo esc_attr($mood_labels[$mood->mood_value] . ' - ' . date_i18n(get_option('date_format'), strtotime($mood->submission_timestamp))); ?>"
                                          style="background-color: <?php echo esc_attr($mood_colors[$mood->mood_value]); ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <a href="#" class="ewc-view-history" data-user-id="<?php echo esc_attr($user->ID); ?>">
                                <?php _e('View Full History', 'employee-wellness-checkin'); ?>
                            </a>
                        <?php else : ?>
                            <em><?php _e('No history available', 'employee-wellness-checkin'); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add pagination after the table -->
    <?php if ($total_pages > 1) : ?>
        <div class="ewc-pagination">
            <?php
            // Previous page link
            if ($current_page > 1) {
                $prev_url = add_query_arg(['paged' => $current_page - 1, 'per_page' => $items_per_page]);
                printf(
                    '<a href="%s" class="ewc-page-link">&laquo; %s</a>',
                    esc_url($prev_url),
                    __('Previous', 'employee-wellness-checkin')
                );
            }

            // Calculate the range of pages to show
            $range = 2; // Number of pages to show on each side of current page
            $showitems = ($range * 2) + 1;

            // Always show first page
            if ($current_page > $range + 1) {
                $page_url = add_query_arg(['paged' => 1, 'per_page' => $items_per_page]);
                printf(
                    '<a href="%s" class="ewc-page-link">1</a>',
                    esc_url($page_url)
                );
                // Add ellipsis if needed
                if ($current_page > $range + 2) {
                    echo '<span class="ewc-page-dots">...</span>';
                }
            }

            // Show pages around current page
            for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
                $class = $i === $current_page ? 'ewc-page-link current' : 'ewc-page-link';
                $page_url = add_query_arg(['paged' => $i, 'per_page' => $items_per_page]);
                printf(
                    '<a href="%s" class="%s">%d</a>',
                    esc_url($page_url),
                    $class,
                    $i
                );
            }

            // Always show last page
            if ($current_page < $total_pages - $range) {
                // Add ellipsis if needed
                if ($current_page < $total_pages - $range - 1) {
                    echo '<span class="ewc-page-dots">...</span>';
                }
                $page_url = add_query_arg(['paged' => $total_pages, 'per_page' => $items_per_page]);
                printf(
                    '<a href="%s" class="ewc-page-link">%d</a>',
                    esc_url($page_url),
                    $total_pages
                );
            }

            // Next page link
            if ($current_page < $total_pages) {
                $next_url = add_query_arg(['paged' => $current_page + 1, 'per_page' => $items_per_page]);
                printf(
                    '<a href="%s" class="ewc-page-link">%s &raquo;</a>',
                    esc_url($next_url),
                    __('Next', 'employee-wellness-checkin')
                );
            }
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Modal for viewing full history -->
    <div id="ewc-history-modal" class="ewc-modal">
        <div class="ewc-modal-content">
            <span class="ewc-modal-close">&times;</span>
            <h2><?php _e('Mood History', 'employee-wellness-checkin'); ?></h2>
            <div id="ewc-history-content">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'employee-wellness-checkin'); ?></th>
                            <th><?php _e('Mood', 'employee-wellness-checkin'); ?></th>
                            <th><?php _e('Reason', 'employee-wellness-checkin'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="ewc-history-table-content">
                        <!-- Content will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>