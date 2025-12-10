<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_SecurityFeatures
{

    public function __construct()
    {
        // Initialize hooks immediately but check for WordPress readiness
        $this->initHooks();
    }

    public function initHooks()
    {
        // Ensure WordPress is ready - if not, just return and let WordPress load it
        if (!function_exists('get_option')) {
            return;
        }
        // General Security Features
        if (get_option('asp_disable_wp_json', 0)) {
            add_action('init', array($this, 'disableWpJson'));
        }

        if (get_option('asp_disable_feeds', 0)) {
            add_action('init', array($this, 'disableFeeds'));
        }

        if (get_option('asp_disable_rest_api', 0)) {
            add_filter('rest_authentication_errors', array($this, 'disableRestApi'));
        }

        if (get_option('asp_disable_registration', 0)) {
            add_filter('option_users_can_register', '__return_false');
        }

        if (get_option('asp_disable_password_recovery', 0)) {
            add_filter('allow_password_reset', '__return_false');
        }

        if (get_option('asp_auto_regenerate_salts', 0)) {
            add_action('wp_loaded', array($this, 'scheduleAutoSaltRegeneration'));
        }

        if (get_option('asp_disable_xmlrpc', 0)) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', array($this, 'removeXmlrpcHeader'));
        }

        if (get_option('asp_disallow_bad_requests', 0)) {
            add_action('init', array($this, 'blockBadRequests'));
        }

        if (get_option('asp_disallow_dir_listing', 0)) {
            add_action('init', array($this, 'disableDirectoryListing'));
        }

        if (get_option('asp_disallow_malicious_uploads', 0)) {
            add_filter('upload_mimes', array($this, 'restrictUploadMimes'));
            add_filter('wp_handle_upload_prefilter', array($this, 'scanUploadedFiles'));
        }

        if (get_option('asp_disallow_plugin_upload', 0)) {
            add_filter('map_meta_cap', array($this, 'disablePluginUpload'), 10, 2);
        }

        if (get_option('asp_disallow_theme_upload', 0)) {
            add_filter('map_meta_cap', array($this, 'disableThemeUpload'), 10, 2);
        }

        if (get_option('asp_disallow_file_edit', 0)) {
            add_action('init', array($this, 'disableFileEdit'));
        }

        if (get_option('asp_hide_login_errors', 0)) {
            add_filter('login_errors', array($this, 'hideLoginErrors'));
        }

        if (get_option('asp_hide_php_version', 0)) {
            add_action('init', array($this, 'hidePhpVersion'));
        }

        if (get_option('asp_hide_wp_version', 0)) {
            add_filter('the_generator', '__return_empty_string');
            add_action('wp_head', array($this, 'removeWpVersion'), 1);
        }

        // Custom login URL is handled by ASP_LoginCustomizer class

        if (get_option('asp_obfuscate_author_slugs', 0)) {
            add_filter('author_link', array($this, 'obfuscateAuthorSlugs'), 10, 2);
        }

        if (get_option('asp_obfuscate_emails', 0)) {
            add_filter('the_content', array($this, 'obfuscateEmails'));
            add_filter('widget_text', array($this, 'obfuscateEmails'));
        }

        if (get_option('asp_protect_headers', 0)) {
            add_action('send_headers', array($this, 'addSecurityHeaders'));
        }

        if (get_option('asp_prevent_user_enumeration', 0)) {
            add_action('init', array($this, 'preventUserEnumeration'));
        }

        if (get_option('asp_disable_comments', 0)) {
            add_action('init', array($this, 'disableComments'));
        }

        // New features
        if (get_option('asp_disable_app_passwords', 0)) {
            add_filter('wp_is_application_passwords_available', '__return_false');
        }

        if (get_option('asp_disable_pingbacks', 0)) {
            add_action('init', array($this, 'disablePingbacks'));
        }

        if (get_option('asp_hide_admin_username', 0)) {
            add_filter('author_link', array($this, 'hideAdminAuthorLink'), 10, 2);
            add_filter('the_author', array($this, 'hideAdminDisplayName'));
            add_filter('the_author_posts_link', array($this, 'hideAdminPostsLink'));
        }

        if (get_option('asp_maintenance_mode', 0)) {
            add_action('template_redirect', array($this, 'enableMaintenanceMode'));
        }

        if (get_option('asp_limit_revisions', 0)) {
            add_filter('wp_revisions_to_keep', array($this, 'limitPostRevisions'), 10, 2);
        }
    }

    public function disableWpJson()
    {
        // Remove JSON API links from header
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // Disable JSON API
        add_filter('json_enabled', '__return_false');
        add_filter('json_jsonp_enabled', '__return_false');
    }

    public function disableFeeds()
    {
        add_action('do_feed', array($this, 'disableFeedAction'), 1);
        add_action('do_feed_rdf', array($this, 'disableFeedAction'), 1);
        add_action('do_feed_rss', array($this, 'disableFeedAction'), 1);
        add_action('do_feed_rss2', array($this, 'disableFeedAction'), 1);
        add_action('do_feed_atom', array($this, 'disableFeedAction'), 1);

        // Remove feed links from header
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
    }

    public function disableFeedAction()
    {
        wp_die(__('Feeds are disabled on this site.', 'advanced-security-lite'));
    }

    public function disableRestApi($access)
    {
        // Allow REST API for logged-in users
        if (is_user_logged_in()) {
            return $access;
        }

        // Allow WooCommerce and other essential endpoints
        $allowed_endpoints = array(
            '/wc/',
            '/wp/v2/media',
            '/contact-form-7/',
            '/elementor/',
            '/yoast/'
        );

        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        foreach ($allowed_endpoints as $endpoint) {
            if (strpos($request_uri, $endpoint) !== false) {
                return $access;
            }
        }

        // Block other REST API access for non-authenticated users
        return new WP_Error('rest_disabled', __('REST API is disabled for non-authenticated users.', 'advanced-security-lite'), array('status' => 401));
    }

    public function scheduleAutoSaltRegeneration()
    {
        // Always add the action hook first
        add_action('asp_regenerate_salts', array($this, 'regenerateSalts'));

        // Clear existing schedule first
        wp_clear_scheduled_hook('asp_regenerate_salts');

        // Only schedule if auto regeneration is enabled
        if (get_option('asp_auto_regenerate_salts', 0)) {
            $frequency = get_option('asp_salt_regeneration_frequency', 'monthly');

            // Validate frequency exists in cron schedules
            $schedules = wp_get_schedules();
            if (!isset($schedules[$frequency])) {
                $frequency = 'monthly'; // Fallback to monthly if invalid
                update_option('asp_salt_regeneration_frequency', $frequency);
            }

            // Schedule new event with selected frequency
            $scheduled = wp_schedule_event(time() + 3600, $frequency, 'asp_regenerate_salts');

            // Log scheduling result for debugging
            if (!$scheduled) {
                error_log('Advanced Security Lite: Failed to schedule salt regeneration with frequency: ' . $frequency);
            }
        }
    }

    public function regenerateSalts()
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
        if (file_exists($wp_config_path) && is_writable($wp_config_path)) {
            $config_content = file_get_contents($wp_config_path);

            foreach ($salts as $salt) {
                $new_salt = wp_generate_password(64, true, true);
                $pattern = "/define\s*\(\s*['\"]" . $salt . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)\s*;/";
                $replacement = "define('" . $salt . "', '" . $new_salt . "');";
                $config_content = preg_replace($pattern, $replacement, $config_content);
            }

            file_put_contents($wp_config_path, $config_content);
        }
    }

    public function removeXmlrpcHeader($headers)
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function blockBadRequests()
    {
        // Skip blocking for admin area and AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        // More conservative bad patterns to avoid false positives
        $bad_patterns = array(
            '/\.\.\//i',
            '/union\s+select/i',
            '/<script[^>]*>/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i'
        );

        foreach ($bad_patterns as $pattern) {
            if (preg_match($pattern, $request_uri) || preg_match($pattern, $query_string)) {
                status_header(403);
                wp_die(__('Forbidden request detected.', 'advanced-security-lite'));
            }
        }
    }

    public function disableDirectoryListing()
    {
        $htaccess_path = ABSPATH . '.htaccess';
        $rules = array('Options -Indexes');

        if (file_exists($htaccess_path) && is_writable($htaccess_path)) {
            // Create backup before modifying
            $backup_path = $htaccess_path . '.asp-backup-' . time();
            copy($htaccess_path, $backup_path);

            // Use WordPress function to safely insert rules
            $result = insert_with_markers($htaccess_path, 'Advanced Security Lite - Directory Listing', $rules);

            // Remove backup if successful, keep if failed
            if ($result && file_exists($backup_path)) {
                @unlink($backup_path);
            }
        }
    }

    public function restrictUploadMimes($mimes)
    {
        // Remove potentially dangerous file types
        unset($mimes['exe']);
        unset($mimes['bat']);
        unset($mimes['cmd']);
        unset($mimes['com']);
        unset($mimes['pif']);
        unset($mimes['scr']);
        unset($mimes['vbs']);
        unset($mimes['js']);

        return $mimes;
    }

    public function scanUploadedFiles($file)
    {
        $filename = $file['name'];
        $filetype = wp_check_filetype($filename);

        // Block files with double extensions
        if (substr_count($filename, '.') > 1) {
            $file['error'] = __('Files with multiple extensions are not allowed.', 'advanced-security-lite');
        }

        // Block executable files
        $dangerous_extensions = array('exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar');
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($file_extension), $dangerous_extensions)) {
            $file['error'] = __('This file type is not allowed for security reasons.', 'advanced-security-lite');
        }

        return $file;
    }

    public function disablePluginUpload($caps, $cap)
    {
        if ($cap === 'install_plugins' || $cap === 'upload_plugins') {
            $caps[] = 'do_not_allow';
        }
        return $caps;
    }

    public function disableThemeUpload($caps, $cap)
    {
        if ($cap === 'install_themes' || $cap === 'upload_themes') {
            $caps[] = 'do_not_allow';
        }
        return $caps;
    }

    public function disableFileEdit()
    {
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }

    public function hideLoginErrors()
    {
        return __('Invalid login credentials.', 'advanced-security-lite');
    }

    public function hidePhpVersion()
    {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        ini_set('expose_php', 'off');
    }

    public function removeWpVersion()
    {
        remove_action('wp_head', 'wp_generator');
    }


    public function obfuscateAuthorSlugs($link, $author_id)
    {
        $obfuscated_slug = 'author-' . md5($author_id . get_option('asp_obfuscate_salt', wp_generate_password(32)));
        return str_replace('/author/', '/author-obf/', $link) . $obfuscated_slug;
    }

    public function obfuscateEmails($content)
    {
        $pattern = '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/';
        return preg_replace_callback($pattern, array($this, 'encodeEmail'), $content);
    }

    private function encodeEmail($matches)
    {
        $email = $matches[1];
        $encoded = '';
        for ($i = 0; $i < strlen($email); $i++) {
            $encoded .= '&#' . ord($email[$i]) . ';';
        }
        return $encoded;
    }

    public function addSecurityHeaders()
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    public function preventUserEnumeration()
    {
        // Block user enumeration via REST API
        add_filter('rest_endpoints', array($this, 'disableUsersEndpoint'));

        // Block user enumeration via author pages
        add_action('template_redirect', array($this, 'blockAuthorPages'));

        // Block user enumeration via login attempts
        add_filter('authenticate', array($this, 'blockUserEnumLogin'), 30, 3);
    }

    public function disableUsersEndpoint($endpoints)
    {
        if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
        }
        if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    public function blockAuthorPages()
    {
        if (is_author()) {
            wp_redirect(home_url(), 301);
            exit;
        }
    }

    public function blockUserEnumLogin($user, $username, $password)
    {
        if (empty($username) || empty($password)) {
            return new WP_Error('empty_credentials', __('Invalid login credentials.', 'advanced-security-lite'));
        }
        return $user;
    }

    public function disableComments()
    {
        // Close comments on the front-end
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10, 2);

        // Remove comments page in menu
        add_action('admin_menu', array($this, 'removeCommentsPage'));

        // Remove comments links from admin bar
        add_action('init', array($this, 'removeCommentsAdminBar'));

        // Remove comments metaboxes from dashboard
        add_action('admin_init', array($this, 'removeCommentsMetaboxes'));

        // Disable support for comments and trackbacks in post types
        add_action('admin_init', array($this, 'disableCommentsPostTypes'));
    }

    public function removeCommentsPage()
    {
        remove_menu_page('edit-comments.php');
    }

    public function removeCommentsAdminBar()
    {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    public function removeCommentsMetaboxes()
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    public function disableCommentsPostTypes()
    {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    // Feature #12: Disable Pingbacks & Trackbacks
    public function disablePingbacks()
    {
        // Disable pingback XMLRPC method
        add_filter('xmlrpc_methods', function ($methods) {
            unset($methods['pingback.ping']);
            unset($methods['pingback.extensions.getPingbacks']);
            return $methods;
        });

        // Disable self-pingbacks
        add_action('pre_ping', function (&$links) {
            $home = get_option('home');
            foreach ($links as $l => $link) {
                if (strpos($link, $home) === 0) {
                    unset($links[$l]);
                }
            }
        });

        // Disable X-Pingback header
        add_filter('wp_headers', function ($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });

        // Close pingbacks on existing posts
        add_filter('pings_open', '__return_false', 20, 2);
    }

    // Feature #13: Hide Admin Username from Author Archives
    public function hideAdminAuthorLink($link, $author_id)
    {
        $user = get_userdata($author_id);
        if ($user && in_array('administrator', (array) $user->roles)) {
            return home_url();
        }
        return $link;
    }

    public function hideAdminDisplayName($display_name)
    {
        global $authordata;
        if (isset($authordata) && is_object($authordata)) {
            $user = get_userdata($authordata->ID);
            if ($user && in_array('administrator', (array) $user->roles)) {
                return __('Author', 'advanced-security-lite');
            }
        }
        return $display_name;
    }

    public function hideAdminPostsLink($link)
    {
        global $authordata;
        if (isset($authordata) && is_object($authordata)) {
            $user = get_userdata($authordata->ID);
            if ($user && in_array('administrator', (array) $user->roles)) {
                return '<span class="author vcard">' . __('Author', 'advanced-security-lite') . '</span>';
            }
        }
        return $link;
    }

    // Feature #14: Maintenance Mode
    public function enableMaintenanceMode()
    {
        // Allow logged-in administrators to access the site
        if (current_user_can('manage_options')) {
            return;
        }

        // Allow access to login page
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
            return;
        }

        // Allow access to admin-ajax.php
        if (strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false) {
            return;
        }

        // Get custom message
        $message = get_option('asp_maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.');

        // Set maintenance header
        status_header(503);
        header('Retry-After: 3600');

        // Display maintenance page
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html(get_bloginfo('name')); ?> - <?php _e('Maintenance', 'advanced-security-lite'); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .maintenance-container {
                    background: rgba(255, 255, 255, 0.95);
                    border-radius: 20px;
                    padding: 60px 40px;
                    text-align: center;
                    max-width: 500px;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
                }

                .maintenance-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                }

                h1 {
                    color: #1a1a2e;
                    font-size: 28px;
                    margin-bottom: 15px;
                }

                p {
                    color: #666;
                    font-size: 16px;
                    line-height: 1.6;
                }
            </style>
        </head>

        <body>
            <div class="maintenance-container">
                <div class="maintenance-icon">🔧</div>
                <h1><?php _e('Under Maintenance', 'advanced-security-lite'); ?></h1>
                <p><?php echo esc_html($message); ?></p>
            </div>
        </body>

        </html>
        <?php
        exit;
    }

    // Feature #15: Limit Post Revisions
    public function limitPostRevisions($num, $post)
    {
        $limit = (int) get_option('asp_revisions_limit', 5);
        return $limit;
    }
}