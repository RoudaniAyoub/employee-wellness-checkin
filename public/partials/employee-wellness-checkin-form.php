<?php
/**
 * Public-facing mood submission form template.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define mood icons and labels
$mood_icons = array(
    'happy' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
    'okay' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>',
    'tired' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="15" x2="16" y2="15"></line><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M21 12.79A9 9 0 1 1 11.21 3 A7 7 0 0 0 21 12.79z"></path></svg>',
    'stressed' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 16s-1.5-2-4-2-4 2-4 2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M12 12.5a.5.5 0 0 1-.5-.5.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5.5.5 0 0 1-.5.5z"></path></svg>',
    'anxious' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 16s-1.5-2-4-2-4 2-4 2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>'
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

<div class="ewc-checkin-container">
    <h2><?php _e('How are you feeling today?', 'employee-wellness-checkin'); ?></h2>
    
    <?php if (isset($today_mood) && $today_mood): ?>
        <div class="ewc-notice">
            <p><?php _e('You have already submitted your wellness check-in for today. You can submit again tomorrow.', 'employee-wellness-checkin'); ?></p>
            <p class="ewc-today-submission">
                <strong><?php _e('Your submission for today:', 'employee-wellness-checkin'); ?></strong>
                <?php echo esc_html(ucfirst($today_mood->mood_value)); ?>
                <?php if (!empty($today_mood->mood_reason)): ?>
                    <br>
                    <em><?php echo esc_html($today_mood->mood_reason); ?></em>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="ewc-mood-selection">
        <?php foreach ($mood_icons as $mood_value => $icon) : ?>
            <div class="ewc-mood-option <?php echo (isset($today_mood) && $today_mood && $today_mood->mood_value === $mood_value) ? 'ewc-selected' : ''; ?>" 
                 data-mood="<?php echo esc_attr($mood_value); ?>"
                 style="--mood-color: <?php echo esc_attr($mood_colors[$mood_value]); ?>">
                <div class="ewc-mood-icon">
                    <?php echo $icon; ?>
                </div>
                <div class="ewc-mood-label">
                    <?php echo esc_html($mood_labels[$mood_value]); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="ewc-reason-container" style="display: none;">
        <label for="ewc-mood-reason"><?php _e('Reason (optional)', 'employee-wellness-checkin'); ?></label>
        <textarea id="ewc-mood-reason" name="mood_reason" rows="3" placeholder="<?php _e('Why do you feel this way today?', 'employee-wellness-checkin'); ?>"></textarea>
        <input type="hidden" id="ewc-selected-mood" value="">
        <button class="ewc-submit-button" style="display: none;"><?php _e('Submit', 'employee-wellness-checkin'); ?></button>
    </div>
    
    <div class="ewc-submission-status">
        <?php if (isset($today_mood) && $today_mood) : ?>
            <p class="ewc-status-message ewc-success">
                <?php _e('You ve already shared your mood today. You can update it if you d like.', 'employee-wellness-checkin'); ?>
            </p>
            <?php if (!empty($today_mood->mood_reason)) : ?>
                <p class="ewc-reason-display">
                    <strong><?php _e('Your reason:', 'employee-wellness-checkin'); ?></strong> 
                    <?php echo esc_html($today_mood->mood_reason); ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="ewc-loading-spinner" style="display: none;">
        <div class="ewc-spinner"></div>
    </div>
</div>

<style>
/* Custom CSS for the mood selection form */
.ewc-checkin-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.ewc-checkin-container h2 {
    margin-bottom: 30px;
    color: #333;
    font-size: 24px;
}

.ewc-mood-selection {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.ewc-reason-container {
    margin-top: 20px;
    width: 100%;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.ewc-reason-container label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

#ewc-mood-reason {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    font-family: inherit;
    margin-bottom: 15px;
}

.ewc-submit-button {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

.ewc-submit-button:hover {
    background-color: #45a049;
}

.ewc-submit-button.ewc-disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.ewc-disabled {
    opacity: 0.6;
    pointer-events: none;
}

.ewc-reason-display {
    background-color: #f9f9f9;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 10px;
    text-align: left;
    border-left: 4px solid #4CAF50;
}

.ewc-mood-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 120px;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.ewc-mood-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    border-color: var(--mood-color, #ddd);
}

.ewc-mood-option.ewc-selected {
    background-color: rgba(var(--mood-color, #ddd), 0.1);
    border-color: var(--mood-color, #ddd);
    transform: scale(1.05);
}

.ewc-mood-icon {
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.ewc-mood-icon svg {
    stroke: var(--mood-color, #333);
    transition: all 0.3s ease;
}

.ewc-mood-option:hover .ewc-mood-icon svg {
    stroke-width: 2.5;
}

.ewc-mood-label {
    font-weight: 600;
    color: var(--mood-color, #333);
}

.ewc-submission-status {
    min-height: 50px;
}

.ewc-status-message {
    padding: 10px 15px;
    border-radius: 4px;
    font-weight: 500;
}

.ewc-success {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}

.ewc-error {
    background-color: rgba(244, 67, 54, 0.1);
    color: #F44336;
}

/* Loading spinner */
.ewc-loading-spinner {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.ewc-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top-color: #2196F3;
    animation: ewc-spin 1s ease-in-out infinite;
}

@keyframes ewc-spin {
    to { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .ewc-mood-selection {
        gap: 10px;
    }
    
    .ewc-mood-option {
        width: 100px;
        padding: 10px;
    }
}

@media (max-width: 480px) {
    .ewc-mood-selection {
        flex-direction: column;
        align-items: center;
    }
    
    .ewc-mood-option {
        width: 80%;
        max-width: 200px;
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        padding: 10px 15px;
    }
    
    .ewc-mood-icon {
        margin-bottom: 0;
        margin-right: 15px;
    }
    
    .ewc-mood-icon svg {
        width: 40px;
        height: 40px;
    }
}

/* Add to existing styles */
.ewc-page-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
</style>