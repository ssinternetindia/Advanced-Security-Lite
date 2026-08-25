<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_Enhancements
{

    public function __construct()
    {
        $this->initEnhancements();
    }

    private function initEnhancements()
    {
        // Always log failed logins (feeds the Activity Log panel).
        add_action('wp_login_failed', array($this, 'logFailedLogin'));

        // A successful login wipes that IP's failure history and any
        // active lockout so legitimate users are never stuck locked out.
        add_action('wp_login', array($this, 'clearLoginAttemptsOnSuccess'), 10, 1);

        // Rate limiting only runs when explicitly enabled and honors the
        // configured attempt count and lockout window.
        if (get_option('asp_enable_login_limit', 0)) {
            add_filter('authenticate', array($this, 'limitLoginAttempts'), 30, 3);
        }

        // Enhanced file security
        add_action('init', array($this, 'enhancedFileSecurity'));

        // Database security
        add_action('init', array($this, 'enhancedDatabaseSecurity'));

        // Admin security enhancements
        add_action('admin_init', array($this, 'enhancedAdminSecurity'));

        // Content security enhancements
        add_action('init', array($this, 'enhancedContentSecurity'));

        // Performance optimizations
        add_action('init', array($this, 'performanceOptimizations'));

        // Disable Password Recovery
        if (get_option('asp_disable_password_recovery', 0)) {
            add_filter('allow_password_reset', '__return_false');
            add_action('login_head', array($this, 'hidePasswordResetLink'));
        }

        // Hide admin notices + admin bar bell
        if (get_option('asp_hide_admin_notices', 0)) {
            $this->initHideAdminNotices();
        }

        // Error log viewer (admin bar entry, available on every admin page)
        if (get_option('asp_error_log_viewer', 0)) {
            $this->initErrorLogViewer();
        }
    }

    /**
     * Error Log Viewer: adds an "Error Logs" entry to the WP admin bar and
     * a modal (rendered in admin_footer on every admin page) that lists all
     * detected log files with view / download / clear actions.
     */
    private function initErrorLogViewer()
    {
        add_action('admin_bar_menu', array($this, 'addErrorLogNode'), 998);
        add_action('admin_enqueue_scripts', array($this, 'enqueueErrorLogViewer'));
        add_action('admin_footer', array($this, 'renderErrorLogModal'));
    }

    public function addErrorLogNode($wp_admin_bar)
    {
        $icon = '<span class="asp-log-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m10 13-2 2 2 2"/><path d="m14 13 2 2-2 2"/></svg></span>';

        $wp_admin_bar->add_node(array(
            'id' => 'asp-errorlog-top',
            'parent' => 'top-secondary',
            'title' => $icon . '<span class="asp-log-text">' . esc_html__('Error Logs', 'advanced-security-lite') . '</span>',
            'href' => '#',
            'meta' => array(
                'title' => __('View all detected log files', 'advanced-security-lite'),
            ),
        ));
    }

    public function enqueueErrorLogViewer()
    {
        wp_register_script('asp-errorlog-viewer', false, array('jquery'), ASP_VERSION, true);
        wp_enqueue_script('asp-errorlog-viewer');

        wp_register_style('asp-errorlog-viewer', false, array(), ASP_VERSION);
        wp_enqueue_style('asp-errorlog-viewer');

        wp_localize_script('asp-errorlog-viewer', 'aspLogConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asp_nonce'),
            'downloadUrl' => wp_nonce_url(
                admin_url('admin-post.php?action=asp_download_error_log'),
                'asp_errorlog_download'
            ),
            'i18n' => array(
                'view' => __('View', 'advanced-security-lite'),
                'download' => __('Download', 'advanced-security-lite'),
                'clear' => __('Clear', 'advanced-security-lite'),
                'empty' => __('The log file is currently empty.', 'advanced-security-lite'),
                'truncated' => __('Showing the most recent entries — the full log is available via Download.', 'advanced-security-lite'),
                'none' => __('No log files were found on this site.', 'advanced-security-lite'),
                'clearConfirm' => __('Are you sure you want to permanently clear this log file?', 'advanced-security-lite'),
                'cleared' => __('Log cleared.', 'advanced-security-lite'),
                'error' => __('Error: ', 'advanced-security-lite'),
                'connError' => __('Connection error.', 'advanced-security-lite'),
            ),
        ));

        wp_add_inline_style('asp-errorlog-viewer', '
#wp-admin-bar-asp-errorlog-top > .ab-item { display: flex !important; align-items: center; gap: 5px; }
.asp-log-icon { display: inline-flex; line-height: 1; color: #c3c4c7; transition: color .15s ease; }
#wp-admin-bar-asp-errorlog-top:hover .asp-log-icon { color: #fff; }
.asp-log-icon svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
@media (max-width: 782px) { #wp-admin-bar-asp-errorlog-top .asp-log-text { display: none; } }
.asl-errorlog-overlay { position: fixed; inset: 0; z-index: 100000; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
.asl-errorlog-overlay[hidden] { display: none; }
.asl-errorlog-modal { width: 880px; max-width: 100%; max-height: 86vh; overflow-y: auto; background: #fff; border-radius: 8px; box-shadow: 0 5px 16px rgba(0,0,0,.16); padding: 24px; }
.asl-errorlog-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.asl-errorlog-head h3 { display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
.asl-errorlog-close { background: #f1f5f9; border: none; width: 34px; height: 34px; border-radius: 50%; font-size: 18px; line-height: 1; color: #64748b; cursor: pointer; }
.asl-errorlog-close:hover { background: #fef2f2; color: #dc2626; }
.asl-errorlog-sub { margin: 4px 0 20px; font-size: 13px; color: #64748b; }
.asl-errorlog-list { display: flex; flex-direction: column; gap: 8px; }
.asl-errorlog-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 16px; background: #f8fafc; border-radius: 14px; }
.asl-errorlog-row:hover { background: #fff; box-shadow: 0 0 0 1.5px #dbeafe, 0 6px 16px -8px rgba(15,23,42,.1); }
.asl-errorlog-row-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.asl-errorlog-row-name { font-size: 14px; font-weight: 750; color: #0f172a; }
.asl-errorlog-row-path { font-family: "SF Mono", ui-monospace, Monaco, Consolas, monospace; font-size: 11.5px; color: #94a3b8; word-break: break-all; }
.asl-errorlog-row-meta { font-size: 12px; color: #64748b; }
.asl-errorlog-actions { display: flex; flex-wrap: wrap; gap: 8px; flex-shrink: 0; }
.asl-errorlog-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; }
.asl-errorlog-btn-secondary { background: #f1f5f9; color: #334155; }
.asl-errorlog-btn-secondary:hover { background: #e2e8f0; }
.asl-errorlog-btn-danger { background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; }
.asl-errorlog-btn-danger:hover { filter: brightness(1.07); }
.asl-errorlog-btn[disabled] { opacity: .5; cursor: not-allowed; }
.asl-errorlog-viewer { margin-top: 20px; }
.asl-errorlog-viewer-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.asl-errorlog-meta-info { font-size: 12px; color: #64748b; }
.asl-errorlog-pre { margin: 0; max-height: 380px; overflow: auto; background: #f8fafc; border-radius: 14px; padding: 16px 20px; font-family: "SF Mono", ui-monospace, Monaco, Consolas, monospace; font-size: 12px; line-height: 1.7; color: #1e293b; white-space: pre-wrap; word-break: break-word; box-shadow: inset 0 0 0 1px #e2e8f0; }
.asl-errorlog-empty { text-align: center; padding: 48px 24px; }
.asl-errorlog-empty-icon { width: 76px; height: 76px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 30px; color: #94a3b8; background: #f1f5f9; border-radius: 50%; }
.asl-errorlog-empty h4 { margin: 0 0 8px; font-size: 16px; font-weight: 800; color: #1e293b; }
.asl-errorlog-empty p { margin: 0 auto; font-size: 13.5px; color: #64748b; max-width: 440px; }
@media (max-width: 782px) { .asl-errorlog-overlay { padding: 12px; align-items: flex-end; } .asl-errorlog-modal { max-height: 92vh; padding: 16px; } .asl-errorlog-row { flex-direction: column; align-items: flex-start; } .asl-errorlog-actions { width: 100%; } }
');

        // Nowdoc (<<<'JS') on purpose: the script contains $ identifiers
        // that PHP must NOT interpolate.
        $js = <<<'JS'
(function ($) {
    'use strict';

    var t = aspLogConfig.i18n;

    function openModal() {
        var modal = document.getElementById('asp-errorlog-modal');
        if (!modal) { return; }
        modal.hidden = false;
        $('#asp-errorlog-viewer').prop('hidden', true);
        loadList();
    }

    function closeModal() {
        var modal = document.getElementById('asp-errorlog-modal');
        if (modal) { modal.hidden = true; }
    }

    function loadList() {
        var $list = $('#asp-errorlog-list');
        $list.empty();
        $('#asp-errorlog-empty').prop('hidden', true);

        $.post(aspLogConfig.ajaxUrl, {
            action: 'asp_get_error_logs',
            nonce: aspLogConfig.nonce
        }, function (response) {
            if (!response || !response.success) { return; }

            var logs = response.data || [];
            if (!logs.length) {
                $('#asp-errorlog-empty').prop('hidden', false);
                return;
            }

            logs.forEach(function (log) {
                var $row = $(
                    '<div class="asl-errorlog-row">' +
                        '<div class="asl-errorlog-row-info">' +
                            '<span class="asl-errorlog-row-name"></span>' +
                            '<span class="asl-errorlog-row-path"></span>' +
                            '<span class="asl-errorlog-row-meta"></span>' +
                        '</div>' +
                        '<div class="asl-errorlog-actions">' +
                            '<button type="button" class="asl-errorlog-btn asl-errorlog-btn-secondary asl-log-view"></button>' +
                            '<a class="asl-errorlog-btn asl-errorlog-btn-secondary asl-log-dl"></a>' +
                            '<button type="button" class="asl-errorlog-btn asl-errorlog-btn-danger asl-log-clr"></button>' +
                        '</div>' +
                    '</div>'
                );

                $row.find('.asl-errorlog-row-name').text(log.name);
                $row.find('.asl-errorlog-row-path').text(log.path);
                $row.find('.asl-errorlog-row-meta').text(
                    log.size_human + (log.modified_human ? ' · ' + log.modified_human : '') + (log.writable ? '' : ' · 🔒')
                );
                $row.find('.asl-log-view').text(t.view);
                $row.find('.asl-log-dl').text(t.download);
                $row.find('.asl-log-clr').text(t.clear);

                $row.find('.asl-log-dl').attr(
                    'href',
                    aspLogConfig.downloadUrl + '&key=' + encodeURIComponent(log.key)
                );

                if (!log.writable) {
                    $row.find('.asl-log-clr').prop('disabled', true);
                }

                $row.find('.asl-log-view').on('click', function () { viewLog(log.key); });
                $row.find('.asl-log-clr').on('click', function () { clearLog(log.key); });

                $list.append($row);
            });
        }).fail(function () {
            window.alert(t.connError);
        });
    }

    function viewLog(key) {
        $.post(aspLogConfig.ajaxUrl, {
            action: 'asp_read_error_log',
            nonce: aspLogConfig.nonce,
            key: key
        }, function (response) {
            if (!response || !response.success) {
                window.alert(t.error + (response && response.data ? response.data : ''));
                return;
            }

            var data = response.data;
            $('#asp-errorlog-viewer-meta').text(
                data.name + ' · ' + (data.truncated ? (t.truncated + ' · ') : '') + (data.meta || '')
            );
            $('#asp-errorlog-pre').text(data.content || t.empty);
            $('#asp-errorlog-viewer').prop('hidden', false);
        }).fail(function () {
            window.alert(t.connError);
        });
    }

    function clearLog(key) {
        if (!window.confirm(t.clearConfirm)) { return; }

        $.post(aspLogConfig.ajaxUrl, {
            action: 'asp_clear_error_log',
            nonce: aspLogConfig.nonce,
            key: key
        }, function (response) {
            if (!response || !response.success) {
                window.alert(t.error + (response && response.data ? response.data : ''));
            }
            $('#asp-errorlog-viewer').prop('hidden', true);
            loadList();
        }).fail(function () {
            window.alert(t.connError);
        });
    }

    $(function () {
        $(document).on('click', '#wp-admin-bar-asp-errorlog-top', function (e) {
            e.preventDefault();
            openModal();
        });
        $(document).on('click', '#asp-errorlog-close', closeModal);
        $(document).on('click', '#asp-errorlog-viewer-close', function () {
            $('#asp-errorlog-viewer').prop('hidden', true);
        });
        $(document).on('click', '#asp-errorlog-modal', function (e) {
            if (e.target === this) { closeModal(); }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') { closeModal(); }
        });
    });
})(jQuery);
JS;

        wp_add_inline_script('asp-errorlog-viewer', $js);
    }

    /**
     * Render the log viewer modal once per admin page (site-wide).
     */
    public function renderErrorLogModal()
    {
        ?>
        <div id="asp-errorlog-modal" class="asl-errorlog-overlay" hidden>
            <div class="asl-errorlog-modal" role="dialog" aria-modal="true" aria-labelledby="asp-errorlog-title">
                <div class="asl-errorlog-head">
                    <h3 id="asp-errorlog-title">
                        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:none;stroke:#2563eb;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m10 13-2 2 2 2"/><path d="m14 13 2 2-2 2"/></svg>
                        <?php esc_html_e('Error Logs', 'advanced-security-lite'); ?>
                    </h3>
                    <button type="button" id="asp-errorlog-close" class="asl-errorlog-close"
                        aria-label="<?php esc_attr_e('Close', 'advanced-security-lite'); ?>">&times;</button>
                </div>
                <p class="asl-errorlog-sub"><?php esc_html_e('All log files currently detected on this site. Latest first.', 'advanced-security-lite'); ?></p>
                <div id="asp-errorlog-list" class="asl-errorlog-list"></div>
                <div id="asp-errorlog-empty" class="asl-errorlog-empty" hidden>
                    <div class="asl-errorlog-empty-icon">🔍</div>
                    <h4><?php esc_html_e('No Log Files Found', 'advanced-security-lite'); ?></h4>
                    <p><?php esc_html_e('No debug.log, PHP error log, or .log files were detected in wp-content. Set WP_DEBUG_LOG to true in wp-config.php to start recording errors.', 'advanced-security-lite'); ?></p>
                </div>
                <div id="asp-errorlog-viewer" class="asl-errorlog-viewer" hidden>
                    <div class="asl-errorlog-viewer-head">
                        <span id="asp-errorlog-viewer-meta" class="asl-errorlog-meta-info"></span>
                        <button type="button" id="asp-errorlog-viewer-close" class="asl-errorlog-btn asl-errorlog-btn-secondary">
                            <?php esc_html_e('Close Viewer', 'advanced-security-lite'); ?>
                        </button>
                    </div>
                    <pre id="asp-errorlog-pre" class="asl-errorlog-pre"></pre>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Hide Admin Notices: third-party notices across wp-admin are collected
     * client-side and hidden; a bell in the WP admin bar opens a popover
     * listing everything that was hidden on the current page.
     */
    private function initHideAdminNotices()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueueNoticesBell'));
        add_action('admin_bar_menu', array($this, 'addNoticesBellNode'), 999);
    }

    public function enqueueNoticesBell()
    {
        wp_register_script('asp-notices-bell', false, array(), ASP_VERSION, true);
        wp_enqueue_script('asp-notices-bell');

        wp_register_style('asp-notices-bell', false, array(), ASP_VERSION);
        wp_enqueue_style('asp-notices-bell');

        wp_localize_script('asp-notices-bell', 'aspBellConfig', array(
            'title' => __('Hidden Notices', 'advanced-security-lite'),
            'empty' => __('No notices were hidden on this page.', 'advanced-security-lite'),
            'restore' => __('Show all', 'advanced-security-lite'),
            'hint' => __('Notices are hidden on this page only and remain available here.', 'advanced-security-lite'),
        ));

        wp_add_inline_style('asp-notices-bell', '
#wp-admin-bar-asp-admin-notices > .ab-item { display: flex !important; align-items: center; gap: 5px; }
.asp-bell-icon { display: inline-flex; line-height: 1; color: #c3c4c7; transition: color .15s ease; }
#wp-admin-bar-asp-admin-notices:hover .asp-bell-icon { color: #fff; }
.asp-bell-icon svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
#wp-admin-bar-asp-admin-notices.has-notices .asp-bell-icon { color: #f0b849; }
.asp-bell-count { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; margin-left: 3px; border-radius: 999px; background: #8c8f94; color: #fff; font-size: 10.5px; font-weight: 700; line-height: 1; }
#wp-admin-bar-asp-admin-notices.has-notices .asp-bell-count { background: #d63638; animation: asp-bell-pulse 2.2s ease-in-out infinite; }
@keyframes asp-bell-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(214,54,56,.45);} 50% { box-shadow: 0 0 0 5px rgba(214,54,56,0);} }
.asp-notice-hidden { display: none !important; }
.asp-notices-popover { position: fixed; z-index: 99999; width: 480px; max-width: calc(100vw - 24px); max-height: 64vh; overflow: auto; background: #fff; border: 1px solid #dcdcde; border-radius: 6px; box-shadow: 0 5px 16px rgba(0,0,0,.14); padding: 0; }
.asp-notices-popover-head { position: sticky; top: 0; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 14px; background: #fff; border-bottom: 1px solid #f0f0f1; font-size: 13px; font-weight: 600; color: #1d2327; }
.asp-notices-popover-actions { display: flex; align-items: center; gap: 6px; }
.asp-notices-restore { background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 6px; padding: 3px 10px; font-size: 12px; cursor: pointer; color: #2271b1; }
.asp-notices-restore:hover { background: #e8eaeb; }
.asp-notices-popover-close { background: none; border: none; cursor: pointer; font-size: 17px; line-height: 1; color: #787c82; padding: 2px 4px; }
.asp-notices-popover-close:hover { color: #1d2327; }
.asp-notices-popover-body { padding: 12px 14px; }
.asp-notices-empty { margin: 0; padding: 22px 6px; text-align: center; color: #787c82; font-size: 13px; }
.asp-notices-popover-foot { padding: 9px 14px; border-top: 1px solid #f0f0f1; font-size: 11.5px; color: #8c8f94; }
.asp-notices-popover .notice, .asp-notices-popover .update-nag, .asp-notices-popover #message { margin: 0 0 10px !important; }
@media (max-width: 782px) { #wp-admin-bar-asp-admin-notices .asp-bell-text { display: none; } }
');

        // Nowdoc (<<<'JS') on purpose: the script contains $ identifiers
        // that PHP must NOT interpolate.
        $js = <<<'JS'
(function () {
    'use strict';

    var NODE_ID = 'wp-admin-bar-asp-admin-notices';
    var POPOVER_ID = 'asp-notices-popover';

    /* Top-level notices plus legacy .updated/.error classes; scoped to the
     * content body so admin-bar/screen-meta areas are never touched. */
    var SELECTOR = [
        '#wpbody-content > .notice',
        '#wpbody-content > .update-nag',
        '#wpbody-content > #message',
        '#wpbody-content > .updated',
        '#wpbody-content > .error',
        '#wpbody-content > .wrap > .notice',
        '#wpbody-content > .wrap > .update-nag',
        '#wpbody-content > .wrap > .updated',
        '#wpbody-content > .wrap > .error'
    ].join(', ');

    function node() { return document.getElementById(NODE_ID); }

    function collect() {
        if (!node()) { return 0; }
        document.querySelectorAll(SELECTOR).forEach(function (el) {
            if (el.closest('.asl-wrap') || el.closest('.asp-notice-kept') || el.closest('#' + POPOVER_ID)) { return; }
            if (el.classList.contains('asp-notice-hidden')) { return; }
            if (!el.textContent || !el.textContent.trim()) { return; }
            if (window.getComputedStyle(el).display === 'none') { return; }
            el.classList.add('asp-notice-hidden');
            el.setAttribute('data-asp-hidden', '1');
        });
        /* The badge always reports the TOTAL hidden count — re-scans must
         * never reset it just because there is nothing new to hide. */
        var count = hiddenCount();
        updateCount(count);
        return count;
    }

    function hiddenCount() {
        return document.querySelectorAll('[data-asp-hidden="1"]').length;
    }

    function updateCount(count) {
        var n = node();
        if (n) { n.classList.toggle('has-notices', count > 0); }
        document.querySelectorAll('.asp-bell-count').forEach(function (badge) {
            var value = String(count);
            if (badge.textContent !== value) { badge.textContent = value; }
        });
        var popover = document.getElementById(POPOVER_ID);
        if (popover && !popover.hidden && popover.getAttribute('data-count') !== String(count)) {
            popover.setAttribute('data-count', String(count));
            renderPopover(popover);
        }
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = String(str == null ? '' : str);
        return d.innerHTML;
    }

    function buildPopover() {
        var pop = document.createElement('div');
        pop.id = POPOVER_ID;
        pop.className = 'asp-notices-popover';
        pop.hidden = true;
        document.body.appendChild(pop);
        return pop;
    }

    function renderPopover(pop) {
        var count = hiddenCount();
        pop.setAttribute('data-count', String(count));
        var items = '';
        document.querySelectorAll('[data-asp-hidden="1"]').forEach(function (el) {
            var clone = el.cloneNode(true);
            clone.classList.remove('asp-notice-hidden');
            clone.removeAttribute('data-asp-hidden');
            clone.removeAttribute('id');
            clone.style.margin = '0 0 10px';
            items += clone.outerHTML;
        });

        pop.innerHTML =
            '<div class="asp-notices-popover-head">' +
                '<span>' + escapeHtml(aspBellConfig.title) + ' (' + count + ')</span>' +
                '<span class="asp-notices-popover-actions">' +
                    (count > 0 ? '<button type="button" class="asp-notices-restore">' + escapeHtml(aspBellConfig.restore) + '</button>' : '') +
                    '<button type="button" class="asp-notices-popover-close" aria-label="Close">&times;</button>' +
                '</span>' +
            '</div>' +
            '<div class="asp-notices-popover-body">' +
                (items || '<p class="asp-notices-empty">' + escapeHtml(aspBellConfig.empty) + '</p>') +
            '</div>' +
            '<div class="asp-notices-popover-foot">' + escapeHtml(aspBellConfig.hint) + '</div>';

        var close = pop.querySelector('.asp-notices-popover-close');
        if (close) { close.addEventListener('click', function () { pop.hidden = true; }); }

        var restore = pop.querySelector('.asp-notices-restore');
        if (restore) {
            restore.addEventListener('click', function () {
                document.querySelectorAll('[data-asp-hidden="1"]').forEach(function (el) {
                    el.classList.remove('asp-notice-hidden');
                    el.removeAttribute('data-asp-hidden');
                });
                updateCount(0);
            });
        }
    }

    function togglePopover() {
        var n = node();
        var pop = document.getElementById(POPOVER_ID) || buildPopover();
        if (!pop.hidden) { pop.hidden = true; return; }

        collect(); /* self-heal: rescan in case the initial pass missed late-rendered notices */
        renderPopover(pop);
        pop.hidden = false;

        var rect = n.getBoundingClientRect();
        var width = pop.offsetWidth || 480;
        var left = Math.min(Math.max(8, rect.right - width), window.innerWidth - width - 8);
        pop.style.top = (rect.bottom + 6) + 'px';
        pop.style.left = left + 'px';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', collect);
    } else {
        collect();
    }

    document.addEventListener('click', function (e) {
        var n = e.target.closest ? e.target.closest('#' + NODE_ID) : null;
        if (n) {
            e.preventDefault();
            e.stopPropagation();
            togglePopover();
            return;
        }
        var pop = document.getElementById(POPOVER_ID);
        if (pop && !pop.hidden && !e.target.closest('#' + POPOVER_ID)) { pop.hidden = true; }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var pop = document.getElementById(POPOVER_ID);
            if (pop) { pop.hidden = true; }
        }
    });

    /* Re-scan when notices are injected or removed after load */
    var scanTimer = null;
    new MutationObserver(function () {
        clearTimeout(scanTimer);
        scanTimer = setTimeout(collect, 250);
    }).observe(document.body, { childList: true, subtree: true });
})();
JS;

        wp_add_inline_script('asp-notices-bell', $js);
    }

    /**
     * Add the bell node to the WP admin bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
     */
    public function addNoticesBellNode($wp_admin_bar)
    {
        $bell_svg = '<span class="asp-bell-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span>';

        $wp_admin_bar->add_node(array(
            'id' => 'asp-admin-notices',
            'parent' => 'top-secondary',
            'title' => $bell_svg . '<span class="asp-bell-text">' . esc_html__('Notices', 'advanced-security-lite') . '</span><span class="asp-bell-count">0</span>',
            'href' => '#',
            'meta' => array(
                'title' => __('Hidden admin notices', 'advanced-security-lite'),
            ),
        ));
    }

    public function logFailedLogin($username)
    {
        // While the lockout is active, do not record further attempts —
        // otherwise every retry re-arms the lock transient and the lockout
        // extends itself indefinitely.
        $ip = asp_get_client_ip();
        if ('' === $ip) {
            return;
        }

        if (get_transient('asp_lock_' . md5($ip))) {
            return;
        }

        // Cap stored usernames to prevent autoloaded-option bloat.
        $username = is_string($username) ? mb_substr($username, 0, 60) : '';

        $attempts = get_option('asp_failed_logins', array());

        if (!isset($attempts[$ip])) {
            $attempts[$ip] = array();
        }

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        $attempts[$ip][] = array(
            'username' => $username,
            'time' => current_time('timestamp'),
            'user_agent' => $user_agent
        );

        // Keep a bounded history per IP (enough for the configured threshold)
        $max_attempts = (int) get_option('asp_max_login_attempts', 5);
        $keep = max(25, $max_attempts * 2);
        if (count($attempts[$ip]) > $keep) {
            $attempts[$ip] = array_slice($attempts[$ip], -$keep);
        }

        update_option('asp_failed_logins', $attempts);

        // Set a hard lockout transient when the threshold is crossed.
        // This eliminates the TOCTOU race between log and check.
        $window = (int) get_option('asp_lockout_duration', 30) * MINUTE_IN_SECONDS;
        if ($window < MINUTE_IN_SECONDS) {
            $window = 30 * MINUTE_IN_SECONDS;
        }
        $now = time();
        $recent = array_filter($attempts[$ip], function ($a) use ($now, $window) {
            return isset($a['time']) && ($now - (int) $a['time']) < $window;
        });
        if (count($recent) >= $max_attempts) {
            set_transient('asp_lock_' . md5($ip), 1, $window);
        }
    }

    public function clearLoginAttemptsOnSuccess($user_login)
    {
        $ip = asp_get_client_ip();
        if ('' === $ip) {
            return;
        }

        $attempts = get_option('asp_failed_logins', array());
        if (isset($attempts[$ip])) {
            unset($attempts[$ip]);
            update_option('asp_failed_logins', $attempts);
        }

        delete_transient('asp_lock_' . md5($ip));
    }

    public function limitLoginAttempts($user, $username, $password)
    {
        if (empty($username) || empty($password)) {
            return $user;
        }

        $ip = asp_get_client_ip();
        if ('' === $ip) {
            return $user;
        }

        // Check the hard lockout transient first — authoritative
        // enforcement that is immune to the option read/write race.
        $lock = get_transient('asp_lock_' . md5($ip));
        if ($lock) {
            $window = (int) get_option('asp_lockout_duration', 30) * MINUTE_IN_SECONDS;
            if ($window < MINUTE_IN_SECONDS) {
                $window = 30 * MINUTE_IN_SECONDS;
            }
            return new WP_Error(
                'too_many_attempts',
                __('Too many failed login attempts. Please try again later.', 'advanced-security-lite')
            );
        }

        $max_attempts = (int) get_option('asp_max_login_attempts', 5);
        if ($max_attempts < 1) {
            $max_attempts = 5;
        }

        // Lockout duration (minutes) doubles as the sliding attempt window.
        $window = (int) get_option('asp_lockout_duration', 30) * MINUTE_IN_SECONDS;
        if ($window < MINUTE_IN_SECONDS) {
            $window = 30 * MINUTE_IN_SECONDS;
        }

        $now = time();
        $attempts = get_option('asp_failed_logins', array());

        if (isset($attempts[$ip])) {
            $recent_attempts = array_filter($attempts[$ip], function ($attempt) use ($now, $window) {
                return isset($attempt['time']) && ($now - (int) $attempt['time']) < $window;
            });

            if (count($recent_attempts) >= $max_attempts) {
                // Belt-and-suspenders: also set the transient here for
                // the edge case where logFailedLogin didn't fire first.
                set_transient('asp_lock_' . md5($ip), 1, $window);
                return new WP_Error(
                    'too_many_attempts',
                    __('Too many failed login attempts. Please try again later.', 'advanced-security-lite')
                );
            }
        }

        return $user;
    }

    public function hidePasswordResetLink()
    {
        echo '<style>#nav > a[href*="lostpassword"] { display: none !important; }</style>';
    }

    public function enhancedFileSecurity()
    {
        // Enhanced upload security (malicious-code scan for image uploads).
        add_filter('wp_handle_upload_prefilter', array($this, 'enhancedUploadSecurity'));

        // Note: sensitive-file .htaccess protection and the uploads PHP
        // execution block are handled by ASP_SecurityFeatures to keep a
        // single writer for each .htaccess section.
    }

    public function enhancedUploadSecurity($file)
    {
        // Only scan image files for malicious code (not PHP, JS, or HTML files)
        if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $scannable_types = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg');

            // Only scan image files that shouldn't contain code
            if (in_array($file_extension, $scannable_types)) {
                $content = @file_get_contents($file['tmp_name']);

                if (false !== $content) {
                    // Look for PHP code in image files (common malware technique)
                    $malicious_patterns = array(
                        '/<\?php/i',
                        '/eval\s*\(/i',
                        '/base64_decode\s*\(/i',
                        '/shell_exec\s*\(/i',
                        '/system\s*\(/i',
                        '/exec\s*\(/i',
                        '/passthru\s*\(/i'
                    );

                    foreach ($malicious_patterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $file['error'] = __('Image file contains potentially malicious code.', 'advanced-security-lite');
                            break;
                        }
                    }
                }
            }
        }

        return $file;
    }

    public function enhancedAdminSecurity()
    {
        // Enhanced admin area protection (access logging only).
        $this->protectAdminArea();

        // Note: DISALLOW_FILE_EDIT / DISALLOW_FILE_MODS are no longer forced
        // here. The plugin respects its own toggles instead:
        // - "Disable File Editor" is handled by ASP_SecurityFeatures.
        // - Plugin/theme upload restrictions use the precise map_meta_cap
        //   filters in ASP_SecurityFeatures, which do not block updates.
    }

    public function enhancedDatabaseSecurity()
    {
        // Remove WordPress version from database
        remove_action('wp_head', 'wp_generator');

        // Disable database error reporting in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            global $wpdb;
            $wpdb->hide_errors();
        }

        // Note: WordPress $wpdb->prepare() already provides SQL injection protection
        // Removed overly aggressive query filtering that was breaking legitimate queries
    }

    public function protectAdminArea()
    {
        // Log admin access attempts
        if (is_admin() && !wp_doing_ajax()) {
            $this->logAdminAccess();
        }

        // Note: WordPress does not use PHP sessions, so cookie flag ini_set
        // calls were removed as ineffective. WP auth cookies are already
        // HttpOnly/Secure per WordPress core behavior.
    }

    private function logAdminAccess()
    {
        // Skip logging right after "Clear All Logs": the automatic reload
        // would otherwise immediately re-log the admin's own page view and
        // make it look like clearing did nothing.
        if (get_transient('asp_suppress_access_log')) {
            delete_transient('asp_suppress_access_log');
            return;
        }

        $user = wp_get_current_user();
        if ($user->ID > 0) {
            $access_log = get_option('asp_admin_access_log', array());
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

            $access_log[] = array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'ip' => asp_get_client_ip(),
                'time' => current_time('timestamp'),
                'page' => $request_uri
            );

            // Keep only last 100 entries
            if (count($access_log) > 100) {
                $access_log = array_slice($access_log, -100);
            }

            update_option('asp_admin_access_log', $access_log);
        }
    }

    public function enhancedContentSecurity()
    {
        // Enhanced XSS protection
        add_filter('the_content', array($this, 'enhancedXssProtection'));
        add_filter('comment_text', array($this, 'enhancedXssProtection'));

        // Note: the CSP meta tag was removed. Meta-tag CSP is largely
        // ineffective (ignored for frame-ancestors, cannot sandbox properly);
        // header-based security headers are sent by ASP_SecurityFeatures.

        // Disable dangerous HTML tags
        add_filter('wp_kses_allowed_html', array($this, 'restrictAllowedHtml'), 10, 2);
    }

    public function enhancedXssProtection($content)
    {
        // Remove potentially dangerous attributes
        $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/javascript\s*:/i', '', $content);
        $content = preg_replace('/vbscript\s*:/i', '', $content);

        return $content;
    }

    public function restrictAllowedHtml($allowed, $context)
    {
        if ($context === 'post') {
            // Remove potentially dangerous tags
            unset($allowed['script']);
            unset($allowed['object']);
            unset($allowed['embed']);
            unset($allowed['form']);
            unset($allowed['input']);
            unset($allowed['iframe']);
        }

        return $allowed;
    }

    public function performanceOptimizations()
    {
        // DB optimization cron is now scheduled at activation (or toggled
        // via the settings save handler) — never unconditionally on init.
        add_action('asp_optimize_database', array($this, 'optimizeDatabase'));

        // Optimize autoloaded options (only in admin, once per day)
        if (is_admin() && !get_transient('asp_autoload_optimized')) {
            add_action('admin_init', array($this, 'optimizeAutoloadedOptions'), 999);
            set_transient('asp_autoload_optimized', true, DAY_IN_SECONDS);
        }
    }

    public function optimizeDatabase()
    {
        // Only delete spam when the admin has explicitly opted in.
        if (!get_option('asp_auto_delete_spam', 0)) {
            return;
        }

        // Remove spam comments older than 30 days
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }

    public function optimizeAutoloadedOptions()
    {
        global $wpdb;

        // Find large autoloaded options
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $large_options = $wpdb->get_results("
            SELECT option_name, LENGTH(option_value) as size 
            FROM {$wpdb->options} 
            WHERE autoload = 'yes' 
            AND LENGTH(option_value) > 1000000
        ");

        // Critical options that must stay autoloaded for a working site.
        $protected = array(
            'active_plugins',
            'siteurl',
            'home',
            'template',
            'stylesheet'
        );

        foreach ($large_options as $option) {
            if (in_array($option->option_name, $protected, true)) {
                continue;
            }
            // Set large options to not autoload
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update(
                $wpdb->options,
                array('autoload' => 'no'),
                array('option_name' => $option->option_name)
            );
        }
    }

    /**
     * Kept for backwards compatibility. The hardened implementation lives
     * in asp_get_client_ip() (main plugin file): REMOTE_ADDR is trusted by
     * default and proxy headers only when explicitly configured.
     */
    private function getRealIpAddress()
    {
        return function_exists('asp_get_client_ip') ? asp_get_client_ip() : '';
    }
}