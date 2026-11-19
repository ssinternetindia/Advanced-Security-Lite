<?php
/**
 * SS Sentinel - Uninstall Script
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database and reverts .htaccess
 * modifications made while the plugin was active.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Every option the plugin has ever shipped, including legacy names from
 * older versions so upgrades leave nothing behind. A wildcard LIKE 'asp_%'
 * delete was intentionally avoided: other plugins use the same prefix.
 *
 * @return array
 */
function asp_get_all_option_names()
{
    return array(
        // Core Security Settings
        'asp_recaptcha_v2_enabled',
        'asp_recaptcha_v3_enabled',
        'asp_image_captcha_enabled',
        'asp_recaptcha_site_key',
        'asp_recaptcha_secret_key',
        'asp_disable_wp_json',
        'asp_disable_feeds',
        'asp_disable_rest_api',
        'asp_disable_registration',
        'asp_disable_password_recovery',
        'asp_auto_regenerate_salts',
        'asp_salt_regeneration_frequency',
        'asp_disable_xmlrpc',
        'asp_disallow_bad_requests',
        'asp_disallow_dir_listing',
        'asp_disallow_malicious_uploads',
        'asp_disallow_plugin_upload',
        'asp_disallow_theme_upload',
        'asp_disallow_file_edit',
        'asp_hide_login_errors',
        'asp_hide_php_version',
        'asp_hide_wp_version',

        // Custom Login Design Settings
        'asp_custom_login_design',
        'asp_login_accent_color',
        'asp_login_panel_side',
        'asp_login_form_style',
        'asp_login_logo',

        // Privacy and Obfuscation Settings
        'asp_obfuscate_author_slugs',
        'asp_obfuscate_salt',
        'asp_obfuscate_emails',
        'asp_protect_headers',
        'asp_prevent_user_enumeration',
        'asp_disable_comments',
        'asp_hide_admin_notices',
        'asp_error_log_viewer',

        // Login Security
        'asp_enable_login_limit',
        'asp_max_login_attempts',
        'asp_lockout_duration',

        // IP Whitelist / Trust Proxy (legacy, removed in 1.0.1)
        'asp_enable_ip_whitelist',
        'asp_ip_whitelist',
        'asp_trust_proxy_headers',

        // Email notifications (legacy, removed in 1.0.1)
        'asp_email_notifications',
        'asp_notification_email',
        'asp_enable_security_logging',

        // Tools & new features
        'asp_disable_app_passwords',
        'asp_disable_pingbacks',
        'asp_hide_admin_username',
        'asp_maintenance_mode',
        'asp_maintenance_message',
        'asp_limit_revisions',
        'asp_revisions_limit',
        'asp_disable_php_execution',
        'asp_protect_sensitive_files',

        // Plugin behavior
        'asp_remove_data_on_uninstall',
        'asp_auto_delete_spam',

        // Logs (also covered by transients cleanup below)
        'asp_failed_logins',
        'asp_admin_access_log'
    );
}

/**
 * Remove all plugin options from database
 */
function asp_remove_all_options()
{
    foreach (asp_get_all_option_names() as $option) {
        delete_option($option);
    }
}

/**
 * Remove plugin transients
 */
function asp_remove_transients()
{
    global $wpdb;

    // Remove plugin-specific transients
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_asp\_%'");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_asp\_%'");
}

/**
 * Revert every .htaccess modification the plugin inserted and remove any
 * leftover backup files.
 */
function asp_cleanup_htaccess()
{
    if (!function_exists('insert_with_markers')) {
        require_once ABSPATH . 'wp-admin/includes/misc.php';
    }

    // Root .htaccess marker sections written via insert_with_markers().
    $root_htaccess = ABSPATH . '.htaccess';
    $markers = array(
        'Advanced Security Lite - Directory Listing',
        'Advanced Security Lite - Sensitive Files Protection',
        'Advanced Security Lite - Sensitive Files'
    );

    if (file_exists($root_htaccess) && wp_is_writable($root_htaccess)) {
        foreach ($markers as $marker) {
            insert_with_markers($root_htaccess, $marker, array());
        }
    }

    // Uploads .htaccess: rules were inserted via insert_with_markers().
    $upload_dir = wp_upload_dir();
    $uploads_basedir = isset($upload_dir['basedir']) ? $upload_dir['basedir'] : '';
    $uploads_htaccess = '' !== $uploads_basedir ? trailingslashit($uploads_basedir) . '.htaccess' : '';

    if ('' !== $uploads_htaccess && file_exists($uploads_htaccess) && wp_is_writable($uploads_htaccess)) {
        insert_with_markers($uploads_htaccess, 'Advanced Security Lite - Disable PHP Execution', array());

        // Delete the file if nothing remains outside our removed marker block.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
        $remaining = file_get_contents($uploads_htaccess);
        if (false !== $remaining && '' === trim($remaining)) {
            wp_delete_file($uploads_htaccess);
        }
    }

    // Remove leftover backups created by the plugin.
    foreach ((array) glob(ABSPATH . '.htaccess.asp-backup-*') as $backup) {
        wp_delete_file($backup);
    }
    if ('' !== $uploads_basedir) {
        foreach ((array) glob(trailingslashit($uploads_basedir) . '.htaccess.asp-backup-*') as $backup) {
            wp_delete_file($backup);
        }
    }
    wp_delete_file(ABSPATH . 'wp-config.php.asp-backup');
}

/**
 * Remove scheduled cron jobs
 */
function asp_remove_cron_jobs()
{
    // Remove salt regeneration cron job
    wp_clear_scheduled_hook('asp_regenerate_salts');

    // Remove any other plugin-specific cron jobs
    $cron_jobs = _get_cron_array();
    if ($cron_jobs) {
        foreach ($cron_jobs as $timestamp => $cron) {
            foreach ($cron as $hook => $dings) {
                if (strpos($hook, 'asp_') === 0) {
                    wp_unschedule_event($timestamp, $hook);
                }
            }
        }
    }
}

/**
 * Flush rewrite rules to clean up custom login URL rules
 */
function asp_flush_rewrite_rules()
{
    flush_rewrite_rules();
}

/**
 * Main uninstall function
 */
function asp_uninstall_plugin()
{
    // Always remove cron jobs and flush rules to cleanup system state
    asp_remove_cron_jobs();
    asp_flush_rewrite_rules();

    // Check if user explicitly opted in to remove data (default: false)
    if (get_option('asp_remove_data_on_uninstall', 0)) {
        asp_cleanup_htaccess();
        asp_remove_all_options();
        asp_remove_transients();
    }
}

// Execute the uninstall
asp_uninstall_plugin();
