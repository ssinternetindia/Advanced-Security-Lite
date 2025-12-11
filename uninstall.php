<?php
/**
 * Advanced Security Lite - Uninstall Script
 * 
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin options from database
 */
function asp_remove_all_options()
{
    $options_to_remove = array(
        // Core Security Settings
        'asp_recaptcha_v2_enabled',
        'asp_recaptcha_v3_enabled',
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

        // Privacy and Obfuscation Settings
        'asp_obfuscate_author_slugs',
        'asp_obfuscate_emails',
        'asp_protect_headers',
        'asp_prevent_user_enumeration',
        'asp_disable_comments',

        // Security Logging and Monitoring
        'asp_enable_security_logging',
        'asp_enable_login_limit',
        'asp_max_login_attempts',
        'asp_lockout_duration',
        'asp_email_notifications',
        'asp_notification_email',

        // IP Whitelist Settings
        'asp_enable_ip_whitelist',
        'asp_ip_whitelist'
    );

    // Remove all plugin options
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }

    // Remove any remaining options that start with 'asp_'
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'asp_%'");
}

/**
 * Remove plugin transients
 */
function asp_remove_transients()
{
    global $wpdb;

    // Remove plugin-specific transients
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_asp_%'");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_asp_%'");
}

/**
 * Remove plugin user meta
 */
function asp_remove_user_meta()
{
    global $wpdb;

    // Remove plugin-specific user meta
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'asp_%'");
}

/**
 * Remove plugin post meta
 */
function asp_remove_post_meta()
{
    global $wpdb;

    // Remove plugin-specific post meta
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'asp_%'");
}

/**
 * Remove custom login page created by plugin
 */
function asp_remove_custom_login_page()
{
    // Find and delete the custom login page
    $login_page = get_page_by_path('secure-login');
    if ($login_page) {
        wp_delete_post($login_page->ID, true);
    }

    // Also remove any pages with the shortcode
    $pages_with_shortcode = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'any',
        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            array(
                'key' => 'post_content',
                'value' => '[asp_custom_login]',
                'compare' => 'LIKE'
            )
        )
    ));

    foreach ($pages_with_shortcode as $page) {
        wp_delete_post($page->ID, true);
    }
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

    // Check if user wants to remove data (default: true)
    if (get_option('asp_remove_data_on_uninstall', 1)) {
        asp_remove_all_options();
        asp_remove_transients();
        asp_remove_user_meta();
        asp_remove_post_meta();
        asp_remove_custom_login_page();

        // Log the uninstall for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // error_log('Advanced Security Lite: Plugin uninstalled and all data removed');
        }
    } else {
        // Log that data was preserved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // error_log('Advanced Security Lite: Plugin uninstalled but data preserved per user settings');
        }
    }
}

// Execute the uninstall
asp_uninstall_plugin();