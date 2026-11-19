/**
 * SS Sentinel - Admin JavaScript
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
            this.initSearch();
            this.initToggles();
            this.initFormSubmission();
            this.initSpecialActions();
            this.initLoginDesign();
            this.initNotifications();
        },

        // Read-only tabs where the Save bar should stay hidden
        readOnlyTabs: ['dashboard', 'activity', 'status', 'about'],

        syncSaveBar: function (targetPanel) {
            if (this.readOnlyTabs.indexOf(targetPanel) !== -1) {
                $('.asl-save-bar').hide();
            } else {
                $('.asl-save-bar').show();
            }
        },

        // Initialize sidebar tab navigation
        initTabs: function () {
            $('.asl-tab').on('click', function (e) {
                e.preventDefault();

                var targetPanel = $(this).data('tab');

                // Clear active search when switching tabs
                if ($('#asl-settings-search').val()) {
                    $('#asl-settings-search').val('');
                    $('#asl-search-results').prop('hidden', true);
                    $('#asl-settings-form').show();
                }

                // Update active tab
                $('.asl-tab').removeClass('active');
                $(this).addClass('active');

                // Show target panel
                $('.asl-panel').removeClass('active');
                $('#panel-' + targetPanel).addClass('active');

                ASL_Admin.syncSaveBar(targetPanel);

                // Update URL hash without scrolling
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetPanel);
                }
            });

            // Handle initial panel from URL hash
            var hash = window.location.hash.substring(1);
            if (hash && $('#panel-' + hash).length) {
                $('.asl-tab[data-tab="' + hash + '"]').trigger('click');
            } else {
                this.syncSaveBar($('.asl-tab.active').data('tab') || 'dashboard');
            }

            // Handle recommendation "Fix" links
            $(document).on('click', '.asl-rec-link', function (e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                if (targetTab) {
                    $('.asl-tab[data-tab="' + targetTab + '"]').trigger('click');
                    $('html, body').animate({
                        scrollTop: 0
                    }, 200);
                }
            });
        },

        // Initialize sidebar settings search
        initSearch: function () {
            var $input = $('#asl-settings-search');
            var $results = $('#asl-search-results');
            var $list = $('#asl-search-list');
            var $summary = $('#asl-search-summary');
            var $form = $('#asl-settings-form');

            if (!$input.length) { return; }

            function clearSearch() {
                $input.val('');
                $results.prop('hidden', true);
                $form.show();
            }

            $('#asl-search-clear').on('click', clearSearch);

            $input.on('input', function () {
                var query = $.trim($(this).val()).toLowerCase();
                if (!query) {
                    clearSearch();
                    return;
                }

                $list.empty();
                var matches = 0;

                $('.asl-panel .asl-option').each(function () {
                    var $opt = $(this);
                    var title = $.trim($opt.find('.asl-option-info strong').first().text());
                    var desc = $.trim($opt.find('.asl-option-info span').first().text());
                    if (!title) { return; }

                    if (title.toLowerCase().indexOf(query) !== -1 || desc.toLowerCase().indexOf(query) !== -1) {
                        var $panel = $opt.closest('.asl-panel');
                        var tabId = ($panel.attr('id') || '').replace('panel-', '');
                        var tabLabel = $.trim($('.asl-tab[data-tab="' + tabId + '"]').text()) || tabId;

                        var $item = $('<div class="asl-search-item"></div>');
                        var $info = $('<div class="asl-search-item-info"></div>')
                            .append($('<strong></strong>').text(title))
                            .append($('<span></span>').text(desc));
                        var $btn = $('<button type="button" class="asl-btn asl-btn-secondary asl-btn-sm"></button>')
                            .text(tabLabel + ' →')
                            .on('click', function () {
                                clearSearch();
                                $('.asl-tab[data-tab="' + tabId + '"]').trigger('click');
                            });

                        $item.append($info, $btn);
                        $list.append($item);
                        matches++;
                    }
                });

                $summary.text(matches + ' matching setting' + (matches === 1 ? '' : 's') + ' for "' + $input.val() + '"');
                if (matches === 0) {
                    var noMatch = (asp_ajax.i18n && asp_ajax.i18n.noSearchMatches) || 'No matching settings found.';
                    $list.append($('<p class="asl-search-empty"></p>').text(noMatch));
                }

                $form.hide();
                $results.prop('hidden', false);
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
                    var copiedLabel = (asp_ajax.i18n && asp_ajax.i18n.copied) || 'Copied!';
                    $btn.empty().append('<i class="ph ph-check"></i> ').append(document.createTextNode(copiedLabel));
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

            $('#recaptcha_v2_enabled').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#recaptcha_v3_enabled, #image_captcha_enabled').prop('checked', false);
                }
                toggleRecaptchaFields();
            });
            $('#recaptcha_v3_enabled').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#recaptcha_v2_enabled, #image_captcha_enabled').prop('checked', false);
                }
                toggleRecaptchaFields();
            });
            $('#image_captcha_enabled').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#recaptcha_v2_enabled, #recaptcha_v3_enabled').prop('checked', false);
                }
                toggleRecaptchaFields();
            });
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

        // Track when salts have been regenerated in this session so subsequent
        // actions prompt for re-login instead of showing a connection error.
        saltsRegenerated: false,
        lastLoginUrl: '',

        // Save settings
        saveSettings: function (isAutoSave) {
            var $form = $('#asl-settings-form');
            var $saveBtn = $('.asl-save-bar .asl-btn-primary');
            var $status = $('.asl-save-status');
            var t = (asp_ajax.i18n || {});

            if (this.saltsRegenerated) {
                this.showLogoutModal(
                    t.saltReloginHint || 'All active sessions have been invalidated. Please re-login to continue making changes.',
                    this.lastLoginUrl || window.location.href
                );
                return;
            }

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
                        } else {
                            $status.css('color', '#22c55e').text(t.saved || '✓ Saved successfully!');
                            setTimeout(function () {
                                $status.text('');
                            }, 2500);
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
                        $saveBtn.prop('disabled', false)
                            .empty()
                            .append('<i class="ph ph-check"></i> ')
                            .append(document.createTextNode(t.saveAll || 'Save All Settings'));

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
                    $btn.prop('disabled', false).html(originalHtml);
                    if (response.success) {
                        if (response.data && response.data.logout) {
                            ASL_Admin.saltsRegenerated = true;
                            ASL_Admin.lastLoginUrl = response.data.redirect_url || '';
                            ASL_Admin.showLogoutModal(response.data.message, response.data.redirect_url);
                        } else {
                            ASL_Admin.showNotification(t.saltSuccess || 'Salt keys regenerated!', 'success');
                        }
                    } else {
                        ASL_Admin.showNotification((t.errorPrefix || 'Error: ') + response.data, 'error');
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

        // Show salt-updated modal with Re-login Now / Later options
        // (built via DOM methods so server-provided text/URLs can never inject markup)
        showLogoutModal: function (message, redirectUrl) {
            var t = (asp_ajax.i18n || {});

            var $overlay = $('<div class="asl-modal-overlay" role="dialog" aria-modal="true"></div>');
            var $modal = $('<div class="asl-modal"></div>');
            var $header = $('<div class="asl-modal-header"></div>').append(
                $('<h3 style="display:flex;align-items:center;gap:8px;"></h3>')
                    .append('<i class="ph ph-check-circle" style="color:#00a32a;font-size:20px;"></i>')
                    .append(document.createTextNode(t.modalTitle || 'Salt Keys Updated'))
            );
            var $body = $('<div class="asl-modal-body"></div>');
            var $message = $('<p style="font-weight:500;color:#1d2327;"></p>').text(message);
            var $hint = $('<p style="color:#646970;font-size:13px;margin-bottom:0;"></p>').text(
                t.saltReloginHint || 'All active sessions have been invalidated. You can re-login immediately or sign in later.'
            );
            var $footer = $('<div class="asl-modal-footer" style="display:flex;justify-content:flex-end;gap:10px;"></div>');
            var $laterBtn = $('<button type="button" class="asl-btn asl-btn-secondary"></button>').text(t.loginLater || 'Later');
            var $loginBtn = $('<button type="button" class="asl-btn asl-btn-primary"></button>').text(t.loginNow || 'Re-login Now');

            function closeModal() {
                $(document).off('keydown.aslSaltModal');
                $overlay.remove();
            }

            $laterBtn.on('click', closeModal);
            $loginBtn.on('click', function () {
                window.location.href = redirectUrl;
            });
            $overlay.on('click', function (e) {
                if (e.target === this) { closeModal(); }
            });
            $(document).on('keydown.aslSaltModal', function (e) {
                if (e.key === 'Escape') { closeModal(); }
            });

            $footer.append($laterBtn, $loginBtn);
            $body.append($message, $hint);
            $modal.append($header, $body, $footer);
            $overlay.append($modal);
            $('body').append($overlay);
        },

        // Initialize notifications
        initNotifications: function () {
            if (!$('.asl-notification-container').length) {
                $('body').append('<div class="asl-notification-container"></div>');
            }
        },

        // Show notification
        showNotification: function (message, type) {
            var allowedTypes = ['info', 'success', 'error', 'warning'];
            type = allowedTypes.indexOf(type) !== -1 ? type : 'info';

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