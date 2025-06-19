/**
 * Public-facing JavaScript for the Employee Wellness Check-in plugin.
 *
 * Handles the mood selection interface and AJAX submission.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

(function($) {
    'use strict';

    // Store DOM elements and state
    const state = {
        selectedMood: null,
        isSubmitting: false
    };

    const elements = {
        moodOptions: '.ewc-mood-option',
        reasonContainer: '.ewc-reason-container',
        reasonInput: '#ewc-mood-reason',
        submitButton: '.ewc-submit-button',
        loadingSpinner: '.ewc-loading-spinner',
        statusContainer: '.ewc-submission-status',
        moodInput: '#ewc-selected-mood'
    };

    /**
     * Initialize the mood selection functionality.
     */
    function initMoodSelection() {
        // Check if user has already submitted today
        if (typeof ewc_form_status !== 'undefined' && ewc_form_status.has_submitted_today) {
            disableForm();
            if (ewc_form_status.today_mood) {
                // Show today's submission
                const $selectedMood = $(elements.moodOptions).filter(`[data-mood="${ewc_form_status.today_mood}"]`);
                $selectedMood.addClass('ewc-selected');
                $(elements.reasonInput).val(ewc_form_status.today_reason);
            }
            showSuccess(ewc_form_status.messages.already_submitted);
            return;
        }

        // Handle mood option click with debounce
        let clickTimeout;
        $(elements.moodOptions).on('click', function(e) {
            e.preventDefault();
            
            if (state.isSubmitting) return;

            const $this = $(this);
            const mood = $this.data('mood');

            // Clear previous timeout
            if (clickTimeout) clearTimeout(clickTimeout);

            // Visual feedback
            $(elements.moodOptions).removeClass('ewc-selected');
            $this.addClass('ewc-selected');
            
            // Animate selection with CSS transform
            $this.css('transform', 'scale(1.1)');
            clickTimeout = setTimeout(() => {
                $this.css('transform', 'scale(1.05)');
            }, 200);

            // Store selected mood
            state.selectedMood = mood;
            $(elements.moodInput).val(mood);

            // Show reason input
            $(elements.reasonContainer).slideDown(300);
            $(elements.submitButton).fadeIn(300);
        });
    }

    /**
     * Submit the selected mood via AJAX.
     */
    function initSubmission() {
        $(elements.submitButton).on('click', function(e) {
            e.preventDefault();
            
            if (state.isSubmitting || !state.selectedMood) return;

            // Basic validation
            if (!validateForm()) {
                showError('Please select a mood first.');
                return;
            }

            state.isSubmitting = true;
            showLoading(true);

            // Prepare submission data
            const data = {
                action: 'ewc_submit_mood',
                mood: state.selectedMood,
                reason: $(elements.reasonInput).val().trim(),
                nonce: ewc_ajax.nonce
            };

            // Send AJAX request
            $.ajax({
                url: ewc_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: handleSubmissionSuccess,
                error: handleSubmissionError,
                complete: () => {
                    state.isSubmitting = false;
                    showLoading(false);
                }
            });
        });
    }

    /**
     * Handle successful mood submission.
     */
    function handleSubmissionSuccess(response) {
        if (response.success) {
            showSuccess(response.data.message);
            disableForm();
        } else {
            showError(response.data ? response.data.message : ewc_ajax.messages.error);
        }
    }

    /**
     * Handle failed mood submission.
     */
    function handleSubmissionError() {
        showError(ewc_ajax.messages.error);
    }

    /**
     * Show loading state.
     */
    function showLoading(show) {
        $(elements.loadingSpinner).toggle(show);
        $(elements.submitButton).prop('disabled', show);
    }

    /**
     * Show success message.
     */
    function showSuccess(message) {
        $(elements.statusContainer).html(
            `<p class="ewc-status-message ewc-success">${message}</p>`
        ).hide().fadeIn(300);
    }

    /**
     * Show error message.
     */
    function showError(message) {
        $(elements.statusContainer).html(
            `<p class="ewc-status-message ewc-error">${message}</p>`
        ).hide().fadeIn(300);
    }

    /**
     * Validate form before submission.
     */
    function validateForm() {
        return state.selectedMood !== null;
    }

    /**
     * Disable form after successful submission.
     */
    function disableForm() {
        $(elements.moodOptions).addClass('ewc-disabled').css('pointer-events', 'none');
        $(elements.reasonInput).prop('disabled', true);
        $(elements.submitButton).prop('disabled', true).addClass('ewc-disabled');
        $(elements.reasonContainer).show(); // Show container to display existing reason if any
    }

    /**
     * Initialize the history view functionality.
     */
    function initHistoryView() {
        const modal = document.getElementById('ewc-history-modal');
        const closeButtons = document.getElementsByClassName('ewc-modal-close');

        if (!modal) return; // Exit if modal doesn't exist

        // Close modal when clicking the close button
        if (closeButtons.length > 0) {
            Array.from(closeButtons).forEach(button => {
                button.onclick = () => modal.style.display = 'none';
            });
        }

        // Close modal when clicking outside
        window.onclick = (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        // Handle view history click
        $('.ewc-view-history').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            
            // Show the modal
            if (modal) {
                modal.style.display = 'block';
                $('#ewc-history-content').html('<p>' + (ewc_ajax.messages.loading || 'Loading history...') + '</p>');
            }
            
            // Load user history via AJAX
            $.ajax({
                url: ewc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewc_get_user_history',
                    user_id: userId,
                    nonce: ewc_ajax.nonce
                },
                success: function(response) {
                    handleHistoryResponse(response);
                },
                error: function(xhr, status, error) {
                    $('#ewc-history-content').html('<p class="ewc-error">' + 
                        (ewc_ajax.messages.error || 'Error loading history. Please try again.') + '</p>');
                    console.error('AJAX Error:', status, error);
                }
            });
        });
    }

    /**
     * Handle history response from AJAX call
     */
    function handleHistoryResponse(response) {
        if (response.success && response.data) {
            var historyHtml = buildHistoryTable(response.data);
            $('#ewc-history-content').html(historyHtml);
        } else {
            $('#ewc-history-content').html('<p class="ewc-error">' + 
                (response.data ? response.data.message : (ewc_ajax.messages.error || 'Unknown error')) + '</p>');
        }
    }

    /**
     * Build history table HTML
     */
    function buildHistoryTable(data) {
        var html = '<h3>' + data.user_name + '\'s History</h3>';
        html += '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr><th>Date</th><th>Mood</th><th>Reason</th></tr></thead><tbody>';
        
        if (data.history && data.history.length > 0) {
            data.history.forEach(function(entry) {
                html += '<tr>';
                html += '<td>' + entry.date + '</td>';
                html += '<td class="ewc-mood-cell">';
                html += '<div class="ewc-mood-indicator" style="color: ' + entry.color + '">';
                html += '<span class="ewc-mood-icon">' + entry.icon + '</span> ';
                html += '<span class="ewc-mood-label">' + entry.mood + '</span>';
                html += '</div></td>';
                html += '<td>' + (entry.reason || '<em>No reason provided</em>') + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="3">' + (ewc_ajax.messages.no_data || 'No history records found.') + '</td></tr>';
        }
        
        html += '</tbody></table>';
        return html;
    }

    /**
     * Initialize everything when the document is ready.
     */
    $(document).ready(function() {
        initMoodSelection();
        initSubmission();
        initHistoryView(); // Make sure this is called
    });

})(jQuery);