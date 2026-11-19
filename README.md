# SS Sentinel – Lightweight Security, CAPTCHA & Firewall

> **Rebrand Notice:** Formerly known as **Advanced Security Lite** — rebranded to **SS Sentinel** in v1.0.3 with 100% backwards compatibility (all filenames, slugs, settings, and `.htaccess` markers remain unchanged for seamless upgrades).

**Contributors:** SS Internet Services · **Requires:** WordPress 5.8+ · **PHP:** 7.4 – 8.x · **License:** GPLv2 or later · **Version:** 1.0.3

A powerful **free** WordPress security plugin featuring a clean admin interface and advanced protection tools to safeguard your website from threats.

---

## Overview

**SS Sentinel** is a lightweight security plugin that protects your WordPress website from common threats, brute force attacks, and vulnerabilities. It ships with a clean, WordPress-native admin interface, color-coded navigation, a live Security Score, and a dedicated Status tab — enterprise-grade security features without bloating your site.

### Highlights

- **Clean Interface** — flat, WordPress-native admin UI with color-coded Phosphor icons, sidebar navigation, and a live Security Score
- **Lightweight** — optimized for performance; your site stays fast while being secure
- **Plug & Play** — essential protections activate immediately on install
- **Privacy-First** — self-hosted image CAPTCHA and direct-connection IP detection mean no data leaves your server unless you opt into Google reCAPTCHA
- **Developer Friendly** — salt regeneration, error log viewer, and a copyable system report

---

## Features

### Authentication & Login Security

| Feature | Description |
| --- | --- |
| **Intelligent Rate Limiting** | Blocks IP addresses after a configurable number of failed login attempts within a configurable lockout window |
| **reCAPTCHA v2 & v3** | Checkbox and invisible reCAPTCHA with fail-closed verification (score + action validated for v3) |
| **Self-Hosted Image CAPTCHA** | Distorted-text SVG challenge with refresh; single-use server-side tokens, no external service, no API keys, GDPR-friendly |
| **Disable Password Recovery** | Turn off the "Lost your password?" flow for hardened login pages |
| **Hide Login Errors** | Masks username/password guessing messages while keeping functional errors (CAPTCHA, lockouts, cookies) visible |
| **Auto-Regenerate Salts** | Scheduled salt rotation (daily/weekly/monthly) with atomic writes, backups, and forced logout of all sessions |
| **Accurate IP Detection** | Direct connection IP only — spoofed proxy headers can never bypass the rate limiter |

### Firewall & Hardening

- **Request Filtering** — blocks malicious query strings and bad requests
- **Upload Protection** — scans image uploads for embedded PHP/script code; blocks PHP execution in `wp-content/uploads` via `.htaccess`
- **XSS Protection** — strips XSS vectors from content and comments
- **Disable XML-RPC, REST API & Feeds** — shrink the attack surface
- **Security Headers** — `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`
- **Sensitive File Protection** — blocks web access to `wp-config.php`, `.htaccess`, `debug.log` and more

### Admin & Database Security

- **Admin Access Logging** — who accessed the admin area, from where, and when
- **Hide Admin Username** — hides administrator usernames from author archives
- **Disable File Editors** — turns off the built-in theme/plugin editors
- **Disable Plugin/Theme Uploads** — lock down new installations
- **Disable Application Passwords & Pingbacks**
- **Database Hardening** — removes the WP version generator tag, hides DB errors

### Privacy & Obfuscation

- **WP Version Hiding** — removes version numbers from page source
- **Email Obfuscation** — encodes emails on the frontend against scrapers
- **Author Slug Protection** — stable random hashes replace usernames in author URLs
- **Hide Admin Notices** — hides third-party wp-admin notices; review them from an admin-bar bell with "Show all" restore

### Tools & Utilities

- **Maintenance Mode** — 503 + `Retry-After` maintenance page with a custom message
- **Custom Login Design** — one-click 40/60 split-screen login theme with your site branding; mobile-friendly
- **Activity Log** — failed login attempts and admin access history with one-click clearing
- **Error Log Viewer** — an "Error Logs" entry in the WP admin bar (on every admin screen) listing every detected log file with view / download / clear actions
- **Status Tab** — live server & WordPress environment details with one-click "Copy System Report"
- **Developer Info** — quick links and credits

---

## Installation

1. Upload the `advanced-security-lite` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **SS Sentinel** in the admin menu and configure your settings.

---

## Frequently Asked Questions

**Does this plugin work with other security plugins?**
Yes, generally. Enabling identical features (like login limiting) in multiple plugins may conflict — pick one plugin per feature.

**Is reCAPTCHA free?**
Yes. Google reCAPTCHA v2/v3 keys are free to generate for most sites.

**Do all features work on my server?**
The `.htaccess`-based features require Apache (or compatible) with `AllowOverride` enabled; on nginx they are skipped gracefully. Everything else is server-agnostic.

**Where do I see PHP errors and debug logs?**
Enable the "Error Log Viewer" toggle in Settings, then use the "Error Logs" entry in the WordPress admin bar. Set `WP_DEBUG_LOG` to true in `wp-config.php` to start recording.

**Can I hide notices from other plugins?**
Enable "Hide Admin Notices" in the General tab. Hidden notices are listed behind the "Notices" bell in the admin bar and can be restored anytime.

---

## Changelog

### 1.0.3

**Added & Changed**
- Rebranded to **SS Sentinel – Lightweight Security, CAPTCHA & Firewall** (filenames, slugs, options, and `.htaccess` markers preserved)
- Salt regeneration popup now offers **Re-login Now** and **Later** options; fixed PHP 8+ fatal error when clearing sessions
- Added Login Rate Limiting to the Bento Dashboard Security Score & Recommendations and unified Header badge scoring

**Security & Reliability**
- Closed REST API `?rest_route=` bypass and added custom `rest_get_url_prefix()` support
- Moved rate limiting (priority 5) and CAPTCHA verification (priority 15) before core auth with priority-99 enforcement and REST/WP-CLI/Cron exemptions
- Fixed reCAPTCHA v3 comment form submission (`HTMLFormElement.prototype.submit`), enforced 3-way CAPTCHA mutual exclusion, and added back links on comment CAPTCHA errors
- Blocked direct `lostpassword`/`rp`/`resetpass` routes, unauthenticated XML-RPC methods, Core XML Sitemaps & oEmbed user leaks, and `?ver=` WP version query strings
- Fixed `register_activation_hook` timing, deactivation `.htaccess` cleanup, Multisite Super Admin guards, salt regex for embedded quotes, Retina-safe email obfuscation, SVG XSS upload scanning, and `wp_commentmeta` spam cleanup

### 1.0.2

**Security & Reliability**
- Fixed login rate limiter and admin access log timestamp mismatch (`time()` vs `current_time('timestamp')`) across all timezones
- Fixed `uploads/.htaccess` uninstall cleanup using `insert_with_markers()`
- Hardened `wp-config.php` salt regeneration (escaped salts, parent-dir lookup, failed-write backup cleanup)
- Gated upload scanning behind its toggle, scoped XSS attribute stripping to HTML tags, and restricted Error Log Viewer UI/nonces to `manage_options`
- Stored log options with `autoload = false`, capped tracked failed-login IPs, and added WP 6.6+ autoload query support
- Added `.htaccess` cleanup on toggle-off for Directory Listing and Sensitive Files Protection, subdirectory REST API prefix fix, paginated author slug resolution, and reCAPTCHA v2/v3 mutual exclusion
- Tested up to WordPress 7.1.4

### 1.0.1

**Added**
- Self-hosted image CAPTCHA with SVG challenges, refresh, and single-use tokens
- Hide Admin Notices + admin-bar bell with "Show all" restore
- Error Log Viewer (admin bar, site-wide) with per-file view / download / clear
- Status tab with live server details and "Copy System Report"
- Custom Login Design — 40/60 split-screen login theme

**Security**
- Atomic, backed-up salt regeneration with global session logout
- Spoof-proof login rate limiting with dedicated toggle and configurable thresholds
- Lockout cannot extend itself: attempts aren't recorded while locked, and successful login clears that IP's history and lock
- Login page is never cached while Image CAPTCHA is active (prevents stale-CAPTCHA failures)
- Fail-closed reCAPTCHA with v3 score + action validation
- "Hide Login Errors" masks only credential-guessing messages

**Fixed**
- Database optimization no longer deletes other plugins' permanent transients
- Custom login stylesheet now actually loads on wp-login.php
- Maintenance message saves; Emergency Reset button added to Settings
- Author slug obfuscation produces stable, working URLs
- "Clear All Logs" rebuilt as a self-contained module with two-click confirm and in-place result
- Admin-notice bell detects legacy notice classes and rescans on open
- Security Score / stats / badge share one feature list and update live
- "Invalid login credentials." no longer shows when opening wp-login.php (empty-credential background signon is left alone)
- Custom Login Design is opt-in again (default off) with its toggle restored

**Changed**
- Clean, flat WordPress-native admin UI with color-coded Phosphor icons
- No more forced `DISALLOW_FILE_EDIT` / `DISALLOW_FILE_MODS` — toggles behave as labeled
- Uninstall reverts all `.htaccess` modifications and removes backup files
- `.htaccess` writes throttled to once per day
- "Trust Proxy Headers" removed — direct connection IP only
- Maintenance screen redesigned (white, blue accent)
- Tested up to WordPress 7.1.4; verified clean on PHP 7.4 – 8.x

### 1.0.0

- Initial release

---

## License

GPLv2 or later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

Icons provided by [Phosphor Icons](https://phosphoricons.com/).
