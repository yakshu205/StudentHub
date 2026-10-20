# Practical-10: Secure Login & Session Management System

## 🎯 Practical Objectives & Features Implemented:
1. **PHP Sessions**: Secure login and logout session tracking using `session_start()`, `$_SESSION`, and `session_destroy()`.
2. **Password Verification (`password_verify()`)**: Passwords securely hashed with `password_hash()` (Bcrypt) and verified against MySQL hashes with `password_verify()`.
3. **Session ID Regeneration**: `session_regenerate_id(true)` immediately after successful login to prevent Session Fixation attacks.
4. **Restricted Dashboard Access**: Unauthorized access to `dashboard.php` and `admin.php` is blocked and redirected to `login.php`.
5. **Role-Based Redirection**:
   - `student` role users are redirected to `dashboard.php`.
   - `admin` role users are redirected to `admin.php`.
   - Students attempting to open `admin.php` are blocked and redirected to `dashboard.php`.
6. **Session Timeout**: 15 minutes (900 seconds) of inactivity automatically terminates the session and redirects to `login.php?error=timeout`.
7. **Secure Remember-Me Token**: Optional persistent login using cryptographically secure random bytes (`bin2hex(random_bytes(32))`), stored securely as SHA-256 hashes in MySQL and in HTTP-Only cookies.

---

## 📁 File Structure:
- **`login.php`**: Login interface with Remember-Me checkbox and authentication processing.
- **`dashboard.php`**: Protected student dashboard showing profile and session audit metrics.
- **`admin.php`**: Protected admin management console showing user database and system statistics.
- **`logout.php`**: Comprehensive logout destroying session, database tokens, and browser cookies.
- **`auth.php`**: Central session authentication guard, timeout checker, and remember-token validator.
- **`db.php`**: Database connection (`studenthub_db`), auto-table creation, and demo account seeder.
- **`database.sql`**: Complete database schema and SQL statements.
- **`style.css`**: Responsive StudentHub stylesheet.

---

## 🔑 Ready-to-Use Test Accounts:

| Role | Name | Email | Password | Redirection Destination |
|---|---|---|---|---|
| **Student** | Yaksh Butani | `student@studenthub.com` | `iron%123` | `dashboard.php` |
| **Admin** | System Admin | `admin@studenthub.com` | `thor%123` | `admin.php` |

---

## 🚀 Kaise Run Karein (Step-by-Step):

1. **XAMPP Control Panel** me **Apache** aur **MySQL** start karein.
2. Browser me open karein:
   ```
   http://localhost/StudentHub/Practical-10/login.php
   ```
3. Test karein:
   - **Student Login**: `student@studenthub.com` / `iron%123` daalein -> Student Dashboard open hoga.
   - **Admin Login**: `admin@studenthub.com` / `thor%123` daalein -> Admin Panel open hoga.
   - **Access Control Test**: Logout karein aur direct URL open karke dekhein `http://localhost/StudentHub/Practical-10/dashboard.php` -> Ye access block karke wapas login page par bhej dega!
