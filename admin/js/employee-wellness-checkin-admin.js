/**
 * Admin JavaScript for the Employee Wellness Check-in plugin.
 *
 * @since      1.0.0
 * @package    Employee_Wellness_Checkin
 */

(function($) {
    'use strict';

    // Initialize the admin dashboard functionality
    $(document).ready(function() {
        initializeModal();
        initializeViewHistory();
        initializeSearch();
        initializeMoodChart();
    });

    function initializeModal() {
        var modal = document.getElementById('ewc-history-modal');
        var closeButtons = document.getElementsByClassName('ewc-modal-close');
        
        // Close modal when clicking the close button
        if (closeButtons.length > 0) {
            Array.from(closeButtons).forEach(function(button) {
                button.onclick = function() {
                    if (modal) modal.style.display = 'none';
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    }

    function initializeViewHistory() {
        $('.ewc-view-history').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            var modal = document.getElementById('ewc-history-modal');
            
            // Show modal and loading message
            if (modal) {
                modal.style.display = 'block';
                $('#ewc-history-content').html('<p>' + ewc_admin_ajax.messages.loading + '</p>');
            }
            
            // Fetch history via AJAX
            $.ajax({
                url: ewc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewc_get_user_history',
                    user_id: userId,
                    nonce: ewc_admin_ajax.nonce
                },
                success: function(response) {
                    handleHistoryResponse(response);
                },
                error: function() {
                    $('#ewc-history-content').html('<p class="ewc-error">' + ewc_admin_ajax.messages.error + '</p>');
                }
            });
        });
    }

    function handleHistoryResponse(response) {
        if (response.success && response.data) {
            var historyHtml = buildHistoryTable(response.data);
            $('#ewc-history-content').html(historyHtml);
        } else {
            $('#ewc-history-content').html('<p class="ewc-error">' + 
                (response.data ? response.data.message : ewc_admin_ajax.messages.error) + '</p>');
        }
    }

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
            html += '<tr><td colspan="3">' + ewc_admin_ajax.messages.no_data + '</td></tr>';
        }
        
        html += '</tbody></table>';
        return html;
    }

    function initializeSearch() {
        const searchForm = $('#ewc-search-form');
        const searchInput = $('#ewc-employee-search');
        let searchTimeout;

        // Real-time search for current page only
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase();
            
            // Show loading indicator
            $('.ewc-dashboard-table').css('opacity', '0.5');
            
            searchTimeout = setTimeout(() => {
                const rows = $('.ewc-dashboard-table tbody tr');
                rows.each(function() {
                    const row = $(this);
                    const name = row.find('td:first').text().toLowerCase();
                    const isMatch = name.includes(searchTerm);
                    row.toggle(isMatch);
                });
                
                // Remove loading indicator
                $('.ewc-dashboard-table').css('opacity', '1');
                
                // Update showing entries text
                updateShowingEntries();
            }, 300);
        });

        // Form submission for full search across all pages
        searchForm.on('submit', function(e) {
            const searchTerm = searchInput.val().trim();
            if (searchTerm === '') {
                e.preventDefault();
            }
        });
    }

    function updateShowingEntries() {
        const visibleRows = $('.ewc-dashboard-table tbody tr:visible').length;
        const totalRows = $('.ewc-dashboard-table tbody tr').length;
        $('.ewc-showing-entries').text(`Showing ${visibleRows} of ${totalRows} entries`);
    }

    function initializeMoodChart() {
        const ctx = document.getElementById('ewc-mood-distribution');
        if (!ctx) return;

        // Collect data from the table
        const moodCounts = {};
        $('.ewc-dashboard-table tbody tr').each(function() {
            const mood = $(this).find('.ewc-mood-label').text();
            if (mood) {
                moodCounts[mood] = (moodCounts[mood] || 0) + 1;
            }
        });

        // Create chart using Chart.js
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(moodCounts),
                datasets: [{
                    label: 'Mood Distribution',
                    data: Object.values(moodCounts),
                    backgroundColor: [
                        '#4CAF50', // happy
                        '#2196F3', // okay
                        '#FF9800', // tired
                        '#F44336', // stressed
                        '#9C27B0'  // anxious
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

})(jQuery);