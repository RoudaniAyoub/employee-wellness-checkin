<?php
/**
 * Dashboard shortcode template.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure user has permission
if (!current_user_can('view_wellness_reports')) {
    return '<p>' . __('You do not have permission to view this dashboard.', 'employee-wellness-checkin') . '</p>';
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
?>

<div class="ewc-dashboard-shortcode ewc-frontend-dashboard">
    <h2><?php _e('Employee Wellness Dashboard', 'employee-wellness-checkin'); ?></h2>
    
    <!-- Statistics Summary -->
    <div class="ewc-statistics-grid">
        <?php
        $total_employees = count($users);
        $happy_count = $db->get_mood_count('happy');
        $stressed_tired_count = $db->get_mood_count(['stressed', 'tired']);
        ?>
        <div class="ewc-stat-card ewc-total">
            <i class="dashicons dashicons-groups"></i>
            <div>
                <span class="ewc-stat-number"><?php echo $total_employees; ?></span>
                <span class="ewc-stat-label"><?php _e('Total Employees', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
        <div class="ewc-stat-card ewc-happy">
            <i class="dashicons dashicons-smiley"></i>
            <div>
                <span class="ewc-stat-number"><?php echo $happy_count; ?></span>
                <span class="ewc-stat-label"><?php _e('Happy Today', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
        <div class="ewc-stat-card ewc-stressed">
            <i class="dashicons dashicons-warning"></i>
            <div>
                <span class="ewc-stat-number"><?php echo $stressed_tired_count; ?></span>
                <span class="ewc-stat-label"><?php _e('Need Attention', 'employee-wellness-checkin'); ?></span>
            </div>
        </div>
    </div>

    <!-- Mood Distribution Chart -->
    <div class="ewc-chart-wrapper">
        <canvas id="ewc-mood-distribution-chart"></canvas>
    </div>

    <div class="all-dashboard-fields">
    <!-- Employee Search -->
    <div class="ewc-search-bar">
        <form method="get" action="" id="ewc-search-form">
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

    <div class="ewc-dashboard-filters">
        <form method="get" style="
    display: flex;
    flex-direction: row;
    align-content: center;
    flex-wrap: nowrap;
    gap: 10px;
">
            <?php foreach ($_GET as $key => $value) : 
                if ($key !== 'mood_filter' && $key !== 'days_filter') : ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                <?php endif;
            endforeach; ?>
            
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
    </div>

    <?php
    // Use the same search and filter logic as the admin dashboard
    // Add this code before pagination calculation
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

    // Pagination settings
    $items_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10;
    $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $total_users = count($filtered_users);
    $total_pages = ceil($total_users / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;

    // Slice the users array for current page
    $paged_users = array_slice($filtered_users, $offset, $items_per_page);
    ?>

    <!-- Add per page selector before the table -->
    <div class="ewc-table-controls">
        <div class="ewc-items-per-page">
            <label for="ewc-per-page"><?php _e('Show:', 'employee-wellness-checkin'); ?></label>
            <select id="ewc-per-page" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $per_page) : ?>
                    <option value="<?php echo $per_page; ?>" <?php selected($items_per_page, $per_page); ?>>
                        <?php echo $per_page; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span><?php _e('entries', 'employee-wellness-checkin'); ?></span>
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
        <table class="ewc-dashboard-table">
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
                printf(
                    '<a href="%s" class="ewc-page-link">&laquo; %s</a>',
                    add_query_arg('paged', $current_page - 1),
                    __('Previous', 'employee-wellness-checkin')
                );
            }

            // Calculate the range of pages to show
            $range = 2; // Number of pages to show on each side of current page
            $showitems = ($range * 2) + 1;

            // Always show first page
            if ($current_page > $range + 1) {
                printf(
                    '<a href="%s" class="ewc-page-link">1</a>',
                    add_query_arg('paged', 1)
                );
                // Add ellipsis if needed
                if ($current_page > $range + 2) {
                    echo '<span class="ewc-page-dots">...</span>';
                }
            }

            // Show pages around current page
            for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
                $class = $i === $current_page ? 'ewc-page-link current' : 'ewc-page-link';
                printf(
                    '<a href="%s" class="%s">%d</a>',
                    add_query_arg('paged', $i),
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
                printf(
                    '<a href="%s" class="ewc-page-link">%d</a>',
                    add_query_arg('paged', $total_pages),
                    $total_pages
                );
            }

            // Next page link
            if ($current_page < $total_pages) {
                printf(
                    '<a href="%s" class="ewc-page-link">%s &raquo;</a>',
                    add_query_arg('paged', $current_page + 1),
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
            <div id="ewc-history-content"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize Chart.js with explicit frontend context
    const chartCtx = document.getElementById('ewc-mood-distribution-chart');
    if (chartCtx) {
        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_values($mood_labels)); ?>,
                datasets: [{
                    label: 'Mood Distribution',
                    data: <?php echo json_encode(array_values($db->get_mood_distribution())); ?>,
                    backgroundColor: <?php echo json_encode(array_values($mood_colors)); ?>,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Initialize search with explicit frontend context
    const searchInput = document.getElementById('ewc-employee-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.ewc-frontend-dashboard .ewc-dashboard-table tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first').textContent.toLowerCase();
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Reinitialize history modal for frontend
    $('.ewc-frontend-dashboard .ewc-view-history').on('click', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const modal = $('#ewc-history-modal');
        
        modal.show();
        $('#ewc-history-content').html('<p>' + ewc_admin_ajax.messages.loading + '</p>');
        
        $.ajax({
            url: ewc_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewc_get_user_history',
                user_id: userId,
                nonce: ewc_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ewc-history-content').html(response.data.html);
                    // Format dates in the modal content
                    $('#ewc-history-content .ewc-history-date').each(function() {
                        const timestamp = $(this).data('timestamp');
                        const formattedDate = new Date(timestamp * 1000).toLocaleDateString(
                            '<?php echo str_replace('_', '-', get_locale()); ?>',
                            {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: 'numeric',
                                second: 'numeric'
                            }
                        );
                        $(this).text(formattedDate);
                    });
                } else {
                    $('#ewc-history-content').html('<p class="ewc-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#ewc-history-content').html('<p class="ewc-error">' + ewc_admin_ajax.messages.error + '</p>');
            }
        });
    });
});
</script>

<style>
/* Dashboard shortcode styles */
.ewc-dashboard-shortcode {
    max-width: 100%;
    margin: 20px 0;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.ewc-dashboard-shortcode h2 {
    margin-bottom: 20px;
    color: #333;
}

.ewc-statistics-grid {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.ewc-stat-card {
    flex: 1;
    padding: 20px;
    background-color: #f5f5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
}

.ewc-stat-card i {
    font-size: 36px;
    color: #333;
}

.ewc-stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.ewc-stat-label {
    font-size: 14px;
    color: #666;
}

.ewc-chart-wrapper {
    margin-bottom: 20px;
    position: relative;
    height: 300px;
}

.ewc-search-wrapper {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ewc-search-wrapper input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.ewc-dashboard-filters {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.ewc-dashboard-filters select {
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.ewc-dashboard-table-container {
    overflow-x: auto;
}

.ewc-dashboard-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.ewc-dashboard-table th,
.ewc-dashboard-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.ewc-dashboard-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.ewc-dashboard-table tr:hover {
    background-color: #f9f9f9;
}

.ewc-mood-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ewc-mood-icon svg {
    vertical-align: middle;
}

.ewc-consecutive-days {
    font-weight: 600;
}

.ewc-consecutive-days.ewc-alert {
    color: #F44336;
}

.ewc-mood-history {
    display: flex;
    gap: 5px;
    margin-bottom: 8px;
}

.ewc-mood-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.ewc-view-history {
    font-size: 14px;
    text-decoration: none;
}

/* Pagination styles */
.ewc-pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 5px;
}

.ewc-page-link {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.ewc-page-link.current {
    background-color: #2196F3;
    color: #fff;
    font-weight: bold;
}

.ewc-page-link:hover {
    background-color: #f5f5f5;
}

/* Modal styles */
.ewc-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.ewc-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 700px;
    border-radius: 8px;
    position: relative;
}

.ewc-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.ewc-modal-close:hover,
.ewc-modal-close:focus {
    color: black;
    text-decoration: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .ewc-dashboard-table th,
    .ewc-dashboard-table td {
        padding: 8px 10px;
    }
    
    .ewc-mood-icon svg {
        width: 20px;
        height: 20px;
    }
}
</style>