<?php
/**
 * Self-hosted Image CAPTCHA.
 *
 * Renders a distorted-text challenge as an inline SVG (no GD, no external
 * services, no API keys). Codes are stored server-side in transients and
 * are single-use: the transient is deleted the moment it is verified, which
 * prevents token replay.
 *
 * @package Advanced_Security_Lite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_ImageCaptcha
{

    const TRANSIENT_PREFIX = 'asp_cap_';
    const CODE_TTL = 600; // 10 minutes

    /**
     * @var bool
     */
    private $enabled;

    public function __construct()
    {
        add_action('init', array($this, 'init'), 1);
    }

    public function init()
    {
        $this->enabled = (bool) get_option('asp_image_captcha_enabled', 0);

        // Always expose the refresh endpoint so challenges already shown on
        // screen remain refreshable across settings changes mid-session.
        add_action('wp_ajax_asp_refresh_captcha', array($this, 'ajaxRefreshCaptcha'));
        add_action('wp_ajax_nopriv_asp_refresh_captcha', array($this, 'ajaxRefreshCaptcha'));

        if (!$this->enabled) {
            return;
        }

        add_action('login_form', array($this, 'renderLoginField'));
        add_action('register_form', array($this, 'renderRegisterField'));
        add_action('lostpassword_form', array($this, 'renderLostPasswordField'));
        add_action('comment_form_after_fields', array($this, 'renderCommentField'));

        add_filter('wp_authenticate_user', array($this, 'verifyLoginCaptcha'), 10, 2);
        add_filter('registration_errors', array($this, 'verifyRegisterCaptcha'), 10, 3);
        add_action('lostpassword_post', array($this, 'verifyLostPasswordCaptcha'));
        add_filter('preprocess_comment', array($this, 'verifyCommentCaptcha'));

        add_action('login_footer', array($this, 'printScript'));
        add_action('wp_footer', array($this, 'printScript'));

        // A page-cached login screen serves stale CAPTCHA IDs, which makes
        // every submit fail and can trigger the login lockout for innocent
        // users. Never let the login page be cached while CAPTCHA is on.
        add_action('login_init', array($this, 'sendLoginNoCache'));
    }

    public function sendLoginNoCache()
    {
        nocache_headers();
    }

    /* ---------------------------------------------------------------------
     * Field rendering
     * ------------------------------------------------------------------- */

    public function renderLoginField()
    {
        $this->renderField('login');
    }

    public function renderRegisterField()
    {
        $this->renderField('register');
    }

    public function renderLostPasswordField()
    {
        $this->renderField('lostpassword');
    }

    public function renderCommentField()
    {
        $this->renderField('comment');
    }

    private function renderField($form)
    {
        if ('comment' === $form && is_user_logged_in()) {
            return;
        }

        $challenge = $this->issueChallenge();

        echo '<style>'
            . '.asp-captcha-wrap{display:block;margin:12px 0;}'
            . '.asp-captcha-row{display:flex;align-items:center;gap:8px;margin-bottom:8px;}'
            . '.asp-captcha-img{width:190px;height:56px;border-radius:6px;border:1px solid #d0d5dd;background:#f9fafb;display:block;}'
            . '.asp-captcha-refresh{cursor:pointer;border:1px solid #d0d5dd;background:#fff;border-radius:6px;'
            . 'width:46px;height:46px;font-size:18px;line-height:1;color:#475467;'
            . 'display:inline-flex;align-items:center;justify-content:center;padding:0;flex-shrink:0;}'
            . '.asp-captcha-refresh:disabled{opacity:.5;cursor:default;}'
            . '.asp-captcha-code{display:block;width:100%;padding:11px 12px;border:1px solid #d0d5dd;'
            . 'border-radius:6px;font-size:15px;letter-spacing:2px;}'
            . '</style>';

        echo '<p class="asp-captcha-wrap" data-form="' . esc_attr($form) . '">';
        echo '<span class="asp-captcha-row">';
        echo '<img class="asp-captcha-img" src="' . esc_attr($challenge['image']) . '" '
            . 'width="190" height="56" alt="' . esc_attr__('CAPTCHA challenge', 'advanced-security-lite') . '" />';
        echo '<button type="button" class="asp-captcha-refresh" title="'
            . esc_attr__('Get a new CAPTCHA', 'advanced-security-lite') . '">&#8635;</button>';
        echo '</span>';
        echo '<input type="text" class="asp-captcha-code" name="asp_captcha_code" autocomplete="off" '
            . 'spellcheck="false" inputmode="text" placeholder="'
            . esc_attr__('Type the code', 'advanced-security-lite') . '" />';
        echo '<input type="hidden" name="asp_captcha_id" value="' . esc_attr($challenge['id']) . '" />';
        echo '</p>';
    }

    /* ---------------------------------------------------------------------
     * Verification
     * ------------------------------------------------------------------- */

    public function verifyLoginCaptcha($user, $password)
    {
        if (is_wp_error($user)) {
            return $user;
        }

        if (!$this->checkRequest()) {
            return new WP_Error(
                'asp_captcha_failed',
                __('Incorrect CAPTCHA. Please try again.', 'advanced-security-lite')
            );
        }

        return $user;
    }

    public function verifyRegisterCaptcha($errors, $sanitized_user_login, $user_email)
    {
        if (!$this->checkRequest()) {
            $errors->add('asp_captcha_failed', __('Incorrect CAPTCHA. Please try again.', 'advanced-security-lite'));
        }

        return $errors;
    }

    public function verifyLostPasswordCaptcha()
    {
        if (!$this->checkRequest()) {
            wp_die(esc_html__('Incorrect CAPTCHA. Please try again.', 'advanced-security-lite'));
        }
    }

    public function verifyCommentCaptcha($commentdata)
    {
        if (!is_user_logged_in() && !$this->checkRequest()) {
            wp_die(esc_html__('Incorrect CAPTCHA. Please try again.', 'advanced-security-lite'));
        }

        return $commentdata;
    }

    /**
     * Single-use validation: the transient is consumed on first check so a
     * solved token cannot be replayed.
     *
     * @return bool
     */
    private function checkRequest()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the challenge itself is the proof-of-work
        $id = isset($_POST['asp_captcha_id']) ? sanitize_text_field(wp_unslash($_POST['asp_captcha_id'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above
        $code = isset($_POST['asp_captcha_code'])
            ? strtolower(sanitize_text_field(wp_unslash($_POST['asp_captcha_code'])))
            : '';

        if ('' === $id || '' === $code || !preg_match('/^[a-z0-9]{10,64}$/', $id)) {
            return false;
        }

        $key = self::TRANSIENT_PREFIX . $id;
        $stored = get_transient($key);
        delete_transient($key);

        return is_string($stored) && '' !== $stored && hash_equals($stored, $code);
    }

    /* ---------------------------------------------------------------------
     * Challenge generation
     * ------------------------------------------------------------------- */

    /**
     * Create a fresh challenge: random code, transient-backed id and an
     * inline SVG rendering of the distorted text.
     *
     * @param string $form Form context (unused for generation, kept for parity).
     * @return array{id:string,image:string}
     */
    private function issueChallenge($form = 'login')
    {
        unset($form);

        $code = $this->generateCode();
        $id = strtolower(wp_generate_password(24, false, false));

        set_transient(self::TRANSIENT_PREFIX . $id, strtolower($code), self::CODE_TTL);

        return array(
            'id' => $id,
            'image' => 'data:image/svg+xml;base64,' . base64_encode($this->buildSvg($code)), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        );
    }

    /**
     * Unambiguous charset (no i/l/o/0/1) keeps human failure rates low.
     */
    private function generateCode()
    {
        $charset = 'abcdefghjkmnpqrstuvwxyz23456789';
        $length = 5;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, strlen($charset) - 1)];
        }

        return $code;
    }

    /**
     * Build the distorted SVG challenge. All dynamic values come from
     * constrained alphabets (alnum chars, whitelisted hex colors, integers),
     * so no XML escaping issues are possible.
     */
    private function buildSvg($code)
    {
        $width = 190;
        $height = 56;
        $colors = array('#1d2939', '#4a1fb8', '#0b5c4a', '#8a2c2c', '#274690', '#5b3a1e');
        $fonts = array('Georgia, serif', 'Verdana, sans-serif', '\'Courier New\', monospace');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img">';
        $svg .= '<defs><linearGradient id="aspcapbg" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0%" stop-color="#eef2f6"/><stop offset="100%" stop-color="#dde5ee"/>'
            . '</linearGradient></defs>';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="url(#aspcapbg)"/>';

        // Noise curves.
        for ($i = 0; $i < 3; $i++) {
            $x1 = random_int(-10, 30);
            $y1 = random_int(5, $height - 5);
            $cx = random_int(50, $width - 50);
            $cy = random_int(0, $height);
            $x2 = random_int($width - 40, $width + 10);
            $y2 = random_int(5, $height - 5);
            $stroke = $colors[array_rand($colors)];
            $opacity = number_format(random_int(20, 45) / 100, 2, '.', '');
            $svg .= '<path d="M' . $x1 . ' ' . $y1 . ' Q' . $cx . ' ' . $cy . ' ' . $x2 . ' ' . $y2 . '" '
                . 'fill="none" stroke="' . $stroke . '" stroke-width="1.2" opacity="' . $opacity . '"/>';
        }

        // Noise dots.
        for ($i = 0; $i < 42; $i++) {
            $svg .= '<circle cx="' . random_int(0, $width) . '" cy="' . random_int(0, $height) . '" r="'
                . random_int(1, 2) . '" fill="' . $colors[array_rand($colors)] . '" opacity="0.35"/>';
        }

        // Distorted characters.
        $x = 14;
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $char = $code[$i];
            $y = random_int(34, 48);
            $size = random_int(23, 29);
            $rotation = random_int(-28, 28);
            $font = $fonts[array_rand($fonts)];
            $fill = $colors[array_rand($colors)];

            $svg .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')" '
                . 'font-size="' . $size . '" font-family="' . $font . '" font-weight="bold" '
                . 'fill="' . $fill . '">' . $char . '</text>';

            $x += random_int(26, 34);
        }

        $svg .= '</svg>';

        return $svg;
    }

    /* ---------------------------------------------------------------------
     * AJAX refresh endpoint
     * ------------------------------------------------------------------- */

    public function ajaxRefreshCaptcha()
    {
        // Rate-limit: 10 requests per IP per 60-second window.
        $ip = function_exists('asp_get_client_ip') ? asp_get_client_ip() : '';
        if ('' !== $ip) {
            $rate_key = 'asp_caprq_' . md5($ip);
            $count = (int) get_transient($rate_key);
            if ($count >= 10) {
                status_header(429);
                wp_send_json_error('Too many captcha requests.');
                return;
            }
            set_transient($rate_key, $count + 1, 60);
        }

        $form = isset($_POST['form']) ? sanitize_key(wp_unslash($_POST['form'])) : 'login';
        if (!in_array($form, array('login', 'register', 'lostpassword', 'comment'), true)) {
            $form = 'login';
        }

        wp_send_json_success($this->issueChallenge($form));
    }

    /**
     * Vanilla-JS refresh handler (the login screen ships without jQuery).
     */
    public function printScript()
    {
        static $printed = false;
        if ($printed || !$this->enabled) {
            return;
        }
        $printed = true;

        $config = 'var aspCaptchaConfig = ' . wp_json_encode(array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
        )) . ';';

        // Nowdoc (<<<'JS') so PHP never interpolates anything in the script.
        $js = <<<'JS'
document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.asp-captcha-refresh') : null;
    if (!btn) { return; }
    e.preventDefault();
    var wrap = btn.closest('.asp-captcha-wrap');
    if (!wrap) { return; }
    var img = wrap.querySelector('.asp-captcha-img');
    var idInput = wrap.querySelector('input[name="asp_captcha_id"]');
    if (!img || !idInput) { return; }
    btn.disabled = true;
    var body = new URLSearchParams();
    body.append('action', 'asp_refresh_captcha');
    body.append('form', wrap.getAttribute('data-form') || 'login');
    fetch(aspCaptchaConfig.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.success && res.data && res.data.image) {
                idInput.value = res.data.id;
                img.src = res.data.image;
            }
        })
        .catch(function () {})
        .finally(function () { btn.disabled = false; });
});
JS;

        if (function_exists('wp_print_inline_script_tag')) {
            wp_print_inline_script_tag($config . "\n" . $js);
        } else {
            echo '<script>' . $config . "\n" . $js . '</script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
        }
    }
}
