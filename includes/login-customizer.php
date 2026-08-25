<?php
/**
 * Custom Login Design - loads the login stylesheet on wp-login.php.
 *
 * The previous implementation enqueued login.css on wp_enqueue_scripts,
 * which never fires on the login screen, so the feature was inert.
 *
 * @package Advanced_Security_Lite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ASP_LoginCustomizer
{

    /** @var string Validated #rrggbb accent color. */
    private $accent = '#2563eb';

    /** @var string 'left' | 'right' | 'none'. */
    private $panel_side = 'left';

    /** @var string 'solid' | 'glass'. */
    private $form_style = 'solid';

    /** @var string Optional logo URL. */
    private $logo = '';

    public function __construct()
    {
        // Strictly opt-in: the design stays completely off unless the admin
        // enables "Custom Login Design" in the Tools tab. Default: OFF.
        if (!get_option('asp_custom_login_design', 0)) {
            return;
        }

        $this->accent     = $this->valid_hex(get_option('asp_login_accent_color', '#2563eb'), '#2563eb');
        $this->panel_side = $this->enum_value(get_option('asp_login_panel_side', 'left'), array('left', 'right', 'none'), 'left');
        $this->form_style = $this->enum_value(get_option('asp_login_form_style', 'solid'), array('solid', 'glass'), 'solid');
        $this->logo       = esc_url_raw(get_option('asp_login_logo', ''));

        add_action('login_enqueue_scripts', array($this, 'enqueueLoginStyles'));
        add_action('login_header', array($this, 'renderBrandPanel'));
        add_filter('login_body_class', array($this, 'loginBodyClasses'));

        // Replace the default "Powered by WordPress" header with the
        // site name (or uploaded logo) linking home.
        add_filter('login_headerurl', array($this, 'loginLogoUrl'));
        add_filter('login_headertext', array($this, 'loginLogoText'));
    }

    /**
     * Body classes consumed by login.css to switch layout variants:
     * asp-side-left|right|none and asp-form-solid|glass.
     */
    public function loginBodyClasses($classes)
    {
        if (!is_array($classes)) {
            $classes = (array) $classes;
        }
        $classes[] = 'asp-side-' . $this->panel_side;
        $classes[] = 'asp-form-' . $this->form_style;
        return $classes;
    }

    /**
     * 40% brand panel rendered on the left side of the split-screen layout.
     * Hooked to login_header so it lands right after <body>, before #login.
     */
    public function renderBrandPanel()
    {
        if ('none' === $this->panel_side) {
            return;
        }

        $site_name = esc_html(get_bloginfo('name'));
        $site_desc = esc_html(get_bloginfo('description'));
        $year = (int) current_time('Y');
        $logo = $this->logo;
        ?>
        <div id="asp-brand-panel" aria-hidden="true">
            <div class="asp-brand-top">
                <?php if ($logo) : ?>
                    <img class="asp-brand-img" src="<?php echo esc_url($logo); ?>" alt="" />
                <?php else : ?>
                    <span class="asp-brand-logo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                    </span>
                <?php endif; ?>
                <div class="asp-brand-id">
                    <span class="asp-brand-name"><?php echo $site_name; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_html() above ?></span>
                    <?php if ($site_desc) : ?>
                        <span class="asp-brand-tagline"><?php echo $site_desc; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_html() above ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="asp-brand-mid">
                <h2 class="asp-brand-headline">
                    <?php esc_html_e('Welcome back.', 'advanced-security-lite'); ?><br />
                    <?php esc_html_e('Sign in to your account.', 'advanced-security-lite'); ?>
                </h2>
                <p class="asp-brand-sub">
                    <?php esc_html_e('Enter your details below to access your dashboard and pick up right where you left off.', 'advanced-security-lite'); ?>
                </p>
            </div>

            <div class="asp-brand-foot">
                <span class="asp-brand-secure">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <?php esc_html_e('Secured connection', 'advanced-security-lite'); ?>
                </span>
                <span class="asp-brand-copy">&copy; <?php echo esc_html($year); ?> <?php echo $site_name; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_html() above ?></span>
            </div>
        </div>
        <?php
    }

    public function loginLogoUrl()
    {
        return home_url('/');
    }

    public function loginLogoText()
    {
        if ($this->logo) {
            return '<img class="asp-login-logo" src="' . esc_url($this->logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '" />';
        }
        return esc_html(get_bloginfo('name'));
    }

    public function enqueueLoginStyles()
    {
        $css_path = ASP_PLUGIN_PATH . 'assets/css/login.css';

        // Never enqueue a stylesheet that does not exist on disk.
        if (!file_exists($css_path)) {
            return;
        }

        wp_enqueue_style('asp-login-css', ASP_PLUGIN_URL . 'assets/css/login.css', array(), filemtime($css_path));

        // Override the default palette with the admin-chosen accent colour.
        // Only validated hex values reach these rules (see valid_hex()).
        $css = sprintf(
            ':root{--asll-accent:%1$s;--asll-accent-strong:%2$s;--asll-accent-soft:%3$s;}',
            $this->accent,
            $this->shade($this->accent, -0.14),
            $this->rgba($this->accent, 0.12)
        );
        wp_add_inline_style('asp-login-css', $css);
    }

    /**
     * Validate and normalize a #rgb / #rrggbb color; fall back to $default.
     */
    private function valid_hex($color, $default)
    {
        $color = trim((string) $color);
        if (1 === preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            if (4 === strlen($color)) {
                $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
            }
            return strtolower($color);
        }
        return $default;
    }

    /**
     * Restrict a raw option value to an explicit allow-list.
     */
    private function enum_value($value, array $allowed, $default)
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function rgb($hex)
    {
        return array(
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2))
        );
    }

    private function rgba($hex, $alpha)
    {
        list($r, $g, $b) = $this->rgb($hex);
        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim(rtrim(number_format($alpha, 3, '.', ''), '0'), '.'));
    }

    /**
     * Darken (negative factor) or lighten (positive factor) a hex color.
     */
    private function shade($hex, $factor)
    {
        list($r, $g, $b) = $this->rgb($hex);
        if ($factor >= 0) {
            $r += (255 - $r) * $factor;
            $g += (255 - $g) * $factor;
            $b += (255 - $b) * $factor;
        } else {
            $f = 1 + $factor;
            $r *= $f;
            $g *= $f;
            $b *= $f;
        }
        return sprintf('#%02x%02x%02x', (int) round($r), (int) round($g), (int) round($b));
    }
}
