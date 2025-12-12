/**
 * Advanced Security Lite - Admin JavaScript
 * Updated for new layout structure
 */

(function ($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function () {
        ASL_Admin.init();
    });

    // Main admin object
    var ASL_Admin = {

        // Initialize all functionality
        init: function () {
            this.initTabs();
            this.initToggles();
            this.initFormSubmission();
            this.initSpecialActions();
            this.initNotifications();
        },

        // Initialize horizontal tab navigation
        initTabs: function () {
            $('.asl-tab').on('click', function (e) {
                e.preventDefault();

                var targetPanel = $(this).data('tab');

                // Update active tab
                $('.asl-tab').removeClass('active');
                $(this).addClass('active');

                // Show target panel
                $('.asl-panel').removeClass('active');
                $('#panel-' + targetPanel).addClass('active');

                // Hide Save Bar if on About Tab
                if (targetPanel === 'about') {
                    $('.asl-save-bar').hide();
                } else {
                    $('.asl-save-bar').show();
                }

                // Update URL hash without scrolling
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetPanel);
                }
            });

            // Handle initial panel from URL hash
            var hash = window.location.hash.substring(1);
            if (hash && $('#panel-' + hash).length) {
                $('.asl-tab[data-tab="' + hash + '"]').trigger('click');
            }

            // Handle recommendation "Fix" links
            $(document).on('click', '.asl-rec-link', function (e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                if (targetTab) {
                    $('.asl-tab[data-tab="' + targetTab + '"]').trigger('click');
                    // Scroll to top of the panel
                    $('html, body').animate({
                        scrollTop: $('#panel-' + targetTab).offset().top - 100
                    }, 300);
                }
            });
        },

        // Initialize toggle switches
        initToggles: function () {
            $('.asl-switch input[type="checkbox"]').on('change', function () {
                // Auto-save on toggle change
                ASL_Admin.autoSave();
            });
        },

        // Initialize form submission
        initFormSubmission: function () {
            $('#asl-settings-form').on('submit', function (e) {
                e.preventDefault();
                ASL_Admin.saveSettings();
            });
        },

        // Initialize special actions
        initSpecialActions: function () {
            // Regenerate salts button
            $('#regenerate-salts-btn').on('click', function (e) {
                e.preventDefault();
                ASL_Admin.regenerateSalts();
            });

            // Emergency reset button
            $('#emergency-reset-btn').on('click', function (e) {
                e.preventDefault();
                ASL_Admin.emergencyReset();
            });

            // Clear logs button
            $('#asp-clear-logs').on('click', function (e) {
                e.preventDefault();
                ASL_Admin.clearLogs();
            });

            // Conditional field visibility
            this.initConditionalFields();
        },

        // Initialize conditional field visibility
        initConditionalFields: function () {
            // Show/hide reCAPTCHA keys based on reCAPTCHA enabled status
            function toggleRecaptchaFields() {
                var v2Enabled = $('#recaptcha_v2_enabled').is(':checked');
                var v3Enabled = $('#recaptcha_v3_enabled').is(':checked');
                var showKeys = v2Enabled || v3Enabled;

                var $keyFields = $('.asl-form-row').has('#recaptcha_site_key, #recaptcha_secret_key');

                if (showKeys) {
                    $keyFields.slideDown(200);
                } else {
                    $keyFields.slideUp(200);
                }
            }

            $('#recaptcha_v2_enabled, #recaptcha_v3_enabled').on('change', toggleRecaptchaFields);
            toggleRecaptchaFields(); // Initial state
        },

        // Auto-save functionality
        autoSave: function () {
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(function () {
                ASL_Admin.saveSettings(true);
            }, 1000);
        },

        // Save settings
        saveSettings: function (isAutoSave) {
            var $form = $('#asl-settings-form');
            var $saveBtn = $('.asl-save-bar .asl-btn-primary');
            var $status = $('.asl-save-status');

            if (!isAutoSave) {
                $saveBtn.prop('disabled', true).text('Saving...');
                $status.text('');
            }

            // Collect form data
            var settings = {};
            $form.find('input[type="checkbox"]').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    settings[name] = $(this).is(':checked') ? 1 : 0;
                }
            });

            $form.find('input[type="text"], input[type="password"], input[type="email"], input[type="number"], select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    settings[name] = $(this).val();
                }
            });

            // Send AJAX request
            $.ajax({
                url: asp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asp_save_settings',
                    nonce: asp_ajax.nonce,
                    settings: settings
                },
                success: function (response) {
                    if (response.success) {
                        if (!isAutoSave) {
                            $status.css('color', '#22c55e').text('✓ Saved successfully!');
                            ASL_Admin.showNotification('Settings saved successfully!', 'success');

                            // Update security badge immediately
                            ASL_Admin.updateSecurityBadge();
                        }
                    } else {
                        $status.css('color', '#ef4444').text('Error saving.');
                        ASL_Admin.showNotification('Error saving settings: ' + response.data, 'error');
                    }
                },
                error: function () {
                    $status.css('color', '#ef4444').text('Connection error.');
                    ASL_Admin.showNotification('Connection error occurred.', 'error');
                },
                complete: function () {
                    if (!isAutoSave) {
                        $saveBtn.prop('disabled', false).text('✓ Save All Settings');

                        // Clear status after 3 seconds
                        setTimeout(function () {
                            $status.text('');
                        }, 3000);
                    }
                }
            });
        },

        // Update security badge based on enabled features
        updateSecurityBadge: function () {
            var enabledCount = 0;
            var featuresToCheck = [
                'disable_wp_json',
                'disable_xmlrpc',
                'hide_wp_version',
                'disallow_file_edit',
                'protect_headers',
                'auto_regenerate_salts',
                'disallow_bad_requests',
                'prevent_user_enumeration',
                'hide_login_errors'
            ];

            // Count enabled features
            featuresToCheck.forEach(function (feature) {
                if ($('#' + feature).is(':checked')) {
                    enabledCount++;
                }
            });

            // Check reCAPTCHA (either v2 or v3)
            if ($('#recaptcha_v2_enabled').is(':checked') || $('#recaptcha_v3_enabled').is(':checked')) {
                enabledCount++;
            }

            // Update badge
            var $badge = $('.asl-status-badge');

            // Remove all status classes
            $badge.removeClass('asl-status-protected asl-status-partial asl-status-unprotected');

            var statusIcon = '';
            var statusText = '';
            var statusClass = '';

            if (enabledCount >= 5) {
                statusClass = 'asl-status-protected';
                statusIcon = '✓';
                statusText = 'Protected';
            } else if (enabledCount >= 2) {
                statusClass = 'asl-status-partial';
                statusIcon = '!';
                statusText = 'Partial';
            } else {
                statusClass = 'asl-status-unprotected';
                statusIcon = '✗';
                statusText = 'Unprotected';
            }

            // Update badge with new content
            $badge.addClass(statusClass);
            $badge.html('<span class="asl-status-dot">' + statusIcon + '</span>' + statusText);

            // Update dashboard stats if on dashboard tab
            var $activeCount = $('.asl-stat-value').first();
            if ($activeCount.length) {
                $activeCount.text(enabledCount + '/10');
            }
        },

        // Regenerate salt keys
        regenerateSalts: function () {
            var $btn = $('#regenerate-salts-btn');
            var originalHtml = $btn.html();

            if (!confirm('This will regenerate all WordPress salt keys and log you out immediately. Are you sure?')) {
                return;
            }

            $btn.prop('disabled', true).text('Regenerating...');

            $.ajax({
                url: asp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asp_regenerate_salts',
                    nonce: asp_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data && response.data.logout) {
                            ASL_Admin.showLogoutModal(response.data.message, response.data.redirect_url);
                        } else {
                            ASL_Admin.showNotification('Salt keys regenerated!', 'success');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    } else {
                        ASL_Admin.showNotification('Error: ' + response.data, 'error');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    ASL_Admin.showNotification('Connection error.', 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        // Emergency reset function
        emergencyReset: function () {
            var $btn = $('#emergency-reset-btn');
            var originalHtml = $btn.html();

            if (!confirm('⚠️ EMERGENCY RESET\n\nThis will reset all security settings to defaults.\n\nContinue?')) {
                return;
            }

            $btn.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: asp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asp_emergency_reset',
                    nonce: asp_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        ASL_Admin.showNotification('Reset complete! Reloading...', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        ASL_Admin.showNotification('Error: ' + response.data, 'error');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    ASL_Admin.showNotification('Connection error.', 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        // Clear all activity logs
        clearLogs: function () {
            var $btn = $('#asp-clear-logs');
            var originalHtml = $btn.html();

            if (!confirm('Are you sure you want to clear all activity logs? This action cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: asp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asp_clear_logs',
                    nonce: asp_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        ASL_Admin.showNotification('All logs cleared successfully! Reloading...', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        ASL_Admin.showNotification('Error: ' + response.data, 'error');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    ASL_Admin.showNotification('Connection error.', 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        // Show logout modal
        showLogoutModal: function (message, redirectUrl) {
            var modalHtml = '<div class="asl-modal-overlay">' +
                '<div class="asl-modal">' +
                '<div class="asl-modal-header"><h3>Security Update Complete</h3></div>' +
                '<div class="asl-modal-body">' +
                '<p>' + message + '</p>' +
                '<p><strong>Redirecting in <span class="asl-countdown">5</span> seconds...</strong></p>' +
                '</div>' +
                '<div class="asl-modal-footer">' +
                '<button class="asl-btn asl-btn-primary" onclick="window.location.href=\'' + redirectUrl + '\'">Login Now</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(modalHtml);

            var countdown = 5;
            var timer = setInterval(function () {
                countdown--;
                $('.asl-countdown').text(countdown);
                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.href = redirectUrl;
                }
            }, 1000);
        },

        // Initialize notifications
        initNotifications: function () {
            if (!$('.asl-notification-container').length) {
                $('body').append('<div class="asl-notification-container"></div>');
            }
        },

        // Show notification
        showNotification: function (message, type) {
            type = type || 'info';

            var icon = '💬';
            if (type === 'success') icon = '✅';
            if (type === 'error') icon = '❌';
            if (type === 'warning') icon = '⚠️';

            var $notification = $('<div class="asl-notification asl-notification-' + type + '">' +
                '<span class="asl-notification-icon">' + icon + '</span>' +
                '<span class="asl-notification-message">' + message + '</span>' +
                '<button class="asl-notification-close">&times;</button>' +
                '</div>');

            $('.asl-notification-container').append($notification);

            setTimeout(function () {
                $notification.addClass('show');
            }, 100);

            setTimeout(function () {
                ASL_Admin.hideNotification($notification);
            }, 5000);

            $notification.find('.asl-notification-close').on('click', function () {
                ASL_Admin.hideNotification($notification);
            });
        },

        // Hide notification
        hideNotification: function ($notification) {
            $notification.removeClass('show');
            setTimeout(function () {
                $notification.remove();
            }, 300);
        }
    };

    // Make ASL_Admin globally available
    window.ASL_Admin = ASL_Admin;

    // Handle browser back/forward buttons
    $(window).on('popstate', function () {
        var hash = window.location.hash.substring(1);
        if (hash && $('#panel-' + hash).length) {
            $('.asl-tab[data-tab="' + hash + '"]').click();
        }
    });

    // Keyboard shortcut: Ctrl/Cmd + S to save
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            ASL_Admin.saveSettings();
        }
    });

})(jQuery);

// Add notification styles dynamically
(function () {
    var styles = `
        .asl-notification-container {
            position: fixed;
            top: 50px;
            right: 20px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .asl-notification {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            font-size: 14px;
        }
        .asl-notification.show {
            transform: translateX(0);
        }
        .asl-notification-success { border-left: 4px solid #22c55e; }
        .asl-notification-error { border-left: 4px solid #ef4444; }
        .asl-notification-warning { border-left: 4px solid #f59e0b; }
        .asl-notification-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #9ca3af;
            margin-left: auto;
        }
        .asl-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
        }
        .asl-modal {
            background: #fff;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .asl-modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .asl-modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .asl-modal-body {
            padding: 20px;
        }
        .asl-modal-body p {
            margin: 0 0 10px;
        }
        .asl-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
            text-align: right;
        }
    `;

    var styleSheet = document.createElement('style');
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
})();