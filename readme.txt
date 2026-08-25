=== Advanced Security Lite ===
Contributors: Anuj Kumar Singh
Tags: security, firewall, hardening, login, captcha, recaptcha, maintenance
Requires at least: 5.8
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful WordPress security plugin featuring a clean admin interface and advanced protection tools to safeguard your website from threats.

== Description ==

**Advanced Security Lite** is a powerful, lightweight security plugin designed to protect your WordPress website from common threats, brute force attacks, and vulnerabilities. It ships with a clean, WordPress-native admin interface, color-coded navigation, a live Security Score, and a dedicated Status tab — enterprise-grade security features without bloating your site.

### Why Choose Advanced Security Lite?

*   **Clean Interface**: A flat, WordPress-native admin UI with color-coded Phosphor icons, sidebar navigation, and a live Security Score.
*   **Lightweight**: Optimized for performance, ensuring your site remains fast while being secure.
*   **Plug & Play**: Essential security features are active immediately upon installation.
*   **Privacy-First**: The self-hosted image CAPTCHA and direct-connection IP detection mean no data leaves your server unless you choose Google reCAPTCHA.
*   **Developer Friendly**: Salt regeneration, an error log viewer, and a copyable system report.

### Key Features

#### Authentication & Login Security

*   **Intelligent Rate Limiting**: Automatically blocks IP addresses after a configurable number of failed login attempts within a configurable lockout window to prevent brute-force attacks.
*   **reCAPTCHA Integration**: Supports both v2 (Checkbox) and v3 (Invisible) reCAPTCHA to stop bot logins. Verification fails closed, so bots cannot slip through when the service misbehaves.
*   **Self-Hosted Image CAPTCHA**: Distorted-text challenge rendered as an inline SVG with a one-click refresh — fully self-hosted, single-use server-side tokens, no external service, GDPR-friendly, and no API keys required.
*   **Disable Password Recovery**: Disable the "Lost your password?" link and reset mechanism for hardening login pages.
*   **Hide Login Errors**: Masks username/password guessing messages to prevent user enumeration — while keeping functional errors (CAPTCHA, lockouts, cookies) visible so legitimate users are never left guessing.
*   **Auto-Regenerate Salts**: Regenerates WordPress security keys/salts on a schedule (Daily/Weekly/Monthly) with atomic writes, automatic backups, and forced logout of all sessions.
*   **Accurate IP Detection**: Uses the direct connection IP only. Spoofed proxy headers (X-Forwarded-For etc.) are never trusted, so the rate limiter cannot be bypassed by faking headers.

#### Firewall & Hardening

*   **Request Filtering**: Blocks malicious query strings and bad requests before they reach your application.
*   **Upload Protection**: Scans image uploads for embedded malicious PHP/script code, and prevents direct PHP execution in `wp-content/uploads` via `.htaccess`.
*   **XSS Protection**: Filters content and comments to strip potential Cross-Site Scripting (XSS) vectors.
*   **Disable XML-RPC & REST API**: Options to disable `xmlrpc.php`, `wp-json`, and RSS feeds to reduce attack surface.
*   **Security Headers**: Adds critical headers like `X-Content-Type-Options`, `X-Frame-Options`, and `X-XSS-Protection`.
*   **Sensitive File Protection**: Blocks web access to `wp-config.php`, `.htaccess`, `debug.log`, and more via `.htaccess` rules.

#### Admin & Database Security

*   **Admin Access Logging**: Detailed logs of admin area access, including IP, time, and page.
*   **Hide Admin Username**: Hides administrator usernames from author archives and displays.
*   **Disable File Editors**: Disables the built-in Theme and Plugin file editors to prevent accidents or hacks.
*   **Disable Plugin/Theme Uploads**: Options to block installing new plugins/themes via upload for a locked-down environment.
*   **Disable Application Passwords & Pingbacks**: Additional hardening toggles for modern attack surfaces.
*   **Database Hardening**: Removes the WordPress version generator tag and hides database errors in production.

#### Privacy & Obfuscation

*   **WP Version Hiding**: Removes the WordPress version number from page source to prevent targeted exploits.
*   **Email Obfuscation**: Encodes email addresses on the frontend to protect them from spam scrapers.
*   **Author Slug Protection**: Replaces author usernames in URLs with stable, random hashes to prevent enumeration.
*   **Hide Admin Notices**: Declutters wp-admin by hiding third-party notices; review them anytime from a bell in the admin bar (with "Show all" restore).

#### Tools & Utilities

*   **Maintenance Mode**: Built-in maintenance mode (503 + Retry-After) with a customizable message on a clean white page.
*   **Custom Login Design**: One-click 40/60 split-screen theme for the WordPress login page, with your site branding on the left panel; collapses gracefully on mobile.
*   **Activity Log**: Tracks failed login attempts and admin access for security auditing, with one-click clearing.
*   **Error Log Viewer**: When enabled, an "Error Logs" entry appears in the WordPress admin bar on every admin screen — inspect the tail of every detected log file (debug.log, PHP error log, `.log` files in wp-content/uploads), download the full file, or clear it.
*   **Status Tab**: Detailed live server information — PHP version & SAPI, database version, memory/upload limits, required PHP extensions, disk space, WordPress debug state, theme, and more — with a one-click "Copy System Report" for support requests.
*   **Developer Info**: Quick links and credits.

== Installation ==

1. Upload the `advanced-security-lite` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Security Lite** in the admin menu to configure your settings.

== Frequently Asked Questions ==

= Does this plugin work with other security plugins? =
Yes, generally. However, enabling similar features (like login limiting) in multiple plugins may cause conflicts. We recommend using one security plugin for specific features.

= Is reCAPTCHA free? =
Yes, Google reCAPTCHA v2 and v3 keys are free to generate effectively for most sites.

= Do all features work on my server? =
The `.htaccess`-based features (directory listing block, sensitive file protection, uploads PHP execution block) require Apache or a compatible server with `AllowOverride` enabled. On nginx these rules are skipped gracefully. The image CAPTCHA and all other features are server-agnostic.

= Where do I see PHP errors and debug logs? =
Enable the "Error Log Viewer" toggle in the Settings tab, then use the "Error Logs" entry in the WordPress admin bar. Set `WP_DEBUG_LOG` to true in `wp-config.php` to start recording errors.

= Can I hide the clutter of notices from other plugins? =
Yes — enable "Hide Admin Notices" in the General tab. Hidden notices are listed behind the "Notices" bell in the admin bar and can be restored at any time.

== Screenshots ==

1. **Dashboard**: Live Security Score, feature stats, and recommendations.
2. **Settings**: Clean toggle switches for all security modules.
3. **Activity Log**: Failed login attempts and admin access history.
4. **Status**: Detailed server and WordPress environment information.

== Changelog ==

= 1.0.1 =

Added
* Self-hosted image CAPTCHA with distorted-text SVG challenges, refresh button, and single-use server-side tokens (no external service, no API keys).
* "Hide Admin Notices" — declutters wp-admin and adds an admin-bar bell listing every hidden notice, with a "Show all" restore action.
* Error Log Viewer — an "Error Logs" entry in the WordPress admin bar (on every admin screen) listing all detected log files with view, download, and clear actions per file.
* Status tab — live server and WordPress environment details with a one-click "Copy System Report".
* Custom Login Design — a 40/60 split-screen login theme with site branding; collapses gracefully on mobile.

Security
* Salt regeneration now writes wp-config.php atomically with a backup, verifies replacements, and logs out all sessions.
* Login rate limiting is enabled via a dedicated toggle, honors configurable attempt counts and lockout windows, and cannot be bypassed by spoofing proxy headers (direct connection IP only).
* Login lockout can no longer extend itself: failed attempts are not recorded while locked, and a successful login immediately clears that IP's history and lock.
* The login page is never cached while Image CAPTCHA is active, preventing stale-CAPTCHA mass login failures.
* reCAPTCHA verification fails closed and validates the v3 action and score.
* "Hide Login Errors" masks only username/password guessing messages — CAPTCHA errors, lockouts, and cookie notices stay visible.

Fixed
* Weekly database optimization no longer deletes permanent transients created by other plugins.
* Custom login stylesheet now loads on wp-login.php (previously the feature was inert).
* Maintenance message textarea is now saved; the Emergency Reset button is available in the Settings tab.
* Author slug obfuscation generates stable, working URLs.
* "Clear All Logs" rebuilt as a self-contained module with a two-click confirm on the button and an in-place result (native confirm() dialogs can be silently suppressed by browsers).
* "Invalid login credentials." no longer appears when simply opening wp-login.php — the enumeration filter no longer rewrites the harmless empty-credential error from the login page's background signon.
* Custom Login Design is opt-in again (default off) with its toggle restored in the Tools tab; accent color, panel side, form style, and logo options apply only while enabled.
* Admin-notice bell detects top-level and legacy (.updated/.error) notices and rescans when opened.
* Security Score, "Features Active" stat, and status badge share the same feature list and update live after saving.

Changed
* Clean, flat WordPress-native admin UI with color-coded Phosphor icons, sidebar navigation, and a live Security Score.
* Plugin no longer forces DISALLOW_FILE_EDIT / DISALLOW_FILE_MODS regardless of settings; toggles behave as described.
* Uninstall now reverts all .htaccess modifications and removes backup files.
* .htaccess rules are written at most once per day instead of on every admin page load.
* "Trust Proxy Headers" removed — visitor IPs always come from the direct connection.
* Maintenance screen redesigned with a clean white layout and blue accent.
* Tested up to WordPress 7.1. Verified clean on PHP 7.4 through PHP 8.x.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Major reliability and security pass: safe salt regeneration, spoof-proof rate limiting, a working error log viewer, admin notice manager, image CAPTCHA, and a full admin UI refresh. Update recommended.
