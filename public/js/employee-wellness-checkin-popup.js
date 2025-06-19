(function($) {
    'use strict';

    class WellnessPopup {
        constructor() {
            this.modal = $('#ewc-popup-modal');
            this.storageKey = 'ewc_popup_dismissed_' + new Date().toDateString();
            this.init();
        }

        init() {
            // Check if user has already submitted today
            if (this.hasSubmittedToday()) {
                return;
            }

            // Initialize based on trigger type
            if (ewc_popup.trigger === 'login') {
                this.showPopup();
            } else if (ewc_popup.trigger === 'delay') {
                setTimeout(() => this.showPopup(), ewc_popup.delay * 60 * 1000);
            }

            // Event listeners
            this.bindEvents();
        }

        hasSubmittedToday() {
            return localStorage.getItem(this.storageKey) === 'true';
        }

        bindEvents() {
            $('.ewc-popup-close, #ewc-remind-later').on('click', () => {
                this.hidePopup();
                this.setDismissed();
            });

            $('#ewc-go-to-form').on('click', () => {
                window.location.href = ewc_popup.form_url;
            });

            // Close on outside click
            $(window).on('click', (e) => {
                if ($(e.target).is(this.modal)) {
                    this.hidePopup();
                    this.setDismissed();
                }
            });
        }

        showPopup() {
            if (!this.hasSubmittedToday()) {
                this.modal
                    .fadeIn(300)
                    .css('display', 'flex')
                    .find('.ewc-popup-content')
                    .css('transform', 'translateY(0)');
            }
        }

        hidePopup() {
            this.modal.fadeOut(300);
            this.modal.find('.ewc-popup-content').css('transform', 'translateY(-20px)');
        }

        setDismissed() {
            localStorage.setItem(this.storageKey, 'true');
        }
    }

    // Initialize on document ready and after login
    $(document).ready(() => {
        if (typeof ewc_popup !== 'undefined' && ewc_popup.enabled) {
            new WellnessPopup();
        }
    });

})(jQuery);
