<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_SecurityFeatures
{

    /**
     * True when the current author archive was resolved from a valid
     * obfuscated hash (see resolveObfuscatedAuthor). Lets legitimate
     * obfuscated author pages through even when user-enumeration
     * protection would otherwise redirect every author archive.
     *
     * @var bool
     */
    private $obfuscated_resolved = false;

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
            add_filter('wp_login_errors', array($this, 'hideLoginErrors'), 10, 2);
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
            add_action('pre_get_posts', array($this, 'resolveObfuscatedAuthor'));
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

        // File Protection Features
        if (get_option('asp_disable_php_execution', 0)) {
            add_action('admin_init', array($this, 'disablePhpExecution'));
        } else {
            // Remove the block if the option was just turned off.
            add_action('admin_init', array($this, 'removePhpExecutionBlock'));
        }

        if (get_option('asp_protect_sensitive_files', 0)) {
            add_action('admin_init', array($this, 'protectSensitiveFiles'));
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
        wp_die(esc_html__('Feeds are disabled on this site.', 'advanced-security-lite'));
    }

    public function disableRestApi($access)
    {
        // Allow REST API for logged-in users
        if (is_user_logged_in()) {
            return $access;
        }

        // Match only the URL path (not query string) to prevent
        // bypasses like /wp-json/wp/v2/users?x=/wc/.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- raw value used only for path extraction
        $raw_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = (string) parse_url($raw_uri, PHP_URL_PATH);
        $path = rawurldecode($path);
        $path = '/' . trim($path, '/');

        // Allowed route prefixes (all start from /wp-json/).
        $allowed_prefixes = array(
            '/wp-json/wc',
            '/wp-json/wp/v2/media',
            '/wp-json/contact-form-7',
            '/wp-json/elementor',
            '/wp-json/yoast',
        );

        foreach ($allowed_prefixes as $prefix) {
            // Require a segment boundary so /wp-json/wc-anything or
            // /wp-json/wp/v2/mediaXYZ cannot slip through as a sibling.
            if ($path === $prefix || 0 === strpos($path, $prefix . '/')) {
                return $access;
            }
        }

        // Block other REST API access for non-authenticated users
        return new WP_Error('rest_disabled', __('REST API is disabled for non-authenticated users.', 'advanced-security-lite'), array('status' => 401));
    }

    public function scheduleAutoSaltRegeneration()
    {
        // Always register the action handler.
        add_action('asp_regenerate_salts', array($this, 'regenerateSalts'));

        if (!get_option('asp_auto_regenerate_salts', 0)) {
            // Feature disabled: remove any leftover schedule.
            wp_clear_scheduled_hook('asp_regenerate_salts');
            return;
        }

        // Only schedule if nothing is already queued (avoids the
        // clear-then-reschedule-on-every-request bug that prevented
        // the cron from ever firing on live-traffic sites).
        if (wp_next_scheduled('asp_regenerate_salts')) {
            return;
        }

        $frequency = get_option('asp_salt_regeneration_frequency', 'monthly');
        $valid_freqs = array('daily', 'weekly', 'monthly');
        if (!in_array($frequency, $valid_freqs, true)) {
            $frequency = 'monthly';
            update_option('asp_salt_regeneration_frequency', $frequency);
        }

        wp_schedule_event(time() + 3600, $frequency, 'asp_regenerate_salts');
    }

    public function regenerateSalts()
    {
        // Shared implementation lives in the main plugin file: atomic write,
        // backup, replacement verification and global session destruction.
        if (function_exists('asp_regenerate_wp_config_salts')) {
            asp_regenerate_wp_config_salts();
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

        // Use raw input for pattern matching so encoding tricks (percent-
        // encoding, double-encoding) cannot evade the rules.  Nothing here
        // is ever echoed, so sanitisation is unnecessary.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $raw_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $raw_qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        // Decode one and two levels so tricks like %252e%252e%252f
        // (double-encoded ../) are still caught.  The decoded variants are
        // collected into a separate array — appending to the array being
        // iterated would make the loop consume each new element forever and
        // exhaust PHP's memory limit.
        $candidates = array();
        foreach (array($raw_uri, $raw_qs) as $val) {
            $candidates[] = $val;
            $candidates[] = rawurldecode($val);
            $candidates[] = rawurldecode(rawurldecode($val));
        }

        $bad_patterns = array(
            '/\.\.\//i',
            '/union\s+select/i',
            '/<script[^>]*>/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i'
        );

        foreach ($candidates as $candidate) {
            foreach ($bad_patterns as $pattern) {
                if (preg_match($pattern, $candidate)) {
                    status_header(403);
                    wp_die(esc_html__('Forbidden request detected.', 'advanced-security-lite'));
                }
            }
        }
    }

    public function disableDirectoryListing()
    {
        // Only administrators may trigger filesystem writes.
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only run in admin context and at most once per day to avoid
        // rewriting .htaccess on every admin page load.
        if (!is_admin() || get_transient('asp_dir_listing_done')) {
            return;
        }

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $htaccess_path = ABSPATH . '.htaccess';
        $rules = array('Options -Indexes');

        if (file_exists($htaccess_path) && wp_is_writable($htaccess_path)) {
            // Create backup before modifying
            $backup_path = $htaccess_path . '.asp-backup-' . time();
            copy($htaccess_path, $backup_path);

            // Use WordPress function to safely insert rules
            $result = insert_with_markers($htaccess_path, 'Advanced Security Lite - Directory Listing', $rules);

            // Remove the backup (and any stale ones from failed runs) on success.
            if ($result) {
                foreach ((array) glob($htaccess_path . '.asp-backup-*') as $stale_backup) {
                    wp_delete_file($stale_backup);
                }
            }
        }

        set_transient('asp_dir_listing_done', true, DAY_IN_SECONDS);
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

        // Block executable files (checks the final extension only, so
        // legitimate names like "my.photo.jpg" are still accepted).
        $dangerous_extensions = array('exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php');
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_extension, $dangerous_extensions, true)) {
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

    /**
     * Mask enumeration-sensitive credential errors only.
     *
     * Previously this replaced EVERY login error with a generic message,
     * which hid real problems (CAPTCHA failures, rate-limit lockouts, cookie
     * errors) behind "Invalid login credentials." and made correct logins
     * appear broken. Now only the username/password guessing vectors are
     * masked; functional errors stay visible so users know what to fix.
     *
     * @param WP_Error $errors      Login errors.
     * @param string   $redirect_to Redirect target (unused).
     * @return WP_Error
     */
    public function hideLoginErrors($errors, $redirect_to = '')
    {
        unset($redirect_to);

        if (!is_wp_error($errors) || !$errors->get_error_codes()) {
            return $errors;
        }

        $masked = array(
            'invalid_username',
            'incorrect_password',
            'invalid_email',
            'empty_username',
            'empty_password'
        );

        $masked_any = false;
        foreach ($errors->get_error_codes() as $code) {
            if (in_array($code, $masked, true)) {
                $errors->remove($code);
                $masked_any = true;
            }
        }

        if ($masked_any && !$errors->get_error_codes()) {
            $errors->add(
                'asp_invalid_credentials',
                __('Invalid username or password.', 'advanced-security-lite')
            );
        }

        return $errors;
    }

    public function hidePhpVersion()
    {
        if (function_exists('header_remove') && !headers_sent()) {
            header_remove('X-Powered-By');
        }
        // Note: expose_php is PHP_INI_SYSTEM and cannot be changed at runtime;
        // it must be set in php.ini by the host.
    }

    public function removeWpVersion()
    {
        remove_action('wp_head', 'wp_generator');
    }


    /**
     * Stable per-user hash used as the public author slug.
     * The salt is generated once and persisted so URLs never change.
     */
    private function getAuthorHash($author_id)
    {
        $salt = get_option('asp_obfuscate_salt');
        if (!$salt) {
            $salt = wp_generate_password(32, false, false);
            update_option('asp_obfuscate_salt', $salt);
        }
        return md5($author_id . '|' . $salt);
    }

    /**
     * Replace the username segment of author archive links with the
     * stable hash (keeps the /author/ base so core rewrite rules work).
     */
    public function obfuscateAuthorSlugs($link, $author_id)
    {
        $parts = explode('/', untrailingslashit($link));
        if (count($parts) < 2) {
            return $link;
        }
        $parts[count($parts) - 1] = $this->getAuthorHash($author_id);
        return implode('/', $parts) . '/';
    }

    /**
     * Map incoming /author/<hash>/ requests back to the correct user.
     */
    public function resolveObfuscatedAuthor($query)
    {
        if (is_admin() || !$query->is_author()) {
            return;
        }

        $hash = $query->get('author_name');
        if (!$hash || !preg_match('/^[0-9a-f]{32}$/', $hash)) {
            return;
        }

        $users = get_users(array('fields' => 'ID', 'number' => 1000));
        foreach ($users as $user_id) {
            if ($this->getAuthorHash($user_id) === $hash) {
                $query->set('author', (int) $user_id);
                $query->set('author_name', '');
                $this->obfuscated_resolved = true;
                break;
            }
        }
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

        // Block author query vars early (before core's canonical redirect
        // leaks the username in the Location header).
        add_action('parse_query', array($this, 'blockAuthorQueryVars'));

        // Defense in depth: also block at template_redirect.
        add_action('template_redirect', array($this, 'blockAuthorPages'));

        // Block user enumeration via login attempts
        add_filter('authenticate', array($this, 'blockUserEnumLogin'), 30, 3);
    }

    /**
     * Intercept author/author_name query vars before canonical redirect.
     *
     * @param WP_Query $query
     */
    public function blockAuthorQueryVars($query)
    {
        if (is_admin()) {
            return;
        }
        // Only intervene for front-end author archive queries.
        if (!$query->is_author()) {
            return;
        }
        $author_name = $query->get('author_name');
        $author_id = $query->get('author');

        // A resolved obfuscated slug is a legitimate author archive, not
        // an enumeration attempt - let it through.
        if ($this->obfuscated_resolved) {
            return;
        }

        // Numeric author ID or non-hash author_name = enumeration attempt.
        if (is_numeric($author_name) || (is_numeric($author_id) && $author_id > 0)) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
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
        // Obfuscated author archives resolved from a valid hash are
        // legitimate; every other author page is an enumeration target.
        if (is_author() && !$this->obfuscated_resolved) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    public function blockUserEnumLogin($user, $username, $password)
    {
        // wp-login.php performs an empty-credential wp_signon() on every
        // page load. That is not a login attempt — replacing its error here
        // would print "Invalid login credentials." on the bare login form.
        // Core's empty_username/empty_password errors reveal nothing, so
        // they are left untouched.
        if (empty($username) || empty($password)) {
            return $user;
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
            if (!$home || !is_string($home)) {
                return;
            }
            foreach ($links as $l => $link) {
                if (is_string($link) && stripos($link, $home) === 0) {
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
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), 'wp-login.php') !== false) {
            return;
        }

        // Allow access to admin-ajax.php
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), 'admin-ajax.php') !== false) {
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
            <title><?php echo esc_html(get_bloginfo('name')); ?> - <?php esc_html_e('Maintenance', 'advanced-security-lite'); ?>
            </title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    background: #ffffff;
                    color: #1d2327;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                }

                .maintenance-box {
                    max-width: 460px;
                    width: 100%;
                    text-align: center;
                }

                .maintenance-icon {
                    width: 64px;
                    height: 64px;
                    margin: 0 auto 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid #dcdcde;
                    border-radius: 50%;
                }

                .maintenance-icon svg {
                    width: 28px;
                    height: 28px;
                    stroke: #2271b1;
                    fill: none;
                    stroke-width: 1.8;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                }

                .maintenance-site {
                    font-size: 12px;
                    font-weight: 600;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: #8c8f94;
                    margin-bottom: 10px;
                }

                h1 {
                    color: #1d2327;
                    font-size: 24px;
                    font-weight: 600;
                    margin-bottom: 12px;
                }

                p {
                    color: #646970;
                    font-size: 15px;
                    line-height: 1.65;
                }
            </style>
        </head>

        <body>
            <div class="maintenance-box">
                <div class="maintenance-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                    </svg>
                </div>
                <div class="maintenance-site"><?php echo esc_html(get_bloginfo('name')); ?></div>
                <h1><?php esc_html_e('Brief maintenance', 'advanced-security-lite'); ?></h1>
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

    // Feature #16: Disable PHP Execution in uploads folder
    public function disablePhpExecution()
    {
        // Only administrators may trigger filesystem writes.
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only run once per day to avoid rewriting on every admin load.
        $run_key = 'asp_php_execution_disabled';
        if (get_transient($run_key)) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $htaccess_path = trailingslashit($upload_dir['basedir']) . '.htaccess';

        $rules = array(
            '# Advanced Security Lite - Disable PHP Execution',
            '<FilesMatch "\.(php[0-9]?|phtml|pht|phar)$">',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</FilesMatch>',
        );

        // Use insert_with_markers so the block is cleanly reversible.
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        if (file_exists($htaccess_path) || wp_is_writable(dirname($htaccess_path))) {
            // Create backup before modifying.
            $backup_path = $htaccess_path . '.asp-backup-' . time();
            if (file_exists($htaccess_path)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
                $existing = file_get_contents($htaccess_path);
                if (false !== $existing && strpos($existing, 'Advanced Security Lite - Disable PHP Execution') === false) {
                    // Pre-existing content we didn't write — back it up.
                    @copy($htaccess_path, $backup_path);
                }
            }

            $result = insert_with_markers($htaccess_path, 'Advanced Security Lite - Disable PHP Execution', $rules);

            // Remove the backup (and any stale ones from failed runs) on success.
            if ($result) {
                foreach ((array) glob($htaccess_path . '.asp-backup-*') as $stale_backup) {
                    wp_delete_file($stale_backup);
                }
            }
        }

        set_transient($run_key, true, DAY_IN_SECONDS);
    }

    /**
     * Remove the PHP execution block from uploads/.htaccess when the
     * option is turned off (called from the settings save handler).
     */
    public function removePhpExecutionBlock()
    {
        // Only administrators may trigger filesystem writes.
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $upload_dir = wp_upload_dir();
        $htaccess_path = trailingslashit($upload_dir['basedir']) . '.htaccess';

        if (file_exists($htaccess_path) && wp_is_writable($htaccess_path)) {
            insert_with_markers($htaccess_path, 'Advanced Security Lite - Disable PHP Execution', array());
        }

        delete_transient('asp_php_execution_disabled');
    }

    // Feature #17: Protect sensitive files (wp-config.php, .htaccess, etc.)
    public function protectSensitiveFiles()
    {
        // Only administrators may trigger filesystem writes.
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only run once per day
        $run_key = 'asp_sensitive_files_protected';
        if (get_transient($run_key)) {
            return;
        }

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $htaccess_path = ABSPATH . '.htaccess';

        if (!file_exists($htaccess_path) || !wp_is_writable($htaccess_path)) {
            return;
        }

        $rules = array(
            '# Protect wp-config.php and any backup variants',
            '<FilesMatch "wp-config\\.php(\\..*)?$">',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</FilesMatch>',
            '',
            '# Protect .htaccess',
            '<Files .htaccess>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
            '',
            '# Protect wp-config-sample.php',
            '<Files wp-config-sample.php>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
            '',
            '# Protect readme.html and license.txt (version disclosure)',
            '<Files readme.html>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
            '<Files license.txt>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
            '',
            '# Block access to debugging logs',
            '<Files debug.log>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
            '',
            '# Protect error_log files',
            '<Files error_log>',
            '    <IfModule mod_authz_core.c>',
            '        Require all denied',
            '    </IfModule>',
            '    <IfModule !mod_authz_core.c>',
            '        Order allow,deny',
            '        Deny from all',
            '    </IfModule>',
            '</Files>',
        );

        // Create a backup before modifying
        $backup_path = $htaccess_path . '.asp-backup-' . time();
        copy($htaccess_path, $backup_path);

        $result = insert_with_markers($htaccess_path, 'Advanced Security Lite - Sensitive Files Protection', $rules);

        // Remove the backup (and any stale ones from failed runs) on success.
        if ($result) {
            foreach ((array) glob($htaccess_path . '.asp-backup-*') as $stale_backup) {
                wp_delete_file($stale_backup);
            }
        }

        set_transient($run_key, true, DAY_IN_SECONDS);
    }
}