<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="asl-wrap">
    <!-- Top Navigation Bar -->
    <header class="asl-header">
        <div class="asl-header-left">
            <div class="asl-brand">
                <span class="asl-brand-icon"><i class="ph ph-shield-check"></i></span>
                <div class="asl-brand-text">
                    <h1><?php echo esc_html(ASP_NAME); ?></h1>
                    <span class="asl-version">v<?php echo ASP_VERSION; ?></span>
                </div>
            </div>
        </div>
        <div class="asl-header-right">
            <button type="button" id="regenerate-salts-btn" class="asl-btn asl-btn-primary">
                <i class="ph ph-key"></i> <?php _e('Regenerate Salts', 'advanced-security-lite'); ?>
            </button>
            <?php
            // Calculate security score
            $enabled_features = count(array_filter([
                get_option('asp_disable_wp_json', 0),
                get_option('asp_disable_xmlrpc', 0),
                get_option('asp_hide_wp_version', 0),
                get_option('asp_disallow_file_edit', 0),
                get_option('asp_protect_headers', 0),
                get_option('asp_auto_regenerate_salts', 0),
                get_option('asp_recaptcha_v2_enabled', 0) || get_option('asp_recaptcha_v3_enabled', 0),
                get_option('asp_disallow_bad_requests', 0),
                get_option('asp_prevent_user_enumeration', 0),
                get_option('asp_hide_login_errors', 0)
            ]));

            if ($enabled_features >= 5) {
                $status_class = 'asl-status-protected';
                $status_text = __('Protected', 'advanced-security-lite');
                $status_icon = '<i class="ph ph-check"></i>';
            } elseif ($enabled_features >= 2) {
                $status_class = 'asl-status-partial';
                $status_text = __('Partial', 'advanced-security-lite');
                $status_icon = '<i class="ph ph-warning"></i>';
            } else {
                $status_class = 'asl-status-unprotected';
                $status_text = __('Unprotected', 'advanced-security-lite');
                $status_icon = '<i class="ph ph-x"></i>';
            }
            ?>
            <span class="asl-status-badge <?php echo $status_class; ?>">
                <span class="asl-status-dot"><?php echo $status_icon; ?></span>
                <?php echo $status_text; ?>
            </span>
        </div>
    </header>

    <!-- Horizontal Tab Navigation -->
    <nav class="asl-tabs">
        <button class="asl-tab active" data-tab="dashboard">
            <span class="asl-tab-icon"><i class="ph ph-chart-bar"></i></span>
            <?php _e('Dashboard', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="general">
            <span class="asl-tab-icon"><i class="ph ph-gear"></i></span>
            <?php _e('General', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="authentication">
            <span class="asl-tab-icon"><i class="ph ph-lock-key"></i></span>
            <?php _e('Authentication', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="firewall">
            <span class="asl-tab-icon"><i class="ph ph-wall"></i></span>
            <?php _e('Firewall', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="hardening">
            <span class="asl-tab-icon"><i class="ph ph-shield"></i></span>
            <?php _e('Hardening', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="tools">
            <span class="asl-tab-icon"><i class="ph ph-wrench"></i></span>
            <?php _e('Tools', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="activity">
            <span class="asl-tab-icon"><i class="ph ph-list-dashes"></i></span>
            <?php _e('Activity Log', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="settings">
            <span class="asl-tab-icon"><i class="ph ph-sliders"></i></span>
            <?php _e('Settings', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="about">
            <span class="asl-tab-icon"><i class="ph ph-info"></i></span>
            <?php _e('About', 'advanced-security-lite'); ?>
        </button>
    </nav>

    <!-- Main Content Area -->
    <main class="asl-main">
        <form id="asl-settings-form" method="post">
            <?php wp_nonce_field('asp_settings', 'asp_nonce'); ?>

            <!-- Dashboard Panel -->
            <div id="panel-dashboard" class="asl-panel active">
                <!-- Stats Grid -->
                <div class="asl-stats-grid">
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-primary"><i class="ph ph-shield-check"></i></div>
                        <div class="asl-stat-info">
                            <span class="asl-stat-number"><?php
                            echo count(array_filter([
                                get_option('asp_disable_wp_json', 0),
                                get_option('asp_disable_xmlrpc', 0),
                                get_option('asp_hide_wp_version', 0),
                                get_option('asp_disallow_file_edit', 0),
                                get_option('asp_protect_headers', 0)
                            ]));
                            ?>/5</span>
                            <span
                                class="asl-stat-label"><?php _e('Features Active', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-success"><i class="ph ph-lock-simple"></i></div>
                        <div class="asl-stat-info">
                            <span
                                class="asl-stat-number"><?php echo get_option('asp_auto_regenerate_salts', 0) ? __('On', 'advanced-security-lite') : __('Off', 'advanced-security-lite'); ?></span>
                            <span
                                class="asl-stat-label"><?php _e('Auto Salt Regen', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-warning"><i class="ph ph-robot"></i></div>
                        <div class="asl-stat-info">
                            <span
                                class="asl-stat-number"><?php echo get_option('asp_recaptcha_v2_enabled', 0) || get_option('asp_recaptcha_v3_enabled', 0) ? __('Active', 'advanced-security-lite') : __('Inactive', 'advanced-security-lite'); ?></span>
                            <span class="asl-stat-label"><?php _e('reCAPTCHA', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                </div>



                <!-- Security Tips -->
                <div class="asl-card asl-card-info">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-lightbulb"></i>
                            <?php _e('Security Recommendations', 'advanced-security-lite'); ?></h3>
                    </div>
                    <div class="asl-card-body">
                        <ul class="asl-checklist">
                            <?php if (!get_option('asp_auto_regenerate_salts', 0)): ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon">⚠️</span>
                                    <?php _e('Enable automatic salt regeneration for better security', 'advanced-security-lite'); ?>
                                </li>
                            <?php endif; ?>
                            <?php if (!get_option('asp_recaptcha_v2_enabled', 0) && !get_option('asp_recaptcha_v3_enabled', 0)): ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon">⚠️</span>
                                    <?php _e('Add reCAPTCHA protection to prevent bot attacks', 'advanced-security-lite'); ?>
                                </li>
                            <?php endif; ?>
                            <li class="asl-check-success">
                                <span class="asl-check-icon">✅</span>
                                <?php _e('Keep WordPress and plugins updated regularly', 'advanced-security-lite'); ?>
                            </li>
                            <li class="asl-check-success">
                                <span class="asl-check-icon">✅</span>
                                <?php _e('Use strong passwords and enable two-factor authentication', 'advanced-security-lite'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- General Settings Panel -->
            <div id="panel-general" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('WordPress Core Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Disable unnecessary WordPress features that can be exploited.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable REST API', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Block WP REST API access for non-authenticated users', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_rest_api" name="disable_rest_api" <?php checked(get_option('asp_disable_rest_api', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable WP-JSON', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Remove WP-JSON link from HTML header', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_wp_json" name="disable_wp_json" <?php checked(get_option('asp_disable_wp_json', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable XML-RPC', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Block XML-RPC interface (prevents brute force attacks)', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_xmlrpc" name="disable_xmlrpc" <?php checked(get_option('asp_disable_xmlrpc', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable RSS Feeds', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Turn off all RSS and Atom feeds', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_feeds" name="disable_feeds" <?php checked(get_option('asp_disable_feeds', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable Comments', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Completely disable WordPress comments system', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_comments" name="disable_comments" <?php checked(get_option('asp_disable_comments', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Salt Key Management', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('WordPress salt keys add extra security to your login cookies.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Auto Regenerate Salts', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Automatically regenerate salt keys on schedule', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="auto_regenerate_salts" name="auto_regenerate_salts" <?php checked(get_option('asp_auto_regenerate_salts', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-option asl-option-select">
                                <div class="asl-option-info">
                                    <strong><?php _e('Regeneration Frequency', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('How often to regenerate salt keys', 'advanced-security-lite'); ?></span>
                                </div>
                                <select id="salt_regeneration_frequency" name="salt_regeneration_frequency"
                                    class="asl-select">
                                    <option value="daily" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'daily'); ?>><?php _e('Daily', 'advanced-security-lite'); ?>
                                    </option>
                                    <option value="weekly" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'weekly'); ?>><?php _e('Weekly', 'advanced-security-lite'); ?>
                                    </option>
                                    <option value="monthly" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'monthly'); ?>><?php _e('Monthly', 'advanced-security-lite'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication Panel -->
            <div id="panel-authentication" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('reCAPTCHA Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Protect your login forms from bots and brute force attacks.', 'advanced-security-lite'); ?>
                            <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener"
                                style="color: #7c3aed; font-weight: 500;">
                                <?php _e('Get reCAPTCHA keys →', 'advanced-security-lite'); ?>
                            </a>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Enable reCAPTCHA v2', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Show checkbox challenge on login forms', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="recaptcha_v2_enabled" name="recaptcha_v2_enabled" <?php checked(get_option('asp_recaptcha_v2_enabled', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Enable reCAPTCHA v3', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Invisible verification with score-based detection', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="recaptcha_v3_enabled" name="recaptcha_v3_enabled" <?php checked(get_option('asp_recaptcha_v3_enabled', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-form-row">
                                <div class="asl-form-group">
                                    <label
                                        for="recaptcha_site_key"><?php _e('Site Key', 'advanced-security-lite'); ?></label>
                                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
                                        class="asl-input"
                                        value="<?php echo esc_attr(get_option('asp_recaptcha_site_key', '')); ?>"
                                        placeholder="<?php _e('Enter your site key', 'advanced-security-lite'); ?>">
                                </div>
                                <div class="asl-form-group">
                                    <label
                                        for="recaptcha_secret_key"><?php _e('Secret Key', 'advanced-security-lite'); ?></label>
                                    <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key"
                                        class="asl-input"
                                        value="<?php echo esc_attr(get_option('asp_recaptcha_secret_key', '')); ?>"
                                        placeholder="<?php _e('Enter your secret key', 'advanced-security-lite'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Login Security', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Additional protection for your login page.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Hide Login Errors', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Show generic error messages to prevent username guessing', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_login_errors" name="hide_login_errors" <?php checked(get_option('asp_hide_login_errors', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable Registration', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Block new user registrations', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_registration" name="disable_registration" <?php checked(get_option('asp_disable_registration', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable Password Recovery', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Block password reset functionality', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_password_recovery"
                                        name="disable_password_recovery" <?php checked(get_option('asp_disable_password_recovery', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Firewall Panel -->
            <div id="panel-firewall" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Request Filtering', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Block malicious requests and suspicious activities.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Block Bad Requests', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Filter and block suspicious query strings', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_bad_requests" name="disallow_bad_requests" <?php checked(get_option('asp_disallow_bad_requests', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Prevent User Enumeration', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Block author enumeration attempts', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="prevent_user_enumeration" name="prevent_user_enumeration"
                                        <?php checked(get_option('asp_prevent_user_enumeration', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Block Directory Listing', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Prevent directory browsing', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_dir_listing" name="disallow_dir_listing" <?php checked(get_option('asp_disallow_dir_listing', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Upload Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Control and restrict file uploads.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Block Malicious Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Scan and block potentially dangerous file uploads', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_malicious_uploads"
                                        name="disallow_malicious_uploads" <?php checked(get_option('asp_disallow_malicious_uploads', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Block Plugin Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Prevent plugin installation via upload', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_plugin_upload" name="disallow_plugin_upload"
                                        <?php checked(get_option('asp_disallow_plugin_upload', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Block Theme Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Prevent theme installation via upload', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_theme_upload" name="disallow_theme_upload" <?php checked(get_option('asp_disallow_theme_upload', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hardening Panel -->
            <div id="panel-hardening" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Version Hiding', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Hide version information from potential attackers.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Hide WordPress Version', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Remove WordPress version from HTML source', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_wp_version" name="hide_wp_version" <?php checked(get_option('asp_hide_wp_version', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Hide PHP Version', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Remove PHP version from HTTP headers', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_php_version" name="hide_php_version" <?php checked(get_option('asp_hide_php_version', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('File Editor & Headers', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Additional security hardening options.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable File Editor', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Remove plugin and theme editor from dashboard', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_file_edit" name="disallow_file_edit" <?php checked(get_option('asp_disallow_file_edit', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Security Headers', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Add security headers (X-Frame-Options, X-XSS-Protection, etc.)', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="protect_headers" name="protect_headers" <?php checked(get_option('asp_protect_headers', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Obfuscate Author Slugs', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Replace author URLs with random strings', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="obfuscate_author_slugs" name="obfuscate_author_slugs"
                                        <?php checked(get_option('asp_obfuscate_author_slugs', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Obfuscate Emails', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Encode email addresses to prevent scraping', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="obfuscate_emails" name="obfuscate_emails" <?php checked(get_option('asp_obfuscate_emails', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tools Panel -->
            <div id="panel-tools" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Site Tools', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Additional tools for managing your site security and access.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Maintenance Mode', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Show a maintenance page to non-logged-in visitors', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php checked(get_option('asp_maintenance_mode', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-form-group" style="margin-top: 15px;">
                                <label
                                    for="maintenance_message"><?php _e('Maintenance Message', 'advanced-security-lite'); ?></label>
                                <textarea id="maintenance_message" name="maintenance_message" class="asl-input" rows="3"
                                    placeholder="<?php _e('Enter your maintenance message...', 'advanced-security-lite'); ?>"><?php echo esc_textarea(get_option('asp_maintenance_message', __('We are currently performing scheduled maintenance. Please check back soon.', 'advanced-security-lite'))); ?></textarea>
                            </div>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Limit Post Revisions', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Limit the number of post revisions to reduce database bloat', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="limit_revisions" name="limit_revisions" <?php checked(get_option('asp_limit_revisions', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-option asl-option-select">
                                <div class="asl-option-info">
                                    <strong><?php _e('Maximum Revisions', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Number of revisions to keep per post', 'advanced-security-lite'); ?></span>
                                </div>
                                <select id="revisions_limit" name="revisions_limit" class="asl-select">
                                    <option value="2" <?php selected(get_option('asp_revisions_limit', 5), 2); ?>>2
                                    </option>
                                    <option value="3" <?php selected(get_option('asp_revisions_limit', 5), 3); ?>>3
                                    </option>
                                    <option value="5" <?php selected(get_option('asp_revisions_limit', 5), 5); ?>>5
                                    </option>
                                    <option value="10" <?php selected(get_option('asp_revisions_limit', 5), 10); ?>>10
                                    </option>
                                    <option value="15" <?php selected(get_option('asp_revisions_limit', 5), 15); ?>>15
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('API & Authentication', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Control API access and authentication methods.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable Application Passwords', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Disable WordPress 5.6+ Application Passwords feature', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_app_passwords" name="disable_app_passwords" <?php checked(get_option('asp_disable_app_passwords', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Disable Pingbacks & Trackbacks', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Completely disable pingback and trackback functionality', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_pingbacks" name="disable_pingbacks" <?php checked(get_option('asp_disable_pingbacks', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Hide Admin Username', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Hide administrator usernames from author archives and displays', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_admin_username" name="hide_admin_username" <?php checked(get_option('asp_hide_admin_username', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log Panel -->
            <div id="panel-activity" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Failed Login Attempts', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Recent failed login attempts detected on your site.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <?php
                        $failed_logins = get_option('asp_failed_logins', array());
                        $has_attempts = false;

                        // Flatten all attempts into a single array with IP info
                        $all_attempts = array();
                        foreach ($failed_logins as $ip => $attempts) {
                            foreach ($attempts as $attempt) {
                                $attempt['ip'] = $ip;
                                $all_attempts[] = $attempt;
                            }
                        }

                        // Sort by time descending
                        usort($all_attempts, function ($a, $b) {
                            return $b['time'] - $a['time'];
                        });

                        // Get last 50 attempts
                        $all_attempts = array_slice($all_attempts, 0, 50);

                        if (!empty($all_attempts)):
                            $has_attempts = true;
                            ?>
                            <div class="asl-table-responsive">
                                <table class="asl-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Date/Time', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('Username', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('IP Address', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('Browser', 'advanced-security-lite'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_attempts as $attempt): ?>
                                            <tr>
                                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $attempt['time']); ?>
                                                </td>
                                                <td><code><?php echo esc_html($attempt['username']); ?></code></td>
                                                <td><code><?php echo esc_html($attempt['ip']); ?></code></td>
                                                <td class="asl-browser-cell">
                                                    <?php echo esc_html(substr($attempt['user_agent'] ?? 'Unknown', 0, 50)); ?>...
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="asl-empty-state">
                                <div class="asl-empty-icon">✅</div>
                                <h4><?php _e('No Failed Login Attempts', 'advanced-security-lite'); ?></h4>
                                <p><?php _e('Great news! There are no failed login attempts recorded.', 'advanced-security-lite'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Admin Access Log', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Recent admin area access by authenticated users.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <?php
                        $admin_log = get_option('asp_admin_access_log', array());
                        $admin_log = array_reverse($admin_log); // Most recent first
                        $admin_log = array_slice($admin_log, 0, 30);

                        if (!empty($admin_log)):
                            ?>
                            <div class="asl-table-responsive">
                                <table class="asl-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Date/Time', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('User', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('IP Address', 'advanced-security-lite'); ?></th>
                                            <th><?php _e('Page', 'advanced-security-lite'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admin_log as $entry): ?>
                                            <tr>
                                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry['time']); ?>
                                                </td>
                                                <td><?php echo esc_html($entry['username']); ?></td>
                                                <td><code><?php echo esc_html($entry['ip']); ?></code></td>
                                                <td class="asl-page-cell">
                                                    <?php echo esc_html(substr($entry['page'], 0, 40)); ?>...
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="asl-empty-state">
                                <div class="asl-empty-icon">📋</div>
                                <h4><?php _e('No Admin Access Logged', 'advanced-security-lite'); ?></h4>
                                <p><?php _e('Admin access logging will begin recording activity.', 'advanced-security-lite'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Settings Panel -->
            <div id="panel-settings" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><?php _e('Plugin Settings', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Manage general plugin behavior and uninstallation options.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php _e('Remove Data on Uninstall', 'advanced-security-lite'); ?></strong>
                                    <span><?php _e('Delete all settings and data when the plugin is uninstalled', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="remove_data_on_uninstall" name="remove_data_on_uninstall"
                                        <?php checked(get_option('asp_remove_data_on_uninstall', 1), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Panel -->
            <div id="panel-about" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3>🛡️ <?php _e('About Advanced Security Lite', 'advanced-security-lite'); ?></h3>
                        <p><?php _e('Comprehensive WordPress security plugin to protect your website from threats.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-about-grid">
                            <div class="asl-about-section">
                                <h4><?php _e('Plugin Information', 'advanced-security-lite'); ?></h4>
                                <table class="asl-info-table">
                                    <tr>
                                        <td><strong><?php _e('Version', 'advanced-security-lite'); ?></strong></td>
                                        <td><?php echo ASP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('Status', 'advanced-security-lite'); ?></strong></td>
                                        <td><span
                                                class="asl-badge asl-badge-success"><?php _e('Active', 'advanced-security-lite'); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('PHP Version', 'advanced-security-lite'); ?></strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('WordPress Version', 'advanced-security-lite'); ?></strong>
                                        </td>
                                        <td><?php echo get_bloginfo('version'); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="asl-about-section">
                                <h4><?php _e('Key Features', 'advanced-security-lite'); ?></h4>
                                <ul class="asl-feature-list">
                                    <li>✓
                                        <?php _e('Disable REST API for non-authenticated users', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php _e('Block XML-RPC attacks', 'advanced-security-lite'); ?></li>
                                    <li>✓ <?php _e('Hide WordPress version information', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php _e('Disable file editor in dashboard', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php _e('Add security headers (X-Frame-Options, etc.)', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php _e('Automatic salt key regeneration', 'advanced-security-lite'); ?></li>
                                    <li>✓ <?php _e('Google reCAPTCHA v2 & v3 support', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php _e('Block malicious requests', 'advanced-security-lite'); ?></li>
                                    <li>✓ <?php _e('Prevent user enumeration', 'advanced-security-lite'); ?></li>
                                    <li>✓ <?php _e('Hide login error messages', 'advanced-security-lite'); ?></li>
                                    <li>✓ <?php _e('Upload protection & file validation', 'advanced-security-lite'); ?>
                                    </li>
                                    <li><i class="ph ph-check" style="color: var(--success);"></i>
                                        <?php _e('Email obfuscation & author slug protection', 'advanced-security-lite'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-user"></i> <?php _e('Developer Information', 'advanced-security-lite'); ?>
                        </h3>
                        <p><?php _e('Created and maintained by SS Internet Services', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-dev-compact-v2">
                            <div class="asl-dev-compact-logo">
                                <i class="ph ph-globe"></i>
                            </div>
                            <div class="asl-dev-compact-details">
                                <h4>SS Internet Services</h4>
                                <div class="asl-dev-compact-name-v2">
                                    <?php printf(__('Developed by %s', 'advanced-security-lite'), esc_html(ASP_AUTHOR)); ?>
                                </div>
                                <div class="asl-dev-compact-links-v2">
                                    <a href="<?php echo esc_url(ASP_AUTHOR_URI); ?>" target="_blank"
                                        rel="noopener"><?php _e('Visit Website', 'advanced-security-lite'); ?></a>
                                </div>
                            </div>
                            <div class="asl-dev-compact-actions">
                                <a href="<?php echo esc_url(ASP_AUTHOR_URI); ?>" target="_blank" rel="noopener"
                                    class="asl-btn asl-btn-secondary asl-btn-sm">
                                    <?php _e('Visit Website', 'advanced-security-lite'); ?> →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credits -->
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-heart"></i> <?php _e('Credits', 'advanced-security-lite'); ?></h3>
                    </div>
                    <div class="asl-card-body">
                        <p><?php printf(__('Icons provided by %s', 'advanced-security-lite'), '<a href="https://phosphoricons.com/" target="_blank" rel="noopener">Phosphor Icons</a>'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="asl-save-bar">
                <button type="submit" class="asl-btn asl-btn-primary asl-btn-lg">
                    <i class="ph ph-check"></i> <?php _e('Save All Settings', 'advanced-security-lite'); ?>
                </button>
                <span class="asl-save-status"></span>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer class="asl-footer">
        <p><?php _e('Advanced Security Lite', 'advanced-security-lite'); ?> v<?php echo ASP_VERSION; ?> |
            <?php _e('Powered by SS Internet Services', 'advanced-security-lite'); ?>
        </p>
    </footer>
</div>