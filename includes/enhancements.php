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
        // Enhanced login security
        add_action('wp_login_failed', array($this, 'logFailedLogin'));
        add_filter('authenticate', array($this, 'limitLoginAttempts'), 30, 3);

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
    }

    public function logFailedLogin($username)
    {
        $ip = $this->getRealIpAddress();
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

        // Keep only last 10 attempts per IP
        if (count($attempts[$ip]) > 10) {
            $attempts[$ip] = array_slice($attempts[$ip], -10);
        }

        update_option('asp_failed_logins', $attempts);
    }

    public function limitLoginAttempts($user, $username, $password)
    {
        if (empty($username) || empty($password)) {
            return $user;
        }

        $ip = $this->getRealIpAddress();
        $attempts = get_option('asp_failed_logins', array());

        if (isset($attempts[$ip])) {
            $recent_attempts = array_filter($attempts[$ip], function ($attempt) {
                return (current_time('timestamp') - $attempt['time']) < 3600; // Last hour
            });

            if (count($recent_attempts) >= 5) {
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
        // Protect sensitive files
        $this->protectSensitiveFiles();

        // Enhanced upload security
        add_filter('wp_handle_upload_prefilter', array($this, 'enhancedUploadSecurity'));

        // Prevent direct PHP execution in uploads
        $this->preventUploadExecution();
    }

    private function protectSensitiveFiles()
    {
        // Only run in admin context
        if (!is_admin()) {
            return;
        }

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $sensitive_files = array(
            '.htaccess',
            'wp-config.php',
            'wp-config-sample.php',
            'readme.html',
            'license.txt'
        );

        $htaccess_rules = array();
        foreach ($sensitive_files as $file) {
            $htaccess_rules[] = "<Files \"$file\">";
            $htaccess_rules[] = "Order allow,deny";
            $htaccess_rules[] = "Deny from all";
            $htaccess_rules[] = "</Files>";
        }

        $htaccess_path = ABSPATH . '.htaccess';
        if (file_exists($htaccess_path) && wp_is_writable($htaccess_path)) {
            // Create backup before modifying
            $backup_path = $htaccess_path . '.asp-backup-' . time();
            copy($htaccess_path, $backup_path);

            // Use WordPress function to safely insert rules
            $result = insert_with_markers($htaccess_path, 'Advanced Security Lite - Sensitive Files', $htaccess_rules);

            // Remove backup if successful, keep if failed
            if ($result && file_exists($backup_path)) {
                wp_delete_file($backup_path);
            }
        }
    }

    public function enhancedUploadSecurity($file)
    {
        // Only scan image files for malicious code (not PHP, JS, or HTML files)
        if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $scannable_types = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg');

            // Only scan image files that shouldn't contain code
            if (in_array($file_extension, $scannable_types)) {
                $content = file_get_contents($file['tmp_name']);

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

        return $file;
    }

    private function preventUploadExecution()
    {
        $upload_dir = wp_upload_dir();
        $htaccess_path = $upload_dir['basedir'] . '/.htaccess';

        $rules = "# Advanced Security Lite - Prevent PHP Execution\n";
        $rules .= "<Files *.php>\n";
        $rules .= "Order allow,deny\n";
        $rules .= "Deny from all\n";
        $rules .= "</Files>\n";
        $rules .= "<Files *.phtml>\n";
        $rules .= "Order allow,deny\n";
        $rules .= "Deny from all\n";
        $rules .= "</Files>\n";

        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, $rules);
        }
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

    public function enhancedAdminSecurity()
    {
        // Hide admin from non-admins
        if (!current_user_can('manage_options')) {
            add_filter('author_link', array($this, 'hideAdminFromAuthorLink'), 10, 2);
        }

        // Enhanced admin area protection
        $this->protectAdminArea();

        // Disable plugin/theme editor completely
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }

        // Disable plugin/theme installation
        if (get_option('asp_disallow_plugin_upload', 0)) {
            if (!defined('DISALLOW_FILE_MODS')) {
                define('DISALLOW_FILE_MODS', true);
            }
        }
    }

    public function hideAdminFromAuthorLink($link, $author_id)
    {
        $user = get_userdata($author_id);
        if ($user && in_array('administrator', $user->roles)) {
            return home_url();
        }
        return $link;
    }

    public function protectAdminArea()
    {
        // Log admin access attempts
        if (is_admin() && !wp_doing_ajax()) {
            $this->logAdminAccess();
        }

        // Enhanced session security
        if (!headers_sent()) {
            if (!headers_sent()) {
                @ini_set('session.cookie_httponly', 1); // phpcs:ignore
                @ini_set('session.cookie_secure', is_ssl() ? 1 : 0); // phpcs:ignore
                @ini_set('session.use_only_cookies', 1); // phpcs:ignore
            }
        }
    }

    private function logAdminAccess()
    {
        $user = wp_get_current_user();
        if ($user->ID > 0) {
            $access_log = get_option('asp_admin_access_log', array());
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

            $access_log[] = array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'ip' => $this->getRealIpAddress(),
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

        // Content Security Policy
        if (get_option('asp_protect_headers', 0)) {
            add_action('wp_head', array($this, 'addContentSecurityPolicy'));
        }

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

    public function addContentSecurityPolicy()
    {
        echo "<meta http-equiv=\"Content-Security-Policy\" content=\"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;\">\n";
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
        // Schedule database optimization (weekly, not on every page load)
        if (!wp_next_scheduled('asp_optimize_database')) {
            wp_schedule_event(time(), 'weekly', 'asp_optimize_database');
        }
        add_action('asp_optimize_database', array($this, 'optimizeDatabase'));

        // Clean up expired transients
        add_action('wp_scheduled_delete', array($this, 'cleanupExpiredTransients'));

        // Optimize autoloaded options (only in admin, once per day)
        if (is_admin() && !get_transient('asp_autoload_optimized')) {
            add_action('admin_init', array($this, 'optimizeAutoloadedOptions'), 999);
            set_transient('asp_autoload_optimized', true, DAY_IN_SECONDS);
        }
    }

    public function optimizeDatabase()
    {
        // Remove spam comments older than 30 days
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");

        // Remove expired transients
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%' AND option_name NOT IN (SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%')");
    }

    public function cleanupExpiredTransients()
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
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

        foreach ($large_options as $option) {
            // Set large options to not autoload
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update(
                $wpdb->options,
                array('autoload' => 'no'),
                array('option_name' => $option->option_name)
            );
        }
    }

    private function getRealIpAddress()
    {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return sanitize_text_field(wp_unslash($ip));
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';
    }
}