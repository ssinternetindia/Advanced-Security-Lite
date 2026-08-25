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
                    <span class="asl-version">v<?php echo esc_html(ASP_VERSION); ?></span>
                </div>
            </div>
        </div>
        <div class="asl-header-right">
            <button type="button" id="regenerate-salts-btn" class="asl-btn asl-btn-primary">
                <i class="ph ph-key"></i> <?php esc_html_e('Regenerate Salts', 'advanced-security-lite'); ?>
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
                get_option('asp_recaptcha_v2_enabled', 0) || get_option('asp_recaptcha_v3_enabled', 0) || get_option('asp_image_captcha_enabled', 0),
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
            <span class="asl-status-badge <?php echo esc_attr($status_class); ?>">
                <span class="asl-status-dot"><?php echo wp_kses_post($status_icon); ?></span>
                <?php echo esc_html($status_text); ?>
            </span>
        </div>
    </header>

    <!-- Horizontal Tab Navigation -->
    <nav class="asl-tabs">
        <button class="asl-tab active" data-tab="dashboard">
            <span class="asl-tab-icon"><i class="ph ph-chart-bar"></i></span>
            <?php esc_html_e('Dashboard', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="general">
            <span class="asl-tab-icon"><i class="ph ph-gear"></i></span>
            <?php esc_html_e('General', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="authentication">
            <span class="asl-tab-icon"><i class="ph ph-lock-key"></i></span>
            <?php esc_html_e('Authentication', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="firewall">
            <span class="asl-tab-icon"><i class="ph ph-wall"></i></span>
            <?php esc_html_e('Firewall', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="hardening">
            <span class="asl-tab-icon"><i class="ph ph-shield"></i></span>
            <?php esc_html_e('Hardening', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="tools">
            <span class="asl-tab-icon"><i class="ph ph-wrench"></i></span>
            <?php esc_html_e('Tools', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="activity">
            <span class="asl-tab-icon"><i class="ph ph-list-dashes"></i></span>
            <?php esc_html_e('Activity Log', 'advanced-security-lite'); ?>
        </button>
        <button class="asl-tab" data-tab="settings">
            <span class="asl-tab-icon"><i class="ph ph-sliders"></i></span>
            <?php esc_html_e('Settings', 'advanced-security-lite'); ?>
        </button>
            <button class="asl-tab" data-tab="status">
                <span class="asl-tab-icon"><i class="ph ph-pulse"></i></span>
                <?php esc_html_e('Status', 'advanced-security-lite'); ?>
            </button>
            <button class="asl-tab" data-tab="about">
            <span class="asl-tab-icon"><i class="ph ph-info"></i></span>
            <?php esc_html_e('About', 'advanced-security-lite'); ?>
        </button>
    </nav>

    <!-- Main Content Area -->
    <main class="asl-main">
        <form id="asl-settings-form" method="post">
            <?php wp_nonce_field('asp_settings', 'asp_nonce'); ?>

            <!-- Dashboard Panel -->
            <div id="panel-dashboard" class="asl-panel active">
                <?php
                // Calculate Security Score based on all key security features
                $security_features = array(
                    // General
                    get_option('asp_disable_wp_json', 0),
                    get_option('asp_disable_xmlrpc', 0),
                    get_option('asp_disable_rest_api', 0),
                    get_option('asp_auto_regenerate_salts', 0),
                    // Authentication
                    get_option('asp_recaptcha_v2_enabled', 0) || get_option('asp_recaptcha_v3_enabled', 0) || get_option('asp_image_captcha_enabled', 0),
                    get_option('asp_hide_login_errors', 0),
                    // Firewall
                    get_option('asp_disallow_bad_requests', 0),
                    get_option('asp_prevent_user_enumeration', 0),
                    get_option('asp_disallow_malicious_uploads', 0),
                    // Hardening
                    get_option('asp_hide_wp_version', 0),
                    get_option('asp_hide_php_version', 0),
                    get_option('asp_disallow_file_edit', 0),
                    get_option('asp_protect_headers', 0),
                    get_option('asp_obfuscate_author_slugs', 0),
                    // File Protection
                    get_option('asp_disable_php_execution', 0),
                    get_option('asp_protect_sensitive_files', 0),
                    // Tools
                    get_option('asp_disable_app_passwords', 0),
                );
                $total_features = count($security_features);
                $enabled_count = count(array_filter($security_features));
                $security_score = round(($enabled_count / $total_features) * 100);

                // Determine score color class
                if ($security_score >= 80) {
                    $score_class = 'asl-score-excellent';
                    $score_label = __('Excellent', 'advanced-security-lite');
                } elseif ($security_score >= 60) {
                    $score_class = 'asl-score-good';
                    $score_label = __('Good', 'advanced-security-lite');
                } elseif ($security_score >= 40) {
                    $score_class = 'asl-score-fair';
                    $score_label = __('Fair', 'advanced-security-lite');
                } else {
                    $score_class = 'asl-score-poor';
                    $score_label = __('Needs Attention', 'advanced-security-lite');
                }
                ?>

                <div class="asl-dash-grid">

                <!-- Security Score Card -->
                <div class="asl-security-score-card <?php echo esc_attr($score_class); ?>">
                    <div class="asl-score-circle">
                        <svg viewBox="0 0 36 36" class="asl-circular-chart">
                            <path class="asl-circle-bg" d="M18 2.0845
                                   a 15.9155 15.9155 0 0 1 0 31.831
                                   a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="asl-circle-progress"
                                stroke-dasharray="<?php echo esc_attr($security_score); ?>, 100" d="M18 2.0845
                                   a 15.9155 15.9155 0 0 1 0 31.831
                                   a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <text x="18" y="20.35"
                                class="asl-score-percentage"><?php echo esc_html($security_score); ?>%</text>
                        </svg>
                    </div>
                    <div class="asl-score-info">
                        <h3><?php esc_html_e('Security Score', 'advanced-security-lite'); ?></h3>
                        <span class="asl-score-label"><?php echo esc_html($score_label); ?></span>
                        <p class="asl-score-details">
                            <strong><?php echo esc_html($enabled_count); ?></strong>
                            <?php esc_html_e('of', 'advanced-security-lite'); ?>
                            <strong><?php echo esc_html($total_features); ?></strong>
                            <?php esc_html_e('security features enabled', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="asl-stats-grid">
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-primary"><i class="ph ph-shield-check"></i></div>
                        <div class="asl-stat-info">
                            <span
                                class="asl-stat-number"><?php echo esc_html($enabled_count); ?>/<?php echo esc_html($total_features); ?></span>
                            <span
                                class="asl-stat-label"><?php esc_html_e('Features Active', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-success"><i class="ph ph-lock-simple"></i></div>
                        <div class="asl-stat-info">
                            <span
                                class="asl-stat-number"><?php echo get_option('asp_auto_regenerate_salts', 0) ? esc_html__('On', 'advanced-security-lite') : esc_html__('Off', 'advanced-security-lite'); ?></span>
                            <span
                                class="asl-stat-label"><?php esc_html_e('Auto Salt Regen', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                    <div class="asl-stat-box">
                        <div class="asl-stat-icon asl-icon-warning"><i class="ph ph-robot"></i></div>
                        <div class="asl-stat-info">
                            <span
                                class="asl-stat-number"><?php echo get_option('asp_recaptcha_v2_enabled', 0) || get_option('asp_recaptcha_v3_enabled', 0) || get_option('asp_image_captcha_enabled', 0) ? esc_html__('Active', 'advanced-security-lite') : esc_html__('Inactive', 'advanced-security-lite'); ?></span>
                            <span
                                class="asl-stat-label"><?php esc_html_e('CAPTCHA', 'advanced-security-lite'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Security Recommendations -->
                <div class="asl-rec-card">
                    <h4><i class="ph ph-lightbulb"></i>
                        <?php esc_html_e('Security Recommendations', 'advanced-security-lite'); ?></h4>
                    <div class="asl-rec-body">
                        <?php
                        // Count warnings to show appropriate message
                        $warning_count = 0;
                        ?>
                        <ul class="asl-rec-list">
                            <?php // Core Protection ?>
                            <?php if (!get_option('asp_disable_xmlrpc', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Disable XML-RPC to prevent brute force attacks', 'advanced-security-lite'); ?></span>
                                    <a href="#general" class="asl-rec-link"
                                        data-tab="general"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!get_option('asp_disable_rest_api', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Disable REST API for non-authenticated users', 'advanced-security-lite'); ?></span>
                                    <a href="#general" class="asl-rec-link"
                                        data-tab="general"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // Salt Regeneration ?>
                            <?php if (!get_option('asp_auto_regenerate_salts', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Enable automatic salt regeneration for enhanced cookie security', 'advanced-security-lite'); ?></span>
                                    <a href="#general" class="asl-rec-link"
                                        data-tab="general"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // CAPTCHA ?>
                            <?php if (!get_option('asp_recaptcha_v2_enabled', 0) && !get_option('asp_recaptcha_v3_enabled', 0) && !get_option('asp_image_captcha_enabled', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Add CAPTCHA protection (reCAPTCHA or image CAPTCHA) to prevent bot login attacks', 'advanced-security-lite'); ?></span>
                                    <a href="#authentication" class="asl-rec-link"
                                        data-tab="authentication"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // Login Security ?>
                            <?php if (!get_option('asp_hide_login_errors', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Hide login errors to prevent username guessing', 'advanced-security-lite'); ?></span>
                                    <a href="#authentication" class="asl-rec-link"
                                        data-tab="authentication"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // Firewall ?>
                            <?php if (!get_option('asp_disallow_bad_requests', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Enable bad request filtering to block malicious queries', 'advanced-security-lite'); ?></span>
                                    <a href="#firewall" class="asl-rec-link"
                                        data-tab="firewall"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!get_option('asp_prevent_user_enumeration', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Prevent user enumeration to hide usernames from attackers', 'advanced-security-lite'); ?></span>
                                    <a href="#firewall" class="asl-rec-link"
                                        data-tab="firewall"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // Hardening ?>
                            <?php if (!get_option('asp_hide_wp_version', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Hide WordPress version to prevent targeted attacks', 'advanced-security-lite'); ?></span>
                                    <a href="#hardening" class="asl-rec-link"
                                        data-tab="hardening"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!get_option('asp_disallow_file_edit', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Disable file editor to prevent code injection if admin is compromised', 'advanced-security-lite'); ?></span>
                                    <a href="#hardening" class="asl-rec-link"
                                        data-tab="hardening"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if (!get_option('asp_protect_headers', 0)):
                                $warning_count++; ?>
                                <li class="asl-check-warning">
                                    <span class="asl-check-icon"><i class="ph ph-warning"></i></span>
                                    <span><?php esc_html_e('Add security headers to protect against XSS and clickjacking', 'advanced-security-lite'); ?></span>
                                    <a href="#hardening" class="asl-rec-link"
                                        data-tab="hardening"><?php esc_html_e('Fix →', 'advanced-security-lite'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php // Show success message if all recommendations are addressed ?>
                            <?php if ($warning_count === 0): ?>
                                <li class="asl-check-success">
                                    <span class="asl-check-icon"><i class="ph ph-check-circle"></i></span>
                                    <span><?php esc_html_e('Excellent! All recommended security features are enabled.', 'advanced-security-lite'); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($warning_count > 0): ?>
                            <div class="asl-rec-summary">
                                <p>
                                    <strong><?php echo esc_html($warning_count); ?></strong>
                                    <?php echo esc_html(_n('recommendation needs attention', 'recommendations need attention', $warning_count, 'advanced-security-lite')); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                </div><!-- /.asl-dash-grid -->
            </div>

            <!-- General Settings Panel -->
            <div id="panel-general" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-globe"></i> <?php esc_html_e('WordPress Core Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Disable unnecessary WordPress features that can be exploited.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable REST API', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block WP REST API access for non-authenticated users', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_rest_api" name="disable_rest_api" <?php checked(get_option('asp_disable_rest_api', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable WP-JSON', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Remove WP-JSON link from HTML header', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_wp_json" name="disable_wp_json" <?php checked(get_option('asp_disable_wp_json', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable XML-RPC', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block XML-RPC interface (prevents brute force attacks)', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_xmlrpc" name="disable_xmlrpc" <?php checked(get_option('asp_disable_xmlrpc', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable RSS Feeds', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Turn off all RSS and Atom feeds', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_feeds" name="disable_feeds" <?php checked(get_option('asp_disable_feeds', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable Comments', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Completely disable WordPress comments system', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_comments" name="disable_comments" <?php checked(get_option('asp_disable_comments', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Hide Admin Notices', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Declutter wp-admin by hiding third-party notices; review them anytime from the bell in the admin bar', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_admin_notices" name="hide_admin_notices" <?php checked(get_option('asp_hide_admin_notices', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-key"></i> <?php esc_html_e('Salt Key Management', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('WordPress salt keys add extra security to your login cookies.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Auto Regenerate Salts', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Automatically regenerate salt keys on schedule', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="auto_regenerate_salts" name="auto_regenerate_salts" <?php checked(get_option('asp_auto_regenerate_salts', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-option asl-option-select">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Regeneration Frequency', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('How often to regenerate salt keys', 'advanced-security-lite'); ?></span>
                                </div>
                                <select id="salt_regeneration_frequency" name="salt_regeneration_frequency"
                                    class="asl-select">
                                    <option value="daily" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'daily'); ?>><?php esc_html_e('Daily', 'advanced-security-lite'); ?>
                                    </option>
                                    <option value="weekly" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'weekly'); ?>>
                                        <?php esc_html_e('Weekly', 'advanced-security-lite'); ?>
                                    </option>
                                    <option value="monthly" <?php selected(get_option('asp_salt_regeneration_frequency', 'monthly'), 'monthly'); ?>>
                                        <?php esc_html_e('Monthly', 'advanced-security-lite'); ?>
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
                        <h3><i class="ph ph-robot"></i> <?php esc_html_e('CAPTCHA Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Protect your login forms from bots and brute force attacks. Choose reCAPTCHA (v2/v3) or the self-hosted image CAPTCHA.', 'advanced-security-lite'); ?>
                            <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener"
                                style="color: #7c3aed; font-weight: 500;">
                                <?php esc_html_e('Get reCAPTCHA keys →', 'advanced-security-lite'); ?>
                            </a>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Enable reCAPTCHA v2', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Show checkbox challenge on login forms', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="recaptcha_v2_enabled" name="recaptcha_v2_enabled" <?php checked(get_option('asp_recaptcha_v2_enabled', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Enable reCAPTCHA v3', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Invisible verification with score-based detection', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="recaptcha_v3_enabled" name="recaptcha_v3_enabled" <?php checked(get_option('asp_recaptcha_v3_enabled', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Enable Image CAPTCHA', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Smart self-hosted distorted-text challenge — no external service or API keys required', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="image_captcha_enabled" name="image_captcha_enabled" <?php checked(get_option('asp_image_captcha_enabled', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-form-row">
                                <div class="asl-form-group">
                                    <label
                                        for="recaptcha_site_key"><?php esc_html_e('Site Key', 'advanced-security-lite'); ?></label>
                                    <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
                                        class="asl-input"
                                        value="<?php echo esc_attr(get_option('asp_recaptcha_site_key', '')); ?>"
                                        placeholder="<?php esc_html_e('Enter your site key', 'advanced-security-lite'); ?>">
                                </div>
                                <div class="asl-form-group">
                                    <label
                                        for="recaptcha_secret_key"><?php esc_html_e('Secret Key', 'advanced-security-lite'); ?></label>
                                    <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key"
                                        class="asl-input"
                                        value="<?php echo esc_attr(get_option('asp_recaptcha_secret_key', '')); ?>"
                                        placeholder="<?php esc_html_e('Enter your secret key', 'advanced-security-lite'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-lock-key"></i> <?php esc_html_e('Login Security', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Additional protection for your login page.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Hide Login Errors', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Show generic error messages to prevent username guessing', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_login_errors" name="hide_login_errors" <?php checked(get_option('asp_hide_login_errors', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable Registration', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block new user registrations', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_registration" name="disable_registration" <?php checked(get_option('asp_disable_registration', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable Password Recovery', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block password reset functionality', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_password_recovery"
                                        name="disable_password_recovery" <?php checked(get_option('asp_disable_password_recovery', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Limit Login Attempts', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block IP addresses after too many failed login attempts', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="enable_login_limit"
                                        name="enable_login_limit" <?php checked(get_option('asp_enable_login_limit', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-form-row">
                                <div class="asl-form-group">
                                    <label for="max_login_attempts"><?php esc_html_e('Max Login Attempts', 'advanced-security-lite'); ?></label>
                                    <input type="number" id="max_login_attempts" name="max_login_attempts"
                                        class="asl-input" min="1" max="100"
                                        value="<?php echo esc_attr(get_option('asp_max_login_attempts', 5)); ?>">
                                </div>
                                <div class="asl-form-group">
                                    <label for="lockout_duration"><?php esc_html_e('Lockout Duration (minutes)', 'advanced-security-lite'); ?></label>
                                    <input type="number" id="lockout_duration" name="lockout_duration"
                                        class="asl-input" min="1" max="1440"
                                        value="<?php echo esc_attr(get_option('asp_lockout_duration', 30)); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Firewall Panel -->
            <div id="panel-firewall" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-funnel"></i> <?php esc_html_e('Request Filtering', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Block malicious requests and suspicious activities.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Block Bad Requests', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Filter and block suspicious query strings', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_bad_requests" name="disallow_bad_requests" <?php checked(get_option('asp_disallow_bad_requests', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Prevent User Enumeration', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block author enumeration attempts', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="prevent_user_enumeration" name="prevent_user_enumeration"
                                        <?php checked(get_option('asp_prevent_user_enumeration', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Block Directory Listing', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Prevent directory browsing', 'advanced-security-lite'); ?></span>
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
                        <h3><i class="ph ph-upload-simple"></i> <?php esc_html_e('Upload Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Control and restrict file uploads.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Block Malicious Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Scan and block potentially dangerous file uploads', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_malicious_uploads"
                                        name="disallow_malicious_uploads" <?php checked(get_option('asp_disallow_malicious_uploads', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Block Plugin Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Prevent plugin installation via upload', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_plugin_upload" name="disallow_plugin_upload"
                                        <?php checked(get_option('asp_disallow_plugin_upload', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Block Theme Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Prevent theme installation via upload', 'advanced-security-lite'); ?></span>
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
                        <h3><i class="ph ph-eye-slash"></i> <?php esc_html_e('Version Hiding', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Hide version information from potential attackers.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Hide WordPress Version', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Remove WordPress version from HTML source', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="hide_wp_version" name="hide_wp_version" <?php checked(get_option('asp_hide_wp_version', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Hide PHP Version', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Remove PHP version from HTTP headers', 'advanced-security-lite'); ?></span>
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
                        <h3><i class="ph ph-pencil-simple-line"></i> <?php esc_html_e('File Editor & Headers', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Additional security hardening options.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable File Editor', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Remove plugin and theme editor from dashboard', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disallow_file_edit" name="disallow_file_edit" <?php checked(get_option('asp_disallow_file_edit', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Security Headers', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Add security headers (X-Frame-Options, X-XSS-Protection, etc.)', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="protect_headers" name="protect_headers" <?php checked(get_option('asp_protect_headers', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Obfuscate Author Slugs', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Replace author URLs with random strings', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="obfuscate_author_slugs" name="obfuscate_author_slugs"
                                        <?php checked(get_option('asp_obfuscate_author_slugs', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Obfuscate Emails', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Encode email addresses to prevent scraping', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="obfuscate_emails" name="obfuscate_emails" <?php checked(get_option('asp_obfuscate_emails', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-file-lock"></i> <?php esc_html_e('File Protection', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Protect sensitive files and prevent malicious code execution.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable PHP Execution in Uploads', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block PHP execution in wp-content/uploads directory', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_php_execution" name="disable_php_execution" <?php checked(get_option('asp_disable_php_execution', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Protect Sensitive Files', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Block access to wp-config.php, .htaccess, debug.log', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="protect_sensitive_files" name="protect_sensitive_files"
                                        <?php checked(get_option('asp_protect_sensitive_files', 0), 1); ?>>
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
                        <h3><i class="ph ph-wrench"></i> <?php esc_html_e('Site Tools', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Additional tools for managing your site security and access.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Maintenance Mode', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Show a maintenance page to non-logged-in visitors', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php checked(get_option('asp_maintenance_mode', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-form-group" style="margin-top: 15px;">
                                <label
                                    for="maintenance_message"><?php esc_html_e('Maintenance Message', 'advanced-security-lite'); ?></label>
                                <textarea id="maintenance_message" name="maintenance_message" class="asl-input" rows="3"
                                    placeholder="<?php esc_html_e('Enter your maintenance message...', 'advanced-security-lite'); ?>"><?php echo esc_textarea(get_option('asp_maintenance_message', __('We are currently performing scheduled maintenance. Please check back soon.', 'advanced-security-lite'))); ?></textarea>
                            </div>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Custom Login Design', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Apply the 40/60 split-screen theme to the WordPress login page', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="custom_login_design"
                                        name="custom_login_design" <?php checked(get_option('asp_custom_login_design', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-login-design-options" id="asl-login-design-options" <?php if (!get_option('asp_custom_login_design', 0)) : ?>style="display: none;"<?php endif; ?>>
                                <div class="asl-form-group">
                                    <label for="login_accent_color"><?php esc_html_e('Accent Color', 'advanced-security-lite'); ?></label>
                                    <div class="asl-color-row">
                                        <input type="color" id="login_accent_color" name="login_accent_color" value="<?php echo esc_attr(get_option('asp_login_accent_color', '#2563eb')); ?>">
                                        <code class="asl-color-hex" id="asl-login-accent-hex"><?php echo esc_html(get_option('asp_login_accent_color', '#2563eb')); ?></code>
                                    </div>
                                    <p class="asl-field-hint"><?php esc_html_e('Used for the sign-in button, links, focus rings and brand highlights.', 'advanced-security-lite'); ?></p>
                                </div>

                                <div class="asl-option asl-option-select">
                                    <div class="asl-option-info">
                                        <strong><?php esc_html_e('Brand Panel Side', 'advanced-security-lite'); ?></strong>
                                        <span><?php esc_html_e('Where to show the decorative side panel, or disable it for a centred card', 'advanced-security-lite'); ?></span>
                                    </div>
                                    <select id="login_panel_side" name="login_panel_side" class="asl-select">
                                        <option value="left" <?php selected(get_option('asp_login_panel_side', 'left'), 'left'); ?>><?php esc_html_e('Left', 'advanced-security-lite'); ?></option>
                                        <option value="right" <?php selected(get_option('asp_login_panel_side', 'left'), 'right'); ?>><?php esc_html_e('Right', 'advanced-security-lite'); ?></option>
                                        <option value="none" <?php selected(get_option('asp_login_panel_side', 'left'), 'none'); ?>><?php esc_html_e('None (centred card)', 'advanced-security-lite'); ?></option>
                                    </select>
                                </div>

                                <div class="asl-option asl-option-select">
                                    <div class="asl-option-info">
                                        <strong><?php esc_html_e('Form Style', 'advanced-security-lite'); ?></strong>
                                        <span><?php esc_html_e('Solid card or translucent glass with backdrop blur', 'advanced-security-lite'); ?></span>
                                    </div>
                                    <select id="login_form_style" name="login_form_style" class="asl-select">
                                        <option value="solid" <?php selected(get_option('asp_login_form_style', 'solid'), 'solid'); ?>><?php esc_html_e('Solid', 'advanced-security-lite'); ?></option>
                                        <option value="glass" <?php selected(get_option('asp_login_form_style', 'solid'), 'glass'); ?>><?php esc_html_e('Glass', 'advanced-security-lite'); ?></option>
                                    </select>
                                </div>

                                <div class="asl-form-group">
                                    <label for="login_logo"><?php esc_html_e('Login Logo', 'advanced-security-lite'); ?></label>
                                    <div class="asl-upload-row">
                                        <input type="text" id="login_logo" name="login_logo" class="asl-input" value="<?php echo esc_attr(get_option('asp_login_logo', '')); ?>" placeholder="<?php esc_attr_e('https://your-site.com/logo.png', 'advanced-security-lite'); ?>">
                                        <button type="button" class="asl-btn asl-btn-secondary" id="asp-login-logo-upload"><i class="ph ph-image"></i> <?php esc_html_e('Choose Image', 'advanced-security-lite'); ?></button>
                                        <button type="button" class="asl-btn asl-btn-outline" id="asp-login-logo-clear" <?php echo get_option('asp_login_logo', '') ? '' : 'disabled'; ?>><?php esc_html_e('Clear', 'advanced-security-lite'); ?></button>
                                    </div>
                                    <p class="asl-field-hint"><?php esc_html_e('Leave empty to use your site name as the text logo.', 'advanced-security-lite'); ?></p>
                                </div>
                            </div>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Limit Post Revisions', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Limit the number of post revisions to reduce database bloat', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="limit_revisions" name="limit_revisions" <?php checked(get_option('asp_limit_revisions', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-option asl-option-select">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Maximum Revisions', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Number of revisions to keep per post', 'advanced-security-lite'); ?></span>
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
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Auto-Delete Spam', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Weekly cleanup of spam comments older than 30 days', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="auto_delete_spam" name="auto_delete_spam" <?php checked(get_option('asp_auto_delete_spam', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-plug"></i> <?php esc_html_e('API & Authentication', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Control API access and authentication methods.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable Application Passwords', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Disable WordPress 5.6+ Application Passwords feature', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_app_passwords" name="disable_app_passwords" <?php checked(get_option('asp_disable_app_passwords', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Disable Pingbacks & Trackbacks', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Completely disable pingback and trackback functionality', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="disable_pingbacks" name="disable_pingbacks" <?php checked(get_option('asp_disable_pingbacks', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Hide Admin Username', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Hide administrator usernames from author archives and displays', 'advanced-security-lite'); ?></span>
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
                <!-- Clear Logs Header -->
                <div class="asl-log-actions">
                    <p class="asl-log-info">
                        <i class="ph ph-info"></i>
                        <?php esc_html_e('Logs are automatically cleared after 3 days.', 'advanced-security-lite'); ?>
                    </p>
                    <button type="button" id="asp-clear-logs" class="asl-btn asl-btn-danger asl-btn-sm">
                        <i class="ph ph-trash"></i>
                        <?php esc_html_e('Clear All Logs', 'advanced-security-lite'); ?>
                    </button>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-shield-warning"></i> <?php esc_html_e('Failed Login Attempts', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Recent failed login attempts detected on your site.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <?php
                        // Run cleanup before displaying to ensure 3-day old logs are removed
                        if (function_exists('asp_cleanup_logs_older_than_3_days')) {
                            asp_cleanup_logs_older_than_3_days();
                        }

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
                                            <th><?php esc_html_e('Date/Time', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('Username', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('IP Address', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('Browser', 'advanced-security-lite'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_attempts as $attempt): ?>
                                            <tr>
                                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $attempt['time'])); ?>
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
                                <h4><?php esc_html_e('No Failed Login Attempts', 'advanced-security-lite'); ?></h4>
                                <p><?php esc_html_e('Great news! There are no failed login attempts recorded.', 'advanced-security-lite'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-clipboard-text"></i> <?php esc_html_e('Admin Access Log', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Recent admin area access by authenticated users.', 'advanced-security-lite'); ?>
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
                                            <th><?php esc_html_e('Date/Time', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('User', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('IP Address', 'advanced-security-lite'); ?></th>
                                            <th><?php esc_html_e('Page', 'advanced-security-lite'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admin_log as $entry): ?>
                                            <tr>
                                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry['time'])); ?>
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
                                <h4><?php esc_html_e('No Admin Access Logged', 'advanced-security-lite'); ?></h4>
                                <p><?php esc_html_e('Admin access logging will begin recording activity.', 'advanced-security-lite'); ?>
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
                        <h3><i class="ph ph-sliders-horizontal"></i> <?php esc_html_e('Plugin Settings', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Manage general plugin behavior and uninstallation options.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-options-list">
                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Error Log Viewer', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Show the "Error Logs" entry in the WordPress admin bar to inspect all detected log files', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="error_log_viewer" name="error_log_viewer"
                                        <?php checked(get_option('asp_error_log_viewer', 0), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <label class="asl-option">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Remove Data on Uninstall', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Delete all settings and data when the plugin is uninstalled', 'advanced-security-lite'); ?></span>
                                </div>
                                <div class="asl-switch">
                                    <input type="checkbox" id="remove_data_on_uninstall" name="remove_data_on_uninstall"
                                        <?php checked(get_option('asp_remove_data_on_uninstall', 1), 1); ?>>
                                    <span class="asl-switch-slider"></span>
                                </div>
                            </label>

                            <div class="asl-option asl-option-select">
                                <div class="asl-option-info">
                                    <strong><?php esc_html_e('Emergency Reset', 'advanced-security-lite'); ?></strong>
                                    <span><?php esc_html_e('Restore all plugin security settings to their defaults', 'advanced-security-lite'); ?></span>
                                </div>
                                <button type="button" id="emergency-reset-btn" class="asl-btn asl-btn-danger asl-btn-sm">
                                    <i class="ph ph-warning"></i> <?php esc_html_e('Emergency Reset', 'advanced-security-lite'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Panel -->
            <?php
            global $wpdb;
            $theme = wp_get_theme();
            $uploads = wp_upload_dir();
            $disk_total = function_exists('disk_total_space') ? @disk_total_space(WP_CONTENT_DIR) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $disk_free = function_exists('disk_free_space') ? @disk_free_space(WP_CONTENT_DIR) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $db_version = method_exists($wpdb, 'db_version') ? $wpdb->db_version() : '';

            if (!function_exists('asp_status_size')) {
                /**
                 * Format a PHP size shorthand value for display.
                 *
                 * @param string $value Raw ini value (e.g. 256M).
                 * @return string
                 */
                function asp_status_size($value)
                {
                    return (string) $value;
                }
            }

            if (!function_exists('asp_status_yes_no')) {
                /**
                 * Colored yes/no badge.
                 *
                 * @param bool  $state    Condition state.
                 * @param string $yes     Label when true.
                 * @param string $no      Label when false.
                 * @param string $yes_k   Dot class when true.
                 * @param string $no_k    Dot class when false.
                 * @return string
                 */
                function asp_status_yes_no($state, $yes, $no, $yes_k = 'ok', $no_k = 'warn')
                {
                    $dot = $state ? $yes_k : $no_k;
                    $label = $state ? $yes : $no;
                    return '<span class="asl-status-line"><span class="asl-dot asl-dot-' . esc_attr($dot) . '"></span>' . esc_html($label) . '</span>';
                }
            }

            $extensions = array('curl', 'gd', 'imagick', 'mbstring', 'xml', 'zip', 'openssl', 'mysqli', 'pdo_mysql', 'json', 'filter', 'iconv', 'sodium', 'fileinfo');

            ob_start();
            echo "Advanced Security Lite — System Report\n";
            echo "Generated: " . gmdate('Y-m-d H:i:s') . " UTC\n";
            echo str_repeat('-', 48) . "\n";
            echo "WordPress:        " . get_bloginfo('version') . "\n";
            echo "Plugin:           Advanced Security Lite " . ASP_VERSION . "\n";
            echo "PHP:              " . PHP_VERSION . " (" . php_sapi_name() . ")\n";
            echo "Server:           " . (isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'Unknown') . "\n";
            echo "Database:         " . $db_version . "\n";
            echo "OS:               " . php_uname('s') . " " . php_uname('r') . "\n";
            echo "HTTPS:            " . (is_ssl() ? 'Yes' : 'No') . "\n";
            echo "Multisite:        " . (is_multisite() ? 'Yes' : 'No') . "\n";
            echo "WP_DEBUG:         " . (defined('WP_DEBUG') && WP_DEBUG ? 'On' : 'Off') . "\n";
            echo "Memory Limit:     " . WP_MEMORY_LIMIT . "\n";
            echo "Theme:            " . $theme->get('Name') . ' ' . $theme->get('Version') . "\n";
            echo "Active Plugins:   " . count((array) get_option('active_plugins', array())) . "\n";
            echo "Uploads Dir:      " . $uploads['basedir'] . "\n";
            echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
            echo "post_max_size:       " . ini_get('post_max_size') . "\n";
            echo "memory_limit:        " . ini_get('memory_limit') . "\n";
            echo "max_execution_time:  " . ini_get('max_execution_time') . "\n";
            echo "max_input_vars:      " . ini_get('max_input_vars') . "\n";
            echo "Extensions:       ";
            foreach ($extensions as $ext) {
                echo $ext . (extension_loaded($ext) ? '+' : '-');
            }
            echo "\n";
            $report_text = ob_get_clean();
            ?>

            <div id="panel-status" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header asl-card-header-row">
                        <div class="asl-card-header-text">
                            <h3><i class="ph ph-gauge"></i> <?php esc_html_e('Server Environment', 'advanced-security-lite'); ?></h3>
                            <p><?php esc_html_e('Live details about the server running this site.', 'advanced-security-lite'); ?></p>
                        </div>
                        <button type="button" id="asp-copy-report" class="asl-btn asl-btn-secondary asl-btn-sm">
                            <i class="ph ph-copy"></i> <?php esc_html_e('Copy System Report', 'advanced-security-lite'); ?>
                        </button>
                    </div>
                    <div class="asl-card-body">
                        <textarea id="asl-report-text" hidden aria-hidden="true"><?php echo esc_textarea($report_text); ?></textarea>
                        <table class="asl-info-table">
                            <tr>
                                <td><?php esc_html_e('Operating System', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(php_uname('s') . ' ' . php_uname('r')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Server Software', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(isset($_SERVER['SERVER_SOFTWARE']) ? wp_unslash($_SERVER['SERVER_SOFTWARE']) : __('Unknown', 'advanced-security-lite')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('PHP Version', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(PHP_VERSION); ?>
                                    <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? asp_status_yes_no(true, __('Supported', 'advanced-security-lite'), '', 'ok', 'ok') : asp_status_yes_no(false, '', __('Outdated', 'advanced-security-lite'), 'ok', 'bad'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('PHP Interface (SAPI)', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(php_sapi_name()); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Database Version', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html($db_version ? $db_version : __('Unknown', 'advanced-security-lite')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('HTTPS', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(is_ssl(), __('Secure (SSL)', 'advanced-security-lite'), __('Not detected', 'advanced-security-lite'), 'ok', 'warn'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html() ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Disk Space Free', 'advanced-security-lite'); ?></td>
                                <td>
                                    <?php
                                    if ($disk_total && $disk_free) {
                                        $free_pct = round(($disk_free / $disk_total) * 100, 1);
                                        echo esc_html(number_format_i18n($disk_free / 1073741824, 1) . ' GB of ' . number_format_i18n($disk_total / 1073741824, 1) . ' GB (' . $free_pct . '%)');
                                        echo $free_pct < 10
                                            ? asp_status_yes_no(false, '', __('Low', 'advanced-security-lite'), 'ok', 'bad') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            : asp_status_yes_no(true, __('Healthy', 'advanced-security-lite'), '', 'ok', 'ok'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    } else {
                                        esc_html_e('Not available on this host', 'advanced-security-lite');
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-cpu"></i> <?php esc_html_e('PHP Configuration', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Active PHP limits that affect uploads and plugin operations.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <table class="asl-info-table">
                            <tr>
                                <td><?php esc_html_e('memory_limit', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html(asp_status_size(ini_get('memory_limit'))); ?></code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('upload_max_filesize', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html(asp_status_size((string) ini_get('upload_max_filesize'))); ?></code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('post_max_size', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html(asp_status_size((string) ini_get('post_max_size'))); ?></code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('max_input_vars', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html((string) ini_get('max_input_vars')); ?></code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('max_execution_time', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html((string) ini_get('max_execution_time')); ?>s</code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('OPcache', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(extension_loaded('Zend OPcache'), __('Enabled', 'advanced-security-lite'), __('Not installed', 'advanced-security-lite'), 'ok', 'warn'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-monitor"></i> <?php esc_html_e('WordPress', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Core configuration relevant to security debugging.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <table class="asl-info-table">
                            <tr>
                                <td><?php esc_html_e('WordPress Version', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('WP_DEBUG', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(defined('WP_DEBUG') && WP_DEBUG, __('On', 'advanced-security-lite'), __('Off', 'advanced-security-lite'), 'warn', 'ok'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('WP_DEBUG_LOG', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG, __('Enabled', 'advanced-security-lite'), __('Disabled', 'advanced-security-lite'), 'ok', 'warn'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Multisite', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(is_multisite(), __('Yes', 'advanced-security-lite'), __('No', 'advanced-security-lite'), 'ok', 'ok'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Site Language / Locale', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(get_locale()); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('WP Memory Limit', 'advanced-security-lite'); ?></td>
                                <td><code><?php echo esc_html(defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '—'); ?></code></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Active Theme', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html($theme->get('Name') . ' ' . $theme->get('Version')); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Active Plugins', 'advanced-security-lite'); ?></td>
                                <td><?php echo esc_html(count((array) get_option('active_plugins', array()))); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e('Uploads Directory', 'advanced-security-lite'); ?></td>
                                <td><?php echo asp_status_yes_no(wp_is_writable($uploads['basedir']), __('Writable', 'advanced-security-lite'), __('Not writable', 'advanced-security-lite'), 'ok', 'bad'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <code><?php echo esc_html($uploads['basedir']); ?></code>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-puzzle-piece"></i> <?php esc_html_e('PHP Extensions', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Extensions commonly required by WordPress and security features.', 'advanced-security-lite'); ?></p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-ext-grid">
                            <?php foreach ($extensions as $ext) : ?>
                                <div class="asl-ext">
                                    <?php if (extension_loaded($ext)) : ?>
                                        <span class="asl-status-line"><span class="asl-dot asl-dot-ok"></span><?php echo esc_html($ext); ?></span>
                                    <?php else : ?>
                                        <span class="asl-status-line"><span class="asl-dot asl-dot-bad"></span><?php echo esc_html($ext); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Panel -->
            <div id="panel-about" class="asl-panel">
                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-sparkle"></i> <?php esc_html_e('About Advanced Security Lite', 'advanced-security-lite'); ?></h3>
                        <p><?php esc_html_e('Comprehensive WordPress security plugin to protect your website from threats.', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-about-grid">
                            <div class="asl-about-section">
                                <h4><?php esc_html_e('Plugin Information', 'advanced-security-lite'); ?></h4>
                                <table class="asl-info-table">
                                    <tr>
                                        <td><strong><?php esc_html_e('Version', 'advanced-security-lite'); ?></strong>
                                        </td>
                                        <td><?php echo esc_html(ASP_VERSION); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php esc_html_e('Status', 'advanced-security-lite'); ?></strong>
                                        </td>
                                        <td><span
                                                class="asl-badge asl-badge-success"><?php esc_html_e('Active', 'advanced-security-lite'); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php esc_html_e('Server & PHP Details', 'advanced-security-lite'); ?></strong>
                                        </td>
                                        <td><a href="#status" class="asl-rec-link"
                                                data-tab="status"><?php esc_html_e('See Status tab →', 'advanced-security-lite'); ?></a>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="asl-about-section">
                                <h4><?php esc_html_e('Key Features', 'advanced-security-lite'); ?></h4>
                                <ul class="asl-feature-list">
                                    <li>✓
                                        <?php esc_html_e('Disable REST API for non-authenticated users', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php esc_html_e('Block XML-RPC attacks', 'advanced-security-lite'); ?></li>
                                    <li>✓
                                        <?php esc_html_e('Hide WordPress version information', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Disable file editor in dashboard', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Add security headers (X-Frame-Options, etc.)', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Automatic salt key regeneration', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Google reCAPTCHA v2 & v3 support', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Self-hosted image CAPTCHA (no external service)', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php esc_html_e('Block malicious requests', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php esc_html_e('Prevent user enumeration', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓ <?php esc_html_e('Hide login error messages', 'advanced-security-lite'); ?>
                                    </li>
                                    <li>✓
                                        <?php esc_html_e('Upload protection & file validation', 'advanced-security-lite'); ?>
                                    </li>
                                    <li><i class="ph ph-check" style="color: var(--success);"></i>
                                        <?php esc_html_e('Email obfuscation & author slug protection', 'advanced-security-lite'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="asl-card">
                    <div class="asl-card-header">
                        <h3><i class="ph ph-user"></i>
                            <?php esc_html_e('Developer Information', 'advanced-security-lite'); ?>
                        </h3>
                        <p><?php esc_html_e('Created and maintained by SS Internet Services', 'advanced-security-lite'); ?>
                        </p>
                    </div>
                    <div class="asl-card-body">
                        <div class="asl-dev-compact-v2">
                            <div class="asl-dev-compact-logo">
                                <i class="ph ph-globe"></i>
                            </div>
                            <div class="asl-dev-compact-details">
                                <h4>SS Internet Services</h4>
                                <div class="asl-dev-compact-name-v2">
                                    <?php
                                    /* translators: %s: Author Name */
                                    printf(esc_html__('Developed by %s', 'advanced-security-lite'), esc_html(ASP_AUTHOR));
                                    ?>
                                </div>
                            </div>
                            <div class="asl-dev-compact-actions">
                                <a href="<?php echo esc_url(ASP_AUTHOR_URI); ?>" target="_blank" rel="noopener"
                                    class="asl-btn asl-btn-secondary asl-btn-sm">
                                    <?php esc_html_e('Visit Website', 'advanced-security-lite'); ?> →
                                </a>
                            </div>
                        </div>

                        <p class="asl-credit-line">
                            <i class="ph ph-heart" style="color:#dd7796;"></i>
                            <?php
                            /* translators: %s: Link to Phosphor Icons website */
                            printf(esc_html__('Icons provided by %s', 'advanced-security-lite'), '<a href="https://phosphoricons.com/" target="_blank" rel="noopener">Phosphor Icons</a>');
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="asl-save-bar">
                <button type="submit" class="asl-btn asl-btn-primary asl-btn-lg">
                    <i class="ph ph-check"></i> <?php esc_html_e('Save All Settings', 'advanced-security-lite'); ?>
                </button>
                <span class="asl-save-status"></span>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer class="asl-footer">
        <p><?php esc_html_e('Advanced Security Lite', 'advanced-security-lite'); ?>
            v<?php echo esc_html(ASP_VERSION); ?> |
            <?php esc_html_e('Powered by SS Internet Services', 'advanced-security-lite'); ?>
        </p>
    </footer>
</div>