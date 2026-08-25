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
            this.initLoginDesign();
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

            // Note: the Clear All Logs button is handled by a dedicated
            // self-contained inline module (see enqueueAdminScripts) so it
            // cannot be affected by script-order or cache issues here.

            // Conditional field visibility
            this.initConditionalFields();

            // Status tab: copy system report
            $(document).on('click', '#asp-copy-report', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var $ta = $('#asl-report-text');
                if (!$ta.length) { return; }

                var done = function () {
                    var original = $btn.html();
                    $btn.html('<i class="ph ph-check"></i> Copied!');
                    setTimeout(function () { $btn.html(original); }, 2000);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText($ta.val()).then(done, function () {
                        $ta.prop('hidden', false).select();
                        document.execCommand('copy');
                        $ta.prop('hidden', true);
                        done();
                    });
                } else {
                    $ta.prop('hidden', false).select();
                    document.execCommand('copy');
                    $ta.prop('hidden', true);
                    done();
                }
            });
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

            // Show/hide the login design options with the toggle
            function toggleLoginDesignOptions() {
                var $options = $('#asl-login-design-options');
                if (!$options.length) { return; }
                if ($('#custom_login_design').is(':checked')) {
                    $options.slideDown(200);
                } else {
                    $options.slideUp(200);
                }
            }
            $('#custom_login_design').on('change', toggleLoginDesignOptions);
            toggleLoginDesignOptions(); // Initial state (syncs the server-side hide)
        },

        // Initialize custom login design controls (colour picker, panel/form
        // selects and the media-library logo picker). Only binds when the
        // options block exists on the page.
        initLoginDesign: function () {
            var $options = $('#asl-login-design-options');
            if (!$options.length) { return; }

            var $color = $('#login_accent_color');
            var $hex = $('#asl-login-accent-hex');
            var $logo = $('#login_logo');
            var $clearBtn = $('#asp-login-logo-clear');

            // Live hex preview next to the native colour picker.
            $color.on('input change', function () {
                if ($hex.length) {
                    $hex.text(this.value);
                }
            });

            // Media-library picker for the login logo.
            $('#asp-login-logo-upload').on('click', function (e) {
                e.preventDefault();

                if (typeof wp === 'undefined' || !wp.media) {
                    ASL_Admin.showNotification((asp_ajax.i18n.errorPrefix || 'Error: ') + 'Media library unavailable.', 'error');
                    return;
                }

                var frame = wp.media({
                    title: 'Select Login Logo',
                    library: { type: 'image' },
                    multiple: false,
                    button: { text: 'Use as Login Logo' }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var url = attachment.url || '';
                    if (url) {
                        $logo.val(url);
                        if ($clearBtn.length) { $clearBtn.prop('disabled', false); }
                        ASL_Admin.autoSave();
                    }
                });

                frame.open();
            });

            // Clear the logo field.
            $clearBtn.on('click', function () {
                $logo.val('');
                $(this).prop('disabled', true);
                ASL_Admin.autoSave();
            });

            // Auto-save when any design option changes.
            $options.on('change', 'select, input[type="color"], input[type="text"]', function () {
                ASL_Admin.autoSave();
            });
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
            var t = (asp_ajax.i18n || {});

            if (!isAutoSave) {
                $saveBtn.prop('disabled', true).text(t.saving || 'Saving...');
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

            $form.find('input[type="text"], input[type="color"], input[type="password"], input[type="email"], input[type="number"], select, textarea').each(function () {
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
                            $status.css('color', '#22c55e').text(t.saved || '✓ Saved successfully!');
                            ASL_Admin.showNotification(t.savedReloading || 'Settings saved! Reloading...', 'success');

                            // Reload so all PHP-rendered tabs reflect the newly saved state
                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        }
                    } else {
                        $status.css('color', '#ef4444').text((t.errorPrefix || 'Error: ') + response.data);
                        ASL_Admin.showNotification((t.saveError || 'Error saving settings: ') + response.data, 'error');
                    }
                },
                error: function () {
                    $status.css('color', '#ef4444').text(t.errorPrefix || 'Error:');
                    ASL_Admin.showNotification(t.connError || 'Connection error occurred.', 'error');
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

        // Regenerate salt keys
        regenerateSalts: function () {
            var t = (asp_ajax.i18n || {});
            var $btn = $('#regenerate-salts-btn');
            var originalHtml = $btn.html();

            if (!window.confirm(t.saltConfirm || 'This will regenerate all WordPress salt keys and log you out immediately. Are you sure?')) {
                return;
            }

            $btn.prop('disabled', true).text(t.regenerating || 'Regenerating...');

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
                            ASL_Admin.showNotification(t.saltSuccess || 'Salt keys regenerated!', 'success');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    } else {
                        ASL_Admin.showNotification((t.errorPrefix || 'Error: ') + response.data, 'error');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    ASL_Admin.showNotification(t.connectionError || 'Connection error.', 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        // Emergency reset function
        emergencyReset: function () {
            var t = (asp_ajax.i18n || {});
            var $btn = $('#emergency-reset-btn');
            var originalHtml = $btn.html();

            if (!window.confirm(t.resetConfirm || '⚠️ EMERGENCY RESET\n\nThis will reset all security settings to defaults.\n\nContinue?')) {
                return;
            }

            $btn.prop('disabled', true).text(t.resetting || 'Resetting...');

            $.ajax({
                url: asp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'asp_emergency_reset',
                    nonce: asp_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        ASL_Admin.showNotification(t.resetComplete || 'Reset complete! Reloading...', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        ASL_Admin.showNotification((t.errorPrefix || 'Error: ') + response.data, 'error');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    ASL_Admin.showNotification(t.connectionError || 'Connection error.', 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        // Show logout modal (built via DOM methods so server-provided text
        // and URLs can never inject markup)
        showLogoutModal: function (message, redirectUrl) {
            var t = (asp_ajax.i18n || {});

            var $overlay = $('<div class="asl-modal-overlay"></div>');
            var $modal = $('<div class="asl-modal"></div>');
            var $header = $('<div class="asl-modal-header"></div>').append($('<h3></h3>').text(t.modalTitle || 'Security Update Complete'));
            var $body = $('<div class="asl-modal-body"></div>');
            var $message = $('<p></p>').text(message);
            var countdownText = document.createTextNode((t.redirectingIn || 'Redirecting in %d seconds...').replace('%d', '5'));
            var $countdownP = $('<p></p>').append($('<strong></strong>').append(countdownText));
            var $footer = $('<div class="asl-modal-footer"></div>');
            var $loginBtn = $('<button class="asl-btn asl-btn-primary"></button>').text(t.loginNow || 'Login Now');

            $footer.append($loginBtn);
            $body.append($message, $countdownP);
            $modal.append($header, $body, $footer);
            $overlay.append($modal);
            $('body').append($overlay);

            $loginBtn.on('click', function () {
                window.location.href = redirectUrl;
            });

            var countdown = 5;
            var timer = setInterval(function () {
                countdown--;
                countdownText.textContent = (t.redirectingIn || 'Redirecting in %d seconds...').replace('%d', String(countdown));
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
                '<span class="asl-notification-icon"></span>' +
                '<span class="asl-notification-message"></span>' +
                '<button class="asl-notification-close">&times;</button>' +
                '</div>');

            $notification.find('.asl-notification-icon').text(icon);
            $notification.find('.asl-notification-message').text(message);

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