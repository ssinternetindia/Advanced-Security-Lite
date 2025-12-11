<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ASP Login Customizer - Simplified (Custom Login URL feature removed)
 * Only handles login page styling enhancements
 */
class ASP_LoginCustomizer
{
    public function __construct()
    {
        if (function_exists('get_option')) {
            $this->initHooks();
        } else {
            add_action('init', array($this, 'initHooks'));
        }
    }

    public function initHooks()
    {
        // Only basic login styling - Custom Login URL feature has been removed
        if (get_option('asp_custom_login_design', 0)) {
            add_action('login_enqueue_scripts', array($this, 'customLoginStyles'));
            add_filter('login_headerurl', array($this, 'customLoginLogoUrl'));
            add_filter('login_headertext', array($this, 'customLoginLogoTitle'));
            add_action('login_head', array($this, 'addCustomLoginHead'));
            add_action('login_footer', array($this, 'addCustomLoginFooter'));

            // Remove password reset links if disabled
            if (get_option('asp_disable_password_recovery', 0)) {
                add_filter('login_message', array($this, 'removePasswordResetLinks'));
                add_action('login_footer', array($this, 'hidePasswordResetLinks'));
            }
        }
    }

    public function customLoginStyles()
    {
        wp_enqueue_style('asp-login-custom', ASP_PLUGIN_URL . 'assets/css/login.css', array(), ASP_VERSION);
    }

    public function customLoginLogoUrl()
    {
        return home_url();
    }

    public function customLoginLogoTitle()
    {
        return get_bloginfo('name');
    }

    public function addCustomLoginHead()
    {
        // Add custom styles to login page head
    }

    public function addCustomLoginFooter()
    {
        ?>
        <style>
            /* Enhanced Login Page Styles */
            body.login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }

            #login {
                background: #fff;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                width: 380px;
            }

            #login h1 a {
                background-image: none !important;
                text-indent: 0 !important;
                width: auto !important;
                height: auto !important;
                font-size: 24px !important;
                font-weight: 700 !important;
                color: #333 !important;
            }

            #loginform {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin-top: 20px;
            }

            #loginform .input,
            #loginform input[type="text"],
            #loginform input[type="password"] {
                background: #f5f5f5 !important;
                border: 1px solid #e0e0e0 !important;
                border-radius: 8px !important;
                padding: 12px 16px !important;
                font-size: 14px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #loginform .input:focus,
            #loginform input[type="text"]:focus,
            #loginform input[type="password"]:focus {
                border-color: #667eea !important;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
                outline: none !important;
            }

            #wp-submit {
                background: #667eea !important;
                border: none !important;
                border-radius: 8px !important;
                padding: 12px 24px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                width: 100% !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }

            #wp-submit:hover {
                background: #5a6fd6 !important;
                transform: translateY(-1px) !important;
            }

            #nav,
            #backtoblog {
                text-align: center !important;
                margin-top: 20px !important;
            }

            #nav a,
            #backtoblog a {
                color: #667eea !important;
                text-decoration: none !important;
            }

            .login .message,
            .login .success {
                border-left: 4px solid #667eea !important;
                border-radius: 4px !important;
            }

            .login #login_error {
                border-left: 4px solid #ef4444 !important;
                border-radius: 4px !important;
            }
        </style>
        <?php
    }

    public function removePasswordResetLinks($message)
    {
        // Remove any password reset related messages
        if (strpos($message, 'lost') !== false || strpos($message, 'password') !== false) {
            return '';
        }
        return $message;
    }

    public function hidePasswordResetLinks()
    {
        ?>
        <style>
            #nav a[href*="lostpassword"],
            .login #nav a:first-child {
                display: none !important;
            }
        </style>
        <?php
    }
}

// Initialized in main plugin class