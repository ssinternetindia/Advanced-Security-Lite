<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_Recaptcha
{

    private $site_key;
    private $secret_key;
    private $v2_enabled;
    private $v3_enabled;

    public function __construct()
    {
        // Always use init hook to ensure WordPress is fully ready
        // and login hooks haven't fired yet
        add_action('init', array($this, 'init'), 1);
    }

    public function init()
    {
        $this->site_key = get_option('asp_recaptcha_site_key', '');
        $this->secret_key = get_option('asp_recaptcha_secret_key', '');
        $this->v2_enabled = get_option('asp_recaptcha_v2_enabled', 0);
        $this->v3_enabled = get_option('asp_recaptcha_v3_enabled', 0);

        $this->initHooks();
    }

    private function initHooks()
    {
        if (($this->v2_enabled || $this->v3_enabled) && !empty($this->site_key) && !empty($this->secret_key)) {
            // Add reCAPTCHA to login form
            add_action('login_form', array($this, 'addRecaptchaToLogin'));
            add_filter('wp_authenticate_user', array($this, 'verifyLoginRecaptcha'), 10, 2);

            // Add reCAPTCHA to registration form
            add_action('register_form', array($this, 'addRecaptchaToRegister'));
            add_filter('registration_errors', array($this, 'verifyRegisterRecaptcha'), 10, 3);

            // Add reCAPTCHA to lost password form
            add_action('lostpassword_form', array($this, 'addRecaptchaToLostPassword'));
            add_action('lostpassword_post', array($this, 'verifyLostPasswordRecaptcha'));

            // Add reCAPTCHA to comment form
            add_action('comment_form_after_fields', array($this, 'addRecaptchaToComments'));
            add_filter('preprocess_comment', array($this, 'verifyCommentRecaptcha'));

            // Enqueue reCAPTCHA scripts
            add_action('login_enqueue_scripts', array($this, 'enqueueRecaptchaScripts'));
            add_action('wp_enqueue_scripts', array($this, 'enqueueRecaptchaScripts'));
        }
    }

    public function enqueueRecaptchaScripts()
    {
        if ($this->v2_enabled) {
            wp_enqueue_script('recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), ASP_VERSION, true);
        }

        if ($this->v3_enabled) {
            wp_enqueue_script('recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr($this->site_key), array(), ASP_VERSION, true);
            wp_add_inline_script('recaptcha-v3', $this->getV3Script());
        }
    }

    private function getV3Script()
    {
        // Escaping site key in JS output
        return "
        document.addEventListener('DOMContentLoaded', function() {
            grecaptcha.ready(function() {
                // Login form
                var loginForm = document.getElementById('loginform');
                if (loginForm) {
                    loginForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha.execute('" . esc_js($this->site_key) . "', {action: 'login'}).then(function(token) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'g-recaptcha-response';
                            input.value = token;
                            loginForm.appendChild(input);
                            loginForm.submit();
                        });
                    });
                }
                
                // Registration form
                var registerForm = document.getElementById('registerform');
                if (registerForm) {
                    registerForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha.execute('" . esc_js($this->site_key) . "', {action: 'register'}).then(function(token) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'g-recaptcha-response';
                            input.value = token;
                            registerForm.appendChild(input);
                            registerForm.submit();
                        });
                    });
                }
                
                // Lost password form
                var lostpasswordForm = document.getElementById('lostpasswordform');
                if (lostpasswordForm) {
                    lostpasswordForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha.execute('" . esc_js($this->site_key) . "', {action: 'lostpassword'}).then(function(token) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'g-recaptcha-response';
                            input.value = token;
                            lostpasswordForm.appendChild(input);
                            lostpasswordForm.submit();
                        });
                    });
                }
                
                // Comment form
                var commentForm = document.getElementById('commentform');
                if (commentForm) {
                    commentForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha.execute('" . esc_js($this->site_key) . "', {action: 'comment'}).then(function(token) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'g-recaptcha-response';
                            input.value = token;
                            commentForm.appendChild(input);
                            commentForm.submit();
                        });
                    });
                }
            });
        });
        ";
    }

    public function addRecaptchaToLogin()
    {
        if ($this->v2_enabled) {
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($this->site_key) . '"></div>';
            echo '<style>.g-recaptcha { margin-bottom: 16px; display: flex; justify-content: center; }</style>';
        }

        if ($this->v3_enabled) {
            echo '<input type="hidden" name="recaptcha_action" value="login">';
        }
    }

    public function addRecaptchaToRegister()
    {
        if ($this->v2_enabled) {
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($this->site_key) . '"></div>';
            echo '<style>.g-recaptcha { margin-bottom: 16px; display: flex; justify-content: center; }</style>';
        }

        if ($this->v3_enabled) {
            echo '<input type="hidden" name="recaptcha_action" value="register">';
        }
    }

    public function addRecaptchaToLostPassword()
    {
        if ($this->v2_enabled) {
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($this->site_key) . '"></div>';
            echo '<style>.g-recaptcha { margin-bottom: 16px; display: flex; justify-content: center; }</style>';
        }

        if ($this->v3_enabled) {
            echo '<input type="hidden" name="recaptcha_action" value="lostpassword">';
        }
    }

    public function addRecaptchaToComments()
    {
        if (!is_user_logged_in()) {
            if ($this->v2_enabled) {
                echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($this->site_key) . '"></div>';
                echo '<style>.g-recaptcha { margin-bottom: 16px; }</style>';
            }

            if ($this->v3_enabled) {
                echo '<input type="hidden" name="recaptcha_action" value="comment">';
            }
        }
    }

    public function verifyLoginRecaptcha($user, $password)
    {
        if (is_wp_error($user)) {
            return $user;
        }

        if (!$this->verifyRecaptcha('login')) {
            return new WP_Error('recaptcha_failed', __('reCAPTCHA verification failed. Please try again.', 'advanced-security-lite'));
        }

        return $user;
    }

    public function verifyRegisterRecaptcha($errors, $sanitized_user_login, $user_email)
    {
        if (!$this->verifyRecaptcha('register')) {
            $errors->add('recaptcha_failed', __('reCAPTCHA verification failed. Please try again.', 'advanced-security-lite'));
        }

        return $errors;
    }

    public function verifyLostPasswordRecaptcha()
    {
        if (!$this->verifyRecaptcha('lostpassword')) {
            wp_die(esc_html__('reCAPTCHA verification failed. Please try again.', 'advanced-security-lite'));
        }
    }

    public function verifyCommentRecaptcha($commentdata)
    {
        if (!is_user_logged_in() && !$this->verifyRecaptcha('comment')) {
            wp_die(esc_html__('reCAPTCHA verification failed. Please try again.', 'advanced-security-lite'));
        }

        return $commentdata;
    }

    /**
     * Verify a reCAPTCHA response.
     *
     * Verification fails CLOSED: if the token is missing or Google cannot be
     * reached / returns a malformed answer, the action is rejected. Failing
     * open would let bots bypass protection whenever the service is down.
     *
     * @param string $action Expected v3 action (login|register|lostpassword|comment).
     * @return bool
     */
    private function verifyRecaptcha($action = '')
    {
        // Skip verification if reCAPTCHA is not properly configured
        if (empty($this->secret_key) || empty($this->site_key)) {
            return true; // Don't block if not configured
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- reCAPTCHA token acts as the security check here
        if (empty($_POST['g-recaptcha-response'])) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above
        $response = sanitize_text_field(wp_unslash($_POST['g-recaptcha-response']));
        $remote_ip = function_exists('asp_get_client_ip') ? asp_get_client_ip() : '';

        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $this->secret_key,
            'response' => $response
        );

        if ('' !== $remote_ip) {
            $data['remoteip'] = $remote_ip;
        }

        $verify_response = wp_remote_post($verify_url, array(
            'body' => $data,
            'timeout' => 15,
            'sslverify' => true
        ));

        if (is_wp_error($verify_response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($verify_response);
        $result = json_decode($response_body, true);

        if (!is_array($result) || !isset($result['success'])) {
            return false;
        }

        if (!$result['success']) {
            return false;
        }

        // Verify the hostname to prevent cross-site token replay.
        $site_host = (string) parse_url(home_url(), PHP_URL_HOST);
        $token_host = isset($result['hostname']) ? strtolower($result['hostname']) : '';
        if ('' === $token_host) {
            return false;
        }
        // Accept both www and bare domain variants.
        if ($token_host !== $site_host
            && 'www.' . $token_host !== $site_host
            && $token_host !== 'www.' . $site_host
        ) {
            return false;
        }

        // For reCAPTCHA v3, check the score and that the action matches.
        if ($this->v3_enabled) {
            $min_score = (float) apply_filters('asp_recaptcha_min_score', 0.5);
            if (!isset($result['score']) || (float) $result['score'] < $min_score) {
                return false;
            }
            // Action must be present and match — reject if Google omits it.
            if ('' !== $action) {
                if (!isset($result['action']) || $result['action'] !== $action) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getRecaptchaErrors($error_codes)
    {
        $error_messages = array(
            'missing-input-secret' => __('The secret parameter is missing.', 'advanced-security-lite'),
            'invalid-input-secret' => __('The secret parameter is invalid or malformed.', 'advanced-security-lite'),
            'missing-input-response' => __('The response parameter is missing.', 'advanced-security-lite'),
            'invalid-input-response' => __('The response parameter is invalid or malformed.', 'advanced-security-lite'),
            'bad-request' => __('The request is invalid or malformed.', 'advanced-security-lite'),
            'timeout-or-duplicate' => __('The response is no longer valid: either is too old or has been used previously.', 'advanced-security-lite')
        );

        $messages = array();
        foreach ($error_codes as $code) {
            if (isset($error_messages[$code])) {
                $messages[] = $error_messages[$code];
            }
        }

        return $messages;
    }
}
