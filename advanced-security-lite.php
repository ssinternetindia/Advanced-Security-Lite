<?php
/**
 * Plugin Name: Advanced Security Lite
 * Plugin URI: https://ssinternet.in/products/wordpress-plugins/advanced-security-lite
 * Description: A powerful WordPress security plugin featuring a modern, intuitive interface and advanced protection tools to safeguard your website from threats.
 * Version: 1.0.0
 * Author: Anuj Kumar Singh
 * Author URI: https://ssinternet.in/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-security-lite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Tested up to: 6.9
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
            add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'), 20);

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
            wp_enqueue_style('asp-phosphor-icons', ASP_PLUGIN_URL . 'assets/css/phosphor.css', array(), ASP_VERSION);

            wp_enqueue_style('asp-admin-css', ASP_PLUGIN_URL . 'assets/css/admin.css', array('asp-phosphor-icons'), ASP_VERSION);
            wp_enqueue_script('asp-admin-js', ASP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ASP_VERSION, true);

            wp_localize_script('asp-admin-js', 'asp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('asp_nonce')
            ));
        }

        public function enqueueFrontendScripts()
        {
            if (get_option('asp_custom_login_design', 0)) {
                wp_enqueue_style('asp-login-css', ASP_PLUGIN_URL . 'assets/css/login.css', array(), ASP_VERSION);
            }
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
                ASP_PLUGIN_PATH . 'includes/login-customizer.php',
                ASP_PLUGIN_PATH . 'includes/enhancements.php'
            );

            foreach ($files as $file) {
                if (!file_exists($file)) {
                    // error_log('Advanced Security Lite: Missing file - ' . $file);
                    return;
                }
            }

            // Include security modules
            require_once ASP_PLUGIN_PATH . 'includes/security-features.php';
            require_once ASP_PLUGIN_PATH . 'includes/recaptcha.php';
            require_once ASP_PLUGIN_PATH . 'includes/login-customizer.php';
            require_once ASP_PLUGIN_PATH . 'includes/enhancements.php';

            // Initialize features based on settings with error handling
            try {
                if (class_exists('ASP_SecurityFeatures')) {
                    new ASP_SecurityFeatures();
                }
                if (class_exists('ASP_Recaptcha')) {
                    new ASP_Recaptcha();
                }
                if (class_exists('ASP_LoginCustomizer')) {
                    new ASP_LoginCustomizer();
                }
                if (class_exists('ASP_Enhancements')) {
                    new ASP_Enhancements();
                }
            } catch (Exception $e) {
                // error_log('Advanced Security Lite: Error initializing features - ' . $e->getMessage());
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
            // Set default options
            $default_options = array(
                'asp_recaptcha_v2_enabled' => 0,
                'asp_recaptcha_v3_enabled' => 0,
                'asp_recaptcha_site_key' => '',
                'asp_recaptcha_secret_key' => '',
                'asp_disable_wp_json' => 0,
                'asp_disable_feeds' => 0,
                'asp_disable_rest_api' => 0,
                'asp_disable_registration' => 0,
                'asp_disable_password_recovery' => 0,
                'asp_auto_regenerate_salts' => 0,
                'asp_salt_regeneration_frequency' => 'monthly',
                'asp_disable_xmlrpc' => 0,
                'asp_disallow_bad_requests' => 0,
                'asp_disallow_dir_listing' => 0,
                'asp_disallow_malicious_uploads' => 0,
                'asp_disallow_plugin_upload' => 0,
                'asp_disallow_theme_upload' => 0,
                'asp_disallow_file_edit' => 0,
                'asp_hide_login_errors' => 0,
                'asp_hide_php_version' => 0,
                'asp_hide_wp_version' => 0,

                'asp_obfuscate_author_slugs' => 0,
                'asp_obfuscate_emails' => 0,
                'asp_protect_headers' => 0,
                'asp_prevent_user_enumeration' => 0,
                'asp_disable_comments' => 0,
                'asp_custom_login_design' => 0,
                'asp_enable_security_logging' => 0,
                'asp_enable_login_limit' => 0,
                'asp_max_login_attempts' => 5,
                'asp_lockout_duration' => 30,
                'asp_email_notifications' => 0,
                'asp_notification_email' => get_option('admin_email'),
                'asp_enable_ip_whitelist' => 0,
                'asp_ip_whitelist' => '',
                'asp_disable_app_passwords' => 0,
                'asp_disable_pingbacks' => 0,
                'asp_hide_admin_username' => 0,
                'asp_maintenance_mode' => 0,
                'asp_maintenance_message' => 'We are currently performing scheduled maintenance. Please check back soon.',
                'asp_limit_revisions' => 0,
                'asp_revisions_limit' => 5
            );

            foreach ($default_options as $option => $value) {
                add_option($option, $value);
            }


        }

        public function deactivate()
        {
            // Clean up scheduled events
            wp_clear_scheduled_hook('asp_regenerate_salts');

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


// AJAX handlers - register after WordPress is loaded
add_action('wp_loaded', function () {
    add_action('wp_ajax_asp_save_settings', 'asp_save_settings');
    add_action('wp_ajax_asp_regenerate_salts', 'asp_regenerate_salts_now');
    add_action('wp_ajax_asp_emergency_reset', 'asp_emergency_reset');
});

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

    // Check if settings are provided
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $settings = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();

    // Sanitize and save settings
    foreach ($settings as $key => $value) {
        $key = sanitize_key(wp_unslash($key));
        // Use sanitize_text_field for values, but handle potential arrays if needed in future
        $value = is_array($value) ? array_map('sanitize_text_field', wp_unslash($value)) : sanitize_text_field(wp_unslash($value));
        update_option('asp_' . $key, $value);
    }

    // If salt regeneration settings changed, reschedule
    if (isset($settings['auto_regenerate_salts']) || isset($settings['salt_regeneration_frequency'])) {
        wp_clear_scheduled_hook('asp_regenerate_salts');

        if (get_option('asp_auto_regenerate_salts', 0)) {
            $frequency = get_option('asp_salt_regeneration_frequency', 'monthly');
            wp_schedule_event(time() + 3600, $frequency, 'asp_regenerate_salts');
        }
    }



    wp_send_json_success('Settings saved successfully');
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

    // Regenerate WordPress salt keys
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

    // Check if wp-config.php exists and is writable
    if (!file_exists($wp_config_path)) {
        wp_send_json_error('wp-config.php file not found');
        return;
    }

    if (!wp_is_writable($wp_config_path)) {
        wp_send_json_error('wp-config.php is not writable. Please check file permissions.');
        return;
    }

    // Read the config file
    $config_content = file_get_contents($wp_config_path);
    if ($config_content === false) {
        wp_send_json_error('Unable to read wp-config.php');
        return;
    }

    // Replace salt keys
    foreach ($salts as $salt) {
        $new_salt = wp_generate_password(64, true, true);
        $pattern = "/define\s*\(\s*['\"]" . preg_quote($salt, '/') . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)\s*;/";
        $replacement = "define('" . $salt . "', '" . $new_salt . "');";
        $config_content = preg_replace($pattern, $replacement, $config_content);
    }

    // Write back to file
    if (file_put_contents($wp_config_path, $config_content) !== false) {
        // Log out all users by clearing all sessions
        if (class_exists('WP_Session_Tokens')) {
            $sessions = WP_Session_Tokens::get_instance(get_current_user_id());
            $sessions->destroy_all();
        }

        // Clear current user session
        wp_clear_auth_cookie();

        wp_send_json_success(array(
            'message' => 'Salt keys regenerated successfully. You will be logged out for security.',
            'logout' => true,
            'redirect_url' => wp_login_url()
        ));
    } else {
        wp_send_json_error('Failed to write to wp-config.php');
    }
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

    // List of all plugin options to reset
    $options_to_reset = array(

        'asp_recaptcha_v2_enabled' => 0,
        'asp_recaptcha_v3_enabled' => 0,
        'asp_disable_wp_json' => 0,
        'asp_disable_feeds' => 0,
        'asp_disable_rest_api' => 0,
        'asp_disable_xmlrpc' => 0,
        'asp_auto_regenerate_salts' => 0,
        'asp_disable_registration' => 0,
        'asp_disable_password_recovery' => 0,
        'asp_hide_login_errors' => 0,
        'asp_disallow_bad_requests' => 0,
        'asp_disallow_dir_listing' => 0,
        'asp_disallow_malicious_uploads' => 0,
        'asp_disallow_plugin_upload' => 0,
        'asp_disallow_theme_upload' => 0,
        'asp_disallow_file_edit' => 0,
        'asp_hide_php_version' => 0,
        'asp_hide_wp_version' => 0,
        'asp_obfuscate_author_slugs' => 0,
        'asp_obfuscate_emails' => 0,
        'asp_protect_headers' => 0,
        'asp_prevent_user_enumeration' => 0,
        'asp_disable_comments' => 0,
        'asp_enable_security_logging' => 0,
        'asp_enable_login_limit' => 0,
        'asp_email_notifications' => 0,
        'asp_enable_ip_whitelist' => 0,
        'asp_disable_app_passwords' => 0,
        'asp_disable_pingbacks' => 0,
        'asp_hide_admin_username' => 0,
        'asp_maintenance_mode' => 0,
        'asp_limit_revisions' => 0
    );

    // Reset all options
    foreach ($options_to_reset as $option => $default_value) {
        update_option($option, $default_value);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('asp_regenerate_salts');

    // Flush rewrite rules
    flush_rewrite_rules();

    wp_send_json_success('Emergency reset completed successfully');
}

