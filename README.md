# AquaIntelX — PHP Backend Setup Guide

## Folder Structure

Place all files inside Laragon's web root:

```
C:\laragon\www\aquaintelx\
│
├── config.php          ← DB credentials & session setup
├── login.php           ← POST handler (returns JSON)
├── logout.php          ← Destroys session, redirects to login
├── auth_check.php      ← Include at top of protected pages
│
├── login.html          ← Updated login page (calls login.php)
├── index.html          ← Your dashboard (protect with auth_check)
├── style.css
├── script.js
│
└── database/
    └── aquaintelx_setup.sql   ← Run this once in MySQL Workbench
```

---

## Step 1 — Run the SQL Script

1. Open **MySQL Workbench** and connect to `localhost` (Laragon's MySQL).
2. Open `database/aquaintelx_setup.sql`.
3. Click **Execute (⚡)** — this creates the `aquaintelx` database, tables, and a default admin user.

---

## Step 2 — Start Laragon

Open Laragon and click **Start All** (Apache + MySQL).

---

## Step 3 — Configure DB credentials

Open `config.php` and verify:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Laragon default is empty
define('DB_NAME', 'aquaintelx');
```

If you changed the Laragon MySQL password, update `DB_PASS`.

---

## Step 4 — Open the app

Visit: **http://localhost/aquaintelx/login.html**

**Default credentials:**
| Field    | Value                    |
|----------|--------------------------|
| Email    | admin@aquaintelx.com     |
| Password | Admin@1234               |

> ⚠️ Change this password after your first login!

---

## Step 5 — Protect index.html → index.php

Rename `index.html` to `index.php` and add this at the very top:

```php
<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
...
```

Also update the **Sign Out** link in the sidebar:

```html
<!-- Change this: -->
<a href="login.html" ...>Sign Out</a>

<!-- To this: -->
<a href="logout.php" ...>Sign Out</a>
```

---

## How Authentication Works

```
Browser                    login.php                   MySQL
  │                            │                          │
  │── POST /login.php ─────────▶                          │
  │   { email, password }      │── SELECT user by email ─▶│
  │                            │◀─ user row ──────────────│
  │                            │                          │
  │                            │  password_verify()        │
  │                            │  log attempt             │
  │◀── { success, redirect } ──│                          │
  │                            │                          │
  │── GET /index.php ──────────▶                          │
  │                       auth_check.php                  │
  │                       checks $_SESSION                │
  │◀── Dashboard HTML ─────────│                          │
```

---

## Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with bcrypt (cost 12) |
| SQL injection | PDO prepared statements throughout |
| Session fixation | `session_regenerate_id(true)` on login |
| Brute-force protection | 5 failed attempts per IP per 15 min |
| Session timeout | Idle sessions expire after 8 hours |
| XSS (login error) | Error messages escaped before display |
| Secure cookies | `httponly=true`, `samesite=Strict` |

---

## Changing the Admin Password

Run this in MySQL Workbench (replace `YourNewPassword` with your actual password):

```sql
USE aquaintelx;

UPDATE users
SET password = '$2y$12$...'   -- generate with PHP below
WHERE email = 'admin@aquaintelx.com';
```

Generate a new hash with PHP:
```php
echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost' => 12]);
```

Or run this one-liner in Laragon's terminal:
```
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
```
