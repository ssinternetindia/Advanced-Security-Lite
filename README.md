# Advanced Security Lite
**Comprehensive WordPress Security Made Simple & Modern.**

![Version](https://img.shields.io/badge/version-1.0.5-blue.svg) ![License](https://img.shields.io/badge/license-GPLv2-green.svg)

**Advanced Security Lite** is a powerful, lightweight, and modern security plugin designed to protect your WordPress website from common threats, brute force attacks, and vulnerabilities. Featuring a state-of-the-art admin interface with **Phosphor Icons** and real-time status monitoring, it offers enterprise-grade security features without bloating your site.

## 🚀 Why Choose Advanced Security Lite?

*   **Modern Interface**: Experience a clean, intuitive, and responsive dashboard that makes security management a breeze.
*   **Lightweight**: Optimized for performance, ensuring your site remains fast while being secure.
*   **Plug & Play**: Essential security features are active immediately upon installation.
*   **Developer Friendly**: Includes advanced tools like salt regeneration and debug logs.

---

## ✨ Key Features

### 🔐 Authentication & Login Security
*   **Intelligent Rate Limiting**: Automatically blocks IP addresses after 5 failed login attempts within an hour to prevent brute-force attacks.
*   **reCAPTCHA Integration**: Supports both v2 (Checkbox) and v3 (Invisible) reCAPTCHA to stop bot logins.
*   **Disable Password Recovery**: Functionality to disable the "Lost your password?" link and mechanism for hardening login pages.
*   **Hide Login Errors**: Suppresses specific login error messages to prevent username guessing.
*   **Auto-Regenerate Salts**: Automatically regenerates WordPress security keys/salts on a schedule (Daily/Weekly/Monthly) to force-logout compromised sessions.
*   **Session Hardening**: Enforces `HttpOnly` and `Secure` flags on session cookies.

### 🛡️ Firewall & Hardening
*   **Request Filtering**: Blocks malicious query strings and bad requests before they reach your application.
*   **Upload Protection**: 
    *   Scans image uploads for embedded malicious PHP/script code.
    *   Prevents direct PHP execution in the `wp-content/uploads` directory via `.htaccess`.
*   **XSS Protection**: Filters content and comments to strip potential Cross-Site Scripting (XSS) vectors.
*   **Disable XML-RPC & REST API**: Options to disable `xmlrpc.php` and `wp-json` (REST API) to reduce attack surface.
*   **Security Headers**: Adds critical headers like `X-Content-Type-Options`, `X-Frame-Options`, and `X-XSS-Protection`.

### ⚡ Admin & Database Security
*   **Admin Access Logging**: detailed logs of every successful admin login, including IP, Time, and Page.
*   **Hide Admin**: Redirects non-admin users away from the `/wp-admin/` area.
*   **Disable File Editors**: Completely disables the built-in Theme and Plugin file editors to prevent accidents or hacks.
*   **Disable Plugin/Theme Installation**: Option to block installing new plugins/themes for a locked-down environment.
*   **Database Hardening**: Removes the WordPress version generator tag and hides database errors in production.

### 🕵️ Privacy & Obfuscation
*   **WP Version Hiding**: Removes the WordPress version number from page source to prevent targeted exploits.
*   **Email Obfuscation**: Encodes email addresses on the frontend to protect them from spam scrapers.
*   **Author Slug Protection**: Obfuscates author URL slugs to prevent username enumeration.

### 🛠️ Tools & Utilities
*   **Maintenance Mode**: Built-in maintenance mode with a customizable message for visitors.
*   **Activity Log**: Tracks failed login attempts and admin access for security auditing.
*   **Developer Info**: Quick access to developer resources and system info.

---

## 📸 Screenshots

*(Add screenshots of your beautiful Phosphor-icon enhanced dashboard here)*

## 📥 Installation

1.  Upload the `advanced-security-lite` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Security Lite** in the admin menu to configure your settings.

## 📝 Credits

*   **Icons**: [Phosphor Icons](https://phosphoricons.com/) - A flexible icon family for interfaces, diagrams, presentations, and more.
*   **Development**: [SS Internet Services](https://ssinternet.in/)

---

_Secure your WordPress site today with Advanced Security Lite._
