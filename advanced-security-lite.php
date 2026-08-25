<?php
/**
 * Plugin Name: Advanced Security Lite
 * Plugin URI: https://ssinternet.in/products/wordpress-plugins/advanced-security-lite
 * Description: A powerful WordPress security plugin featuring a modern, intuitive interface and advanced protection tools to safeguard your website from threats.
 * Version: 1.0.1
 * Author: Anuj Kumar Singh
 * Author URI: https://ssinternet.in/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-security-lite
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress version compatibility check
function asp_check_requirements()
{
    global $wp_version;

    $min_wp_version = '5.8';
    $min_php_version = '7.4';
    $current_php_version = phpversion();

    $errors = array();

    // Check WordPress version
    if (version_compare($wp_version, $min_wp_version, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required WordPress version, 2: Current WordPress version */
            esc_html__('Advanced Security Lite requires WordPress %1$s or higher. You are running version %2$s.', 'advanced-security-lite'),
            $min_wp_version,
            esc_html($wp_version)
        );
    }

    // Check PHP version
    if (version_compare($current_php_version, $min_php_version, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            esc_html__('Advanced Security Lite requires PHP %1$s or higher. You are running version %2$s.', 'advanced-security-lite'),
            $min_php_version,
            esc_html($current_php_version)
        );
    }

    // Display errors if any
    if (!empty($errors)) {
        add_action('admin_notices', function () use ($errors) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p><strong>Advanced Security Lite:</strong> ' . esc_html($error) . '</p></div>';
            }
        });

        // Deactivate the plugin
        add_action('admin_init', function () {
            deactivate_plugins(plugin_basename(__FILE__));
        });

        return false;
    }

    return true;
}

// Run requirements check
if (!asp_check_requirements()) {
    return;
}

// Define plugin constants
if (!defined('ASP_PLUGIN_FILE')) {
    define('ASP_PLUGIN_FILE', __FILE__);
}

// Read plugin headers dynamically
$asp_plugin_data = get_file_data(__FILE__, array(
    'Version' => 'Version',
    'Name' => 'Plugin Name',
    'Author' => 'Author',
    'AuthorURI' => 'Author URI',
    'PluginURI' => 'Plugin URI'
));

if (!defined('ASP_VERSION')) {
    define('ASP_VERSION', $asp_plugin_data['Version']);
}
if (!defined('ASP_NAME')) {
    define('ASP_NAME', $asp_plugin_data['Name']);
}
if (!defined('ASP_AUTHOR')) {
    define('ASP_AUTHOR', $asp_plugin_data['Author']);
}
if (!defined('ASP_AUTHOR_URI')) {
    define('ASP_AUTHOR_URI', $asp_plugin_data['AuthorURI']);
}
if (!defined('ASP_PLUGIN_URI')) {
    define('ASP_PLUGIN_URI', $asp_plugin_data['PluginURI']);
}
if (!defined('ASP_PLUGIN_URL')) {
    define('ASP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('ASP_PLUGIN_PATH')) {
    define('ASP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('ASP_PLUGIN_BASENAME')) {
    define('ASP_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Main plugin class
if (!class_exists('AdvancedSecurityLite')) {
    class AdvancedSecurityLite
    {

        private static $instance = null;

        public static function getInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct()
        {
            // Register activation/deactivation hooks first
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));

            // Hook into WordPress initialization with lower priority to avoid conflicts
            add_action('plugins_loaded', array($this, 'init'), 20);
            add_action('admin_menu', array($this, 'addAdminMenu'), 99);
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'), 20);

            // Initialize security features after WordPress is fully loaded
            // Changed from 'init' to 'plugins_loaded' to ensure hooks added in features (like 'init') are valid
            add_action('plugins_loaded', array($this, 'initSecurityFeatures'), 20);

            // Add custom cron schedules
            add_filter('cron_schedules', array($this, 'addCustomCronSchedules'), 20);
        }

        public function init()
        {
            // Load text domain is handled by WordPress for plugins on repository
        }

        public function addAdminMenu()
        {
            add_menu_page(
                __('Advanced Security Lite', 'advanced-security-lite'),
                __('Security Lite', 'advanced-security-lite'),
                'manage_options',
                'advanced-security-lite',
                array($this, 'adminPage'),
                'dashicons-shield-alt',
                99 // Lower priority to avoid conflicts
            );
        }

        public function enqueueAdminScripts($hook)
        {
            if ($hook !== 'toplevel_page_advanced-security-lite') {
                return;
            }

            // Enqueue Phosphor Icons (Local)
            wp_enqueue_style('asp-phosphor-icons', ASP_PLUGIN_URL . 'assets/css/phosphor.css', array(), $this->asset_version('assets/css/phosphor.css'));

            // Cache-busted versions: plugin version + file modification time,
            // so browsers always fetch the current asset after an update.
            wp_enqueue_style('asp-admin-css', ASP_PLUGIN_URL . 'assets/css/admin.css', array('asp-phosphor-icons'), $this->asset_version('assets/css/admin.css'));
            wp_enqueue_script('asp-admin-js', ASP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), $this->asset_version('assets/js/admin.js'), true);

            // Media library for the custom login logo picker.
            wp_enqueue_media();

            wp_localize_script('asp-admin-js', 'asp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('asp_nonce'),
                'errorlog_download_url' => wp_nonce_url(
                    admin_url('admin-post.php?action=asp_download_error_log'),
                    'asp_errorlog_download'
                ),
                'i18n' => array(
                    'saving' => __('Saving...', 'advanced-security-lite'),
                    'saved' => __('✓ Saved successfully!', 'advanced-security-lite'),
                    'savedNotify' => __('Settings saved successfully!', 'advanced-security-lite'),
                    'savedReloading' => __('Settings saved! Reloading...', 'advanced-security-lite'),
                    'saveError' => __('Error saving settings: ', 'advanced-security-lite'),
                    'connError' => __('Connection error occurred.', 'advanced-security-lite'),
                    'connectionError' => __('Connection error.', 'advanced-security-lite'),
                    'errorPrefix' => __('Error: ', 'advanced-security-lite'),
                    'saltConfirm' => __('This will regenerate all WordPress salt keys and log you out immediately. Are you sure?', 'advanced-security-lite'),
                    'regenerating' => __('Regenerating...', 'advanced-security-lite'),
                    'saltSuccess' => __('Salt keys regenerated!', 'advanced-security-lite'),
                    'resetConfirm' => __('⚠️ EMERGENCY RESET\n\nThis will reset all security settings to defaults.\n\nContinue?', 'advanced-security-lite'),
                    'resetting' => __('Resetting...', 'advanced-security-lite'),
                    'resetComplete' => __('Reset complete! Reloading...', 'advanced-security-lite'),
                    'modalTitle' => __('Security Update Complete', 'advanced-security-lite'),
                    'redirectingIn' => __('Redirecting in %d seconds...', 'advanced-security-lite'),
                    'loginNow' => __('Login Now', 'advanced-security-lite'),
                    'protectedText' => __('Protected', 'advanced-security-lite'),
                    'partialText' => __('Partial', 'advanced-security-lite'),
                    'unprotectedText' => __('Unprotected', 'advanced-security-lite'),
                    'scoreExcellent' => __('Excellent', 'advanced-security-lite'),
                    'scoreGood' => __('Good', 'advanced-security-lite'),
                    'scoreFair' => __('Fair', 'advanced-security-lite'),
                    'scorePoor' => __('Needs Attention', 'advanced-security-lite'),
                    'logNotFound' => __('Log file not found.', 'advanced-security-lite'),
                    'logEmpty' => __('The log file is currently empty.', 'advanced-security-lite'),
                    'logTruncated' => __('Showing the most recent entries — the full log is available via Download.', 'advanced-security-lite'),
                    'logsNone' => __('No log files were found on this site.', 'advanced-security-lite'),
                    'viewLabel' => __('View', 'advanced-security-lite'),
                    'downloadLabel' => __('Download', 'advanced-security-lite'),
                    'clearLabel' => __('Clear', 'advanced-security-lite'),
                    'clearLogConfirm' => __('Are you sure you want to permanently clear this log file?', 'advanced-security-lite'),
                    'logCleared' => __('Log cleared.', 'advanced-security-lite')
                )
            ));

            // Config for the self-contained clear-logs module below.
            wp_add_inline_script('asp-admin-js', 'window.ASL = window.ASL || {}; window.ASL.cfg = ' . wp_json_encode(array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('asp_nonce'),
                'i18n' => array(
                    'label' => __('Clear All Logs', 'advanced-security-lite'),
                    'armText' => __('Click again to confirm', 'advanced-security-lite'),
                    'clearing' => __('Clearing…', 'advanced-security-lite'),
                    'success' => __('All logs cleared successfully!', 'advanced-security-lite'),
                    'clearedHeading' => __('Logs Cleared', 'advanced-security-lite'),
                    'clearedText' => __('There are no log entries to display.', 'advanced-security-lite'),
                    'clearedInfo' => __('All logs were cleared.', 'advanced-security-lite'),
                    'errorPrefix' => __('Error: ', 'advanced-security-lite'),
                    'connError' => __('Connection error. Please try again.', 'advanced-security-lite'),
                ),
            )) . ';', 'before');

            // Self-contained "Clear All Logs" module: vanilla JS, own <script>
            // tag, capture-phase listener. It works even if admin.js fails to
            // parse, jQuery is missing, or another script stops propagation.
            $clear_logs_js = <<<'JS'
(function () {
    'use strict';

    function cfg() {
        window.ASL = window.ASL || {};
        window.ASL.cfg = window.ASL.cfg || {};
        return window.ASL.cfg;
    }

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = String(str == null ? '' : str);
        return d.innerHTML;
    }

    function toast(message, kind) {
        var colors = {
            success: { bg: '#edfaef', border: '#b8e6bf', text: '#00701a' },
            error: { bg: '#fcf0f1', border: '#f5c4c5', text: '#b32d2e' }
        };
        var c = colors[kind] || colors.error;

        var el = document.createElement('div');
        el.style.cssText = 'position:fixed;top:48px;right:24px;z-index:999999;max-width:380px;' +
            'padding:12px 16px;border-radius:6px;border:1px solid ' + c.border + ';' +
            'background:' + c.bg + ';color:' + c.text + ';font:500 13px/1.5 -apple-system,Segoe UI,Arial,sans-serif;' +
            'box-shadow:0 5px 16px rgba(0,0,0,.12);';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 5000);
    }

    function markCleared(t) {
        var emptyHtml = '<div class="asl-empty-state"><div class="asl-empty-icon">✅</div>' +
            '<h4>' + esc(t.clearedHeading) + '</h4>' +
            '<p>' + esc(t.clearedText) + '</p></div>';

        var panel = document.getElementById('panel-activity');
        if (panel) {
            panel.querySelectorAll('.asl-card-body').forEach(function (body) {
                body.innerHTML = emptyHtml;
            });
        }

        document.querySelectorAll('.asl-log-info').forEach(function (info) {
            info.textContent = t.clearedInfo;
        });
    }

    function run(btn) {
        var c = cfg();
        var t = c.i18n || {};

        if (!c || !c.ajaxUrl || !c.nonce) {
            toast('Configuration missing — please reload the page.', 'error');
            return;
        }

        var original = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = t.clearing || 'Clearing…';

        var body = new URLSearchParams();
        body.append('action', 'asp_clear_logs');
        body.append('nonce', c.nonce);

        fetch(c.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success) {
                    markCleared(t);
                    toast(t.success, 'success');
                } else {
                    var msg = res && res.data ? res.data : 'Unknown error';
                    toast((t.errorPrefix || 'Error: ') + msg, 'error');
                }
                btn.disabled = false;
                btn.innerHTML = original;
            })
            .catch(function () {
                toast(t.connError, 'error');
                btn.disabled = false;
                btn.innerHTML = original;
            });
    }

    /*
     * Two-click confirmation instead of window.confirm():
     * native dialogs are silently suppressed by browsers once the user
     * ticks "prevent this page from creating additional dialogs", which
     * made the button appear completely dead. First click arms the button
     * ("Click again to confirm"), second click within 3.5s executes.
     */
    var armed = null;

    function disarm(btn, t) {
        armed = null;
        if (btn) {
            btn.innerHTML = btn.getAttribute('data-asl-label') || t.label || btn.innerHTML;
            btn.classList.remove('asl-armed');
        }
    }

    function handleClick(btn) {
        var c = cfg();
        var t = c.i18n || {};

        if (!armed || armed.btn !== btn) {
            if (armed && armed.timer) { clearTimeout(armed.timer); }
            if (armed && armed.btn && armed.btn !== btn) { disarm(armed.btn, t); }

            btn.setAttribute('data-asl-label', btn.innerHTML);
            btn.innerHTML = t.armText || 'Click again to confirm';
            btn.classList.add('asl-armed');
            armed = {
                btn: btn,
                timer: setTimeout(function () { disarm(btn, t); }, 3500)
            };
            return;
        }

        clearTimeout(armed.timer);
        armed = null;
        run(btn);
    }

    /* 1) Direct binding on the element itself. */
    function bindDirect() {
        var btn = document.getElementById('asp-clear-logs');
        if (btn && !btn.dataset.aslBound) {
            btn.dataset.aslBound = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                handleClick(btn);
            });
        }
    }
    bindDirect();
    document.addEventListener('DOMContentLoaded', bindDirect);

    /* 2) Document capture delegation as backup (re-renders, timing). */
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('#asp-clear-logs') : null;
        if (!btn) { return; }
        if (btn.dataset.aslBound) { return; } // direct binding owns it
        e.preventDefault();
        try {
            handleClick(btn);
        } catch (err) {
            toast('Unexpected error occurred.', 'error');
        }
    }, true);
})();
JS;

            wp_add_inline_script('asp-admin-js', $clear_logs_js, 'after');
        }

        /**
         * Cache-busting asset version: plugin version + file modification
         * time, so browsers always fetch the current file after an update.
         *
         * @param string $relative_path Path relative to the plugin directory.
         * @return string
         */
        public function asset_version($relative_path)
        {
            $path = ASP_PLUGIN_PATH . ltrim($relative_path, '/');

            if (file_exists($path)) {
                clearstatcache(true, $path);
                return ASP_VERSION . '.' . (string) filemtime($path);
            }

            return ASP_VERSION . '.0';
        }

        public function adminPage()
        {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'advanced-security-lite'));
            }
            include ASP_PLUGIN_PATH . 'includes/admin-page.php';
        }

        public function initSecurityFeatures()
        {
            // Check if files exist before including
            $files = array(
                ASP_PLUGIN_PATH . 'includes/security-features.php',
                ASP_PLUGIN_PATH . 'includes/recaptcha.php',
                ASP_PLUGIN_PATH . 'includes/image-captcha.php',
                ASP_PLUGIN_PATH . 'includes/enhancements.php',
                ASP_PLUGIN_PATH . 'includes/login-customizer.php'
            );

            foreach ($files as $file) {
                if (!file_exists($file)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('Advanced Security Lite: Missing file - ' . $file);
                    return;
                }
            }

            // Include security modules
            require_once ASP_PLUGIN_PATH . 'includes/security-features.php';
            require_once ASP_PLUGIN_PATH . 'includes/recaptcha.php';
            require_once ASP_PLUGIN_PATH . 'includes/image-captcha.php';
            require_once ASP_PLUGIN_PATH . 'includes/enhancements.php';
            require_once ASP_PLUGIN_PATH . 'includes/login-customizer.php';

            // Initialize features based on settings with error handling
            try {
                if (class_exists('ASP_SecurityFeatures')) {
                    new ASP_SecurityFeatures();
                }
                if (class_exists('ASP_Recaptcha')) {
                    new ASP_Recaptcha();
                }
                if (class_exists('ASP_ImageCaptcha')) {
                    new ASP_ImageCaptcha();
                }
                if (class_exists('ASP_LoginCustomizer')) {
                    new ASP_LoginCustomizer();
                }
                if (class_exists('ASP_Enhancements')) {
                    new ASP_Enhancements();
                }
            } catch (\Throwable $e) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Advanced Security Lite: Error initializing features - ' . $e->getMessage());
            }
        }

        public function addCustomCronSchedules($schedules)
        {
            $schedules['daily'] = array(
                'interval' => DAY_IN_SECONDS,
                'display' => __('Daily', 'advanced-security-lite')
            );
            $schedules['weekly'] = array(
                'interval' => WEEK_IN_SECONDS,
                'display' => __('Weekly', 'advanced-security-lite')
            );
            $schedules['monthly'] = array(
                'interval' => 30 * DAY_IN_SECONDS, // 30 days
                'display' => __('Monthly', 'advanced-security-lite')
            );
            return $schedules;
        }

        public function activate()
        {
            // Set default options from the shared settings schema.
            foreach (asp_get_settings_schema() as $key => $rule) {
                add_option('asp_' . $key, $rule['default']);
            }

            // Schedule recurring jobs once here rather than on every page load.
            if (!wp_next_scheduled('asp_cleanup_old_logs')) {
                wp_schedule_event(time(), 'daily', 'asp_cleanup_old_logs');
            }

            if (get_option('asp_auto_delete_spam', 0) && !wp_next_scheduled('asp_optimize_database')) {
                wp_schedule_event(time(), 'weekly', 'asp_optimize_database');
            }
        }

        public function deactivate()
        {
            // Clean up every scheduled event this plugin created.
            wp_clear_scheduled_hook('asp_regenerate_salts');
            wp_clear_scheduled_hook('asp_cleanup_old_logs');
            wp_clear_scheduled_hook('asp_optimize_database');

            // Flush rewrite rules
            flush_rewrite_rules();
        }

    }
}

// Safe plugin initialization - only after WordPress is loaded
if (!function_exists('asp_safe_init')) {
    function asp_safe_init()
    {
        // Initialize the plugin
        AdvancedSecurityLite::getInstance();
    }
}

// Hook into WordPress initialization
add_action('plugins_loaded', 'asp_safe_init', 1);

// Recurring log cleanup: scheduled at activation (and cleared on
// deactivation); only the handler is registered here.
add_action('asp_cleanup_old_logs', 'asp_cleanup_logs_older_than_3_days');

/**
 * Cleanup logs older than 3 days
 */
function asp_cleanup_logs_older_than_3_days()
{
    $three_days_ago = time() - (3 * DAY_IN_SECONDS);

    // Cleanup failed logins
    $failed_logins = get_option('asp_failed_logins', array());
    foreach ($failed_logins as $ip => $attempts) {
        $failed_logins[$ip] = array_filter($attempts, function ($attempt) use ($three_days_ago) {
            return isset($attempt['time']) && $attempt['time'] > $three_days_ago;
        });
        if (empty($failed_logins[$ip])) {
            unset($failed_logins[$ip]);
        }
    }
    update_option('asp_failed_logins', $failed_logins);

    // Cleanup admin access log
    $admin_log = get_option('asp_admin_access_log', array());
    $admin_log = array_filter($admin_log, function ($entry) use ($three_days_ago) {
        return isset($entry['time']) && $entry['time'] > $three_days_ago;
    });
    update_option('asp_admin_access_log', array_values($admin_log));
}

// AJAX handlers - register after WordPress is loaded
add_action('wp_loaded', function () {
    add_action('wp_ajax_asp_save_settings', 'asp_save_settings');
    add_action('wp_ajax_asp_regenerate_salts', 'asp_regenerate_salts_now');
    add_action('wp_ajax_asp_emergency_reset', 'asp_emergency_reset');
    add_action('wp_ajax_asp_clear_logs', 'asp_clear_all_logs');
    add_action('wp_ajax_asp_get_error_logs', 'asp_get_error_logs');
    add_action('wp_ajax_asp_read_error_log', 'asp_read_error_log');
    add_action('wp_ajax_asp_clear_error_log', 'asp_clear_error_log');
    add_action('admin_post_asp_download_error_log', 'asp_download_error_log');
});

/**
 * Resolve the client IP address.
 *
 * Only the direct connection IP (REMOTE_ADDR) is used. Headers like
 * X-Forwarded-For are client-controlled and trivially spoofed, so they are
 * never trusted — spoofing them cannot bypass the login rate limiter.
 *
 * @return string Client IP or empty string when unavailable.
 */
function asp_get_client_ip()
{
    if (empty($_SERVER['REMOTE_ADDR'])) {
        return '';
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitized below before use.
    $raw = wp_unslash($_SERVER['REMOTE_ADDR']);
    $parts = explode(',', (string) $raw);
    $ip = trim($parts[0]);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }

    return sanitize_text_field($ip);
}

/**
 * Human-readable file size.
 *
 * @param int $bytes Size in bytes.
 * @return string
 */
function asp_size_human($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB');
    $index = 0;
    $value = (float) $bytes;

    while ($value >= 1024 && $index < count($units) - 1) {
        $value /= 1024;
        $index++;
    }

    return round($value, $index > 1 ? 1 : 0) . ' ' . $units[$index];
}

/**
 * Read the tail of the error log without loading the whole file.
 *
 * @param string $path Log file path.
 * @param int    $max  Maximum bytes to read.
 * @return array{content:string,truncated:bool}
 */
function asp_read_log_tail($path, $max = 262144)
{
    $max = (int) apply_filters('asp_errorlog_tail_bytes', $max);

    // The log may have been written (or truncated) earlier in this request;
    // clear the stat cache so filesize()/filemtime() reflect reality.
    clearstatcache(true, $path);

    $size = (int) @filesize($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    if ($size <= 0) {
        return array('content' => '', 'truncated' => false);
    }

    $offset = max(0, $size - $max);
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $handle = @fopen($path, 'rb'); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    if (!$handle) {
        return array('content' => '', 'truncated' => false);
    }

    fseek($handle, $offset);
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations___
    $content = (string) stream_get_contents($handle);
    fclose($handle);

    $truncated = $offset > 0;
    if ($truncated) {
        // Drop the (likely partial) first line.
        $newline = strpos($content, "\n");
        if (false !== $newline) {
            $content = substr($content, $newline + 1);
        }
    }

    return array('content' => $content, 'truncated' => $truncated);
}

/**
 * Discover all readable log files on the site.
 *
 * Sources: the WP_DEBUG_LOG path (when set to a string), the PHP ini
 * error_log location, wp-content/debug.log, and any *.log files in the
 * wp-content root and the uploads root. Newest files first. Clients only
 * ever reference logs by numeric key into this server-computed list —
 * never by path.
 *
 * @return array[]
 */
function asp_discover_error_logs()
{
    $candidates = array();

    if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && '' !== WP_DEBUG_LOG && '1' !== WP_DEBUG_LOG) {
        $candidates[] = WP_DEBUG_LOG;
    }

    $ini_path = ini_get('error_log');
    if ($ini_path) {
        $candidates[] = $ini_path;
    }

    $candidates[] = WP_CONTENT_DIR . '/debug.log';

    // Common log locations inside wp-content (top level + uploads root).
    foreach ((array) glob(WP_CONTENT_DIR . '/*.log') as $found) {
        $candidates[] = $found;
    }

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['basedir'])) {
        foreach ((array) glob(trailingslashit($upload_dir['basedir']) . '*.log') as $found) {
            $candidates[] = $found;
        }
    }

    $logs = array();
    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || '' === $candidate) {
            continue;
        }

        $real = realpath($candidate);
        if (!$real || isset($logs[$real])) {
            continue;
        }

        if (!@is_file($real) || !@is_readable($real)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            continue;
        }

        clearstatcache(true, $real);

        $logs[$real] = array(
            'path' => $real,
            'name' => basename($real),
            'size' => (int) @filesize($real), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            'modified' => (int) @filemtime($real), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            'writable' => wp_is_writable($real),
        );
    }

    usort($logs, function ($a, $b) {
        return $b['modified'] <=> $a['modified'];
    });

    return array_values($logs);
}

/**
 * Resolve a client-supplied log key to a server-discovered log entry.
 *
 * @param mixed $key Numeric key from the client.
 * @return array|null
 */
function asp_resolve_error_log($key)
{
    if (!is_numeric($key)) {
        return null;
    }

    $logs = asp_discover_error_logs();
    $index = (int) $key;

    return isset($logs[$index]) ? $logs[$index] : null;
}

/**
 * AJAX: list every log file detected on the site.
 */
function asp_get_error_logs()
{
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $logs = array();
    foreach (asp_discover_error_logs() as $index => $log) {
        $logs[] = array(
            'key' => $index,
            'name' => $log['name'],
            'path' => $log['path'],
            'size_human' => asp_size_human($log['size']),
            'modified_human' => $log['modified'] > 0
                ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['modified'])
                : '',
            'writable' => $log['writable'],
        );
    }

    wp_send_json_success($logs);
}

/**
 * AJAX: return the tail of one log (by numeric key).
 */
function asp_read_error_log()
{
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $log = asp_resolve_error_log(isset($_POST['key']) ? wp_unslash($_POST['key']) : '');

    if (null === $log) {
        wp_send_json_error(__('Log file not found.', 'advanced-security-lite'));
        return;
    }

    $tail = asp_read_log_tail($log['path']);

    wp_send_json_success(array(
        'name' => $log['name'],
        'meta' => asp_size_human($log['size']) . ' · ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['modified']),
        'content' => $tail['content'],
        'truncated' => $tail['truncated'],
    ));
}

/**
 * AJAX: truncate one log file (by numeric key).
 */
function asp_clear_error_log()
{
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $log = asp_resolve_error_log(isset($_POST['key']) ? wp_unslash($_POST['key']) : '');

    if (null === $log) {
        wp_send_json_error(__('Log file not found.', 'advanced-security-lite'));
        return;
    }

    if (!$log['writable']) {
        wp_send_json_error(__('The log file is not writable. Check file permissions.', 'advanced-security-lite'));
        return;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    if (file_put_contents($log['path'], '') === false) {
        wp_send_json_error(__('Failed to clear the log file.', 'advanced-security-lite'));
        return;
    }

    wp_send_json_success(__('Log cleared.', 'advanced-security-lite'));
}

/**
 * Stream one log file as a download (admin-post endpoint, by numeric key).
 */
function asp_download_error_log()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to do that.', 'advanced-security-lite'));
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'asp_errorlog_download')) {
        wp_die(esc_html__('Security check failed.', 'advanced-security-lite'));
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $log = asp_resolve_error_log(isset($_GET['key']) ? wp_unslash($_GET['key']) : '');

    if (null === $log) {
        wp_die(esc_html__('Log file not found.', 'advanced-security-lite'));
    }

    $size = (int) @filesize($log['path']); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    $filename = sanitize_file_name($log['name']);

    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) $size);

    if ($size > 0) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $handle = @fopen($log['path'], 'rb'); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ($handle) {
            while (!feof($handle)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
                echo (string) fread($handle, 8192); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file download
            }
            fclose($handle);
        }
    }

    exit;
}

/**
 * Shared schema for every asp_* option the plugin uses.
 *
 * Single source of truth consumed by activation defaults, the settings
 * save handler, and emergency reset so they can never drift apart.
 *
 * Each entry: key (without the asp_ prefix) => [
 *   'default' => mixed,
 *   'type'    => 'bool'|'int'|'string'|'enum'|'email'|'textarea',
 *   'min'    => int|null  (only for int),
 *   'max'    => int|null  (only for int),
 *   'values' => array    (only for enum - the allowed values),
 * ]
 *
 * @return array<string, array{default:mixed, type:string, min:int|null, max:int|null}>
 */
function asp_get_settings_schema()
{
    return array(
        'recaptcha_v2_enabled'         => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'recaptcha_v3_enabled'         => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'image_captcha_enabled'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'recaptcha_site_key'           => array('default' => '', 'type' => 'string', 'min' => null, 'max' => null),
        'recaptcha_secret_key'         => array('default' => '', 'type' => 'string', 'min' => null, 'max' => null),
        'disable_wp_json'              => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_feeds'                => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_rest_api'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_registration'         => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_password_recovery'    => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'auto_regenerate_salts'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'salt_regeneration_frequency'  => array('default' => 'monthly', 'type' => 'enum', 'min' => null, 'max' => null, 'values' => array('daily', 'weekly', 'monthly')),
        'disable_xmlrpc'               => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_bad_requests'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_dir_listing'         => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_malicious_uploads'   => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_plugin_upload'       => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_theme_upload'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disallow_file_edit'           => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'hide_login_errors'            => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'hide_php_version'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'hide_wp_version'              => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'obfuscate_author_slugs'       => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'obfuscate_emails'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'hide_admin_username'          => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'protect_headers'              => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'prevent_user_enumeration'     => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_comments'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_pingbacks'            => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'disable_app_passwords'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'hide_admin_notices'           => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'error_log_viewer'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'login_accent_color'           => array('default' => '#2563eb', 'type' => 'string', 'min' => null, 'max' => null),
        'login_panel_side'             => array('default' => 'left', 'type' => 'enum', 'min' => null, 'max' => null, 'values' => array('left', 'right', 'none')),
        'login_form_style'             => array('default' => 'solid', 'type' => 'enum', 'min' => null, 'max' => null, 'values' => array('solid', 'glass')),
        'login_logo'                   => array('default' => '', 'type' => 'string', 'min' => null, 'max' => null),
        'custom_login_design'          => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'enable_login_limit'           => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'max_login_attempts'           => array('default' => 5, 'type' => 'int', 'min' => 1, 'max' => 100),
        'lockout_duration'             => array('default' => 30, 'type' => 'int', 'min' => 1, 'max' => 1440),
        'maintenance_mode'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'maintenance_message'          => array('default' => 'We are currently performing scheduled maintenance. Please check back soon.', 'type' => 'textarea', 'min' => null, 'max' => null),
        'limit_revisions'              => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'revisions_limit'              => array('default' => 5, 'type' => 'int', 'min' => 1, 'max' => 100),
        'disable_php_execution'        => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'protect_sensitive_files'      => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
        'remove_data_on_uninstall'     => array('default' => 1, 'type' => 'bool', 'min' => null, 'max' => null),
        'auto_delete_spam'             => array('default' => 0, 'type' => 'bool', 'min' => null, 'max' => null),
    );
}

/**
 * Sanitize a single setting value according to the schema rule.
 *
 * @param mixed  $value Raw value from the form.
 * @param array  $rule  Schema rule for this key.
 * @return mixed
 */
function asp_sanitize_setting($value, $rule)
{
    switch ($rule['type']) {
        case 'bool':
            return $value ? 1 : 0;
        case 'int':
            $v = (int) $value;
            if (null !== $rule['min']) {
                $v = max($rule['min'], $v);
            }
            if (null !== $rule['max']) {
                $v = min($rule['max'], $v);
            }
            return $v;
        case 'enum':
            $v = sanitize_text_field(wp_unslash($value));
            $valid = isset($rule['values']) && is_array($rule['values']) ? $rule['values'] : array();
            return in_array($v, $valid, true) ? $v : $rule['default'];
        case 'email':
            return sanitize_email(wp_unslash($value));
        case 'textarea':
            return sanitize_textarea_field(wp_unslash($value));
        default:
            return sanitize_text_field(wp_unslash($value));
    }
}

function asp_save_settings()
{
    // Check if this is a valid AJAX request
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // Verify nonce and capabilities
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verification doesn't require sanitization
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Check if settings are provided
    if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
        wp_send_json_error('No settings provided');
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $settings = wp_unslash($_POST['settings']);

    $schema = asp_get_settings_schema();
    $salt_changed = false;

    foreach ($settings as $raw_key => $value) {
        $key = sanitize_key($raw_key);
        if (!isset($schema[$key])) {
            continue; // Unknown key — ignore.
        }

        $sanitized = asp_sanitize_setting($value, $schema[$key]);
        update_option('asp_' . $key, $sanitized);

        if ('auto_regenerate_salts' === $key || 'salt_regeneration_frequency' === $key) {
            $salt_changed = true;
        }
    }

    // Reschedule salt regeneration only when its settings actually changed.
    if ($salt_changed) {
        wp_clear_scheduled_hook('asp_regenerate_salts');
        if (get_option('asp_auto_regenerate_salts', 0)) {
            $frequency = get_option('asp_salt_regeneration_frequency', 'monthly');
            $valid_freqs = array('daily', 'weekly', 'monthly');
            if (!in_array($frequency, $valid_freqs, true)) {
                $frequency = 'monthly';
                update_option('asp_salt_regeneration_frequency', $frequency);
            }
            wp_schedule_event(time() + 3600, $frequency, 'asp_regenerate_salts');
        }
    }

    // Toggle the DB optimization cron to match the new setting.
    if (isset($settings['auto_delete_spam'])) {
        if ((int) $settings['auto_delete_spam'] && !wp_next_scheduled('asp_optimize_database')) {
            wp_schedule_event(time(), 'weekly', 'asp_optimize_database');
        } elseif (empty($settings['auto_delete_spam'])) {
            wp_clear_scheduled_hook('asp_optimize_database');
        }
    }

    wp_send_json_success('Settings saved successfully');
}

/**
 * Safely regenerate the WordPress salt keys in wp-config.php.
 *
 * - Verifies at least one salt definition was found before writing.
 * - Writes atomically via a temp file + rename (with a direct-write fallback
 *   for filesystems that refuse to rename over an existing file).
 * - Keeps a pre-write backup only for the duration of the write; it is
 *   deleted again immediately after the swap succeeds so a file full of
 *   credentials is never left sitting in the web root.
 * - Destroys every user's session so all sessions are forced out.
 *
 * @return array|WP_Error Result info or error object.
 */
function asp_regenerate_wp_config_salts()
{
    $salts = array(
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT'
    );

    $wp_config_path = ABSPATH . 'wp-config.php';

    if (!file_exists($wp_config_path)) {
        return new WP_Error('asp_config_missing', __('wp-config.php file not found', 'advanced-security-lite'));
    }

    if (!wp_is_writable($wp_config_path)) {
        return new WP_Error('asp_config_not_writable', __('wp-config.php is not writable. Please check file permissions.', 'advanced-security-lite'));
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
    $config_content = file_get_contents($wp_config_path);
    if (false === $config_content) {
        return new WP_Error('asp_config_unreadable', __('Unable to read wp-config.php', 'advanced-security-lite'));
    }

    $replaced = 0;
    foreach ($salts as $salt) {
        $new_salt = wp_generate_password(64, true, true);
        $pattern = "/define\s*\(\s*['\"]" . preg_quote($salt, '/') . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)\s*;/";
        $config_content = preg_replace(
            $pattern,
            "define('" . $salt . "', '" . $new_salt . "');",
            $config_content,
            -1,
            $count
        );
        $replaced += $count;
    }

    if (0 === $replaced) {
        return new WP_Error('asp_no_salts_found', __('No salt key definitions were found in wp-config.php. If your salts are managed outside wp-config.php, disable automatic regeneration.', 'advanced-security-lite'));
    }

    // Remove any backup left behind by a previously failed run before a
    // fresh one is created — that copy contains live database credentials
    // and must not linger in the web root.
    $backup_path = $wp_config_path . '.asp-backup';
    if (file_exists($backup_path)) {
        wp_delete_file($backup_path);
    }

    // Backup the current config for the duration of the write only; it is
    // deleted again right after the swap succeeds.
    if (!@copy($wp_config_path, $backup_path)) {
        $backup_path = '';
    }

    // Atomic write: temp file then rename; fall back to a direct write when
    // rename() cannot replace an existing file (e.g. on Windows).
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    $written = false;
    $tmp_path = $wp_config_path . '.asp-tmp';
    if (file_put_contents($tmp_path, $config_content) !== false) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        if (@rename($tmp_path, $wp_config_path)) {
            $written = true;
        } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            $written = file_put_contents($wp_config_path, $config_content) !== false;
            wp_delete_file($tmp_path);
        }
    }

    if (!$written) {
        return new WP_Error('asp_write_failed', __('Failed to write to wp-config.php', 'advanced-security-lite'));
    }

    // The write succeeded — drop the credential backup immediately.
    if ('' !== $backup_path && file_exists($backup_path)) {
        wp_delete_file($backup_path);
        $backup_path = '';
    }

    // Force logout for every user by destroying all session tokens.
    if (class_exists('WP_Session_Tokens')) {
        WP_Session_Tokens::destroy_all_sessions();
    }
    wp_clear_auth_cookie();

    return array(
        'replaced' => $replaced,
        'backup' => '' !== $backup_path
    );
}

function asp_regenerate_salts_now()
{
    // Check if this is a valid AJAX request
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // Verify nonce and capabilities
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $result = asp_regenerate_wp_config_salts();

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }

    wp_send_json_success(array(
        'message' => __('Salt keys regenerated successfully. All users have been logged out for security.', 'advanced-security-lite'),
        'logout' => true,
        'redirect_url' => wp_login_url()
    ));
}

function asp_emergency_reset()
{
    // Check if this is a valid AJAX request
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // Verify nonce and capabilities
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Reset all options to schema defaults — single source of truth.
    foreach (asp_get_settings_schema() as $key => $rule) {
        update_option('asp_' . $key, $rule['default']);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('asp_regenerate_salts');
    wp_clear_scheduled_hook('asp_optimize_database');

    // Flush rewrite rules
    flush_rewrite_rules();

    wp_send_json_success('Emergency reset completed successfully');
}

/**
 * Clear all activity logs - AJAX handler
 */
function asp_clear_all_logs()
{
    // Check if this is a valid AJAX request
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        wp_die('Invalid request');
    }

    // Verify nonce and capabilities
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'asp_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Count what is being removed so the response proves the action ran.
    $failed_logins = (array) get_option('asp_failed_logins', array());
    $admin_log = (array) get_option('asp_admin_access_log', array());

    $failed_count = 0;
    foreach ($failed_logins as $attempts) {
        $failed_count += is_array($attempts) ? count($attempts) : 0;
    }

    // Clear failed logins
    update_option('asp_failed_logins', array());

    // Clear admin access log
    update_option('asp_admin_access_log', array());

    // Suppress the automatic admin-access log entry for the immediate
    // post-clear reload, otherwise the log repopulates instantly and the
    // button appears broken.
    set_transient('asp_suppress_access_log', 1, 30);

    // Lift any active login lockouts so clearing also means unlocking.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_asp\_lock\_%'");

    wp_send_json_success(array(
        'message' => __('All logs have been cleared successfully.', 'advanced-security-lite'),
        'removed' => array(
            'failed_logins' => $failed_count,
            'admin_access' => count($admin_log)
        )
    ));
}
