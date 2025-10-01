DSA School — Prototype

Quick start:

1. Place this folder inside your XAMPP htdocs directory (example path: C:\xampp\htdocs\DSA_Finals\dsa-school).
2. Start Apache (and PHP) via XAMPP.
3. Open http://localhost/dsa-school/ in your browser.

What is included:

- index.php (login/register UI)
- assets/css/style.css
- assets/js/app.js
- api/\* PHP endpoints (register, login, verify_2fa, logout)
- api/helpers.php (JSON DB helpers, TOTP implementation)
- data/users.json (simple JSON "database")
- dashboard.php (role-based dashboard)

Notes:

- This prototype uses TOTP (RFC-style) 2FA. After registering you'll receive the secret and an otpauth:// URL to add to an authenticator app.
- No external services or databases are used.
- For a production app you should serve via HTTPS, secure sessions, and handle many security edge-cases.
