# Practical 14: Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log

## 📋 Problem Definition
Develop an admin dashboard that displays **user statistics, student count, event count, and latest registrations**. Restrict access to **admin users only** and show **dynamic menu options based on role**.

---

## 🎯 Key Questions & Verification (From Lab Curriculum)

### 1. Are dashboard values loaded dynamically from the database?
- **Yes.** All metric tiles and overview cards are computed in real time directly from MySQL via SQL aggregation queries:
  - **User Statistics & Breakdown:**
    ```sql
    SELECT COUNT(*) as total_users,
           SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
           SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
    FROM students;
    ```
  - **Student Count:** `SELECT COUNT(*) FROM students WHERE role = 'student';`
  - **Event Count:** `SELECT COUNT(*) FROM events;`
  - **Latest Registrations:** `SELECT * FROM registrations ORDER BY id DESC LIMIT 8;`

### 2. Is access restricted to admin users?
- **Yes.** Role-Based Access Control (RBAC) is enforced at the PHP session layer:
  - Guard function `require_admin()` in `auth.php` verifies that `$_SESSION['user_id']` exists and `$_SESSION['user_role'] === 'admin'`.
  - If a non-logged-in visitor tries to access `admin.php`, they are redirected to `login.php?error=unauthorized`.
  - If a logged-in student (e.g. `student@studenthub.com`) tries to access `admin.php`, their attempt is intercepted, logged in `audit_logs` as an **Unauthorized Access Attempt**, and redirected to `dashboard.php?error=forbidden` with an Access Denied alert.

### 3. Are role-based menus working?
- **Yes.** Dynamic navigation is generated via `render_navigation()` in `auth.php`:
  - **Administrator View:** Displays `Admin Dashboard`, `Users`, `Events`, `Registrations`, `Audit Logs`, `Student View`, and `Logout` with a `🛡️ ADMIN` badge.
  - **Student View:** Displays `Dashboard`, `Events`, `Profile`, `About`, `Contact`, `FAQ`, `Feedback`, and `Logout` with a `🎓 STUDENT` badge.
  - **Guest View:** Displays `Home`, `About`, `Events`, `Contact`, `FAQ`, `Register`, and `Login`.

### 4. Are important admin actions recorded in audit logs?
- **Yes.** Dedicated `audit_logs` table records every critical action:
  - User Login / Logout
  - Failed Login Attempts (wrong password, non-existent email, deactivated account)
  - Active/Inactive User Status Toggle (Intermediate Extension)
  - Event Creation, Updates, and Deletion
  - Student Event Registration and Status Updates
  - Each log entry stores `user_id`, `user_name`, `user_role`, `action`, `module`, `details`, `ip_address`, and `created_at`.

---

## 🚀 Extensions Implemented

### 1. Intermediate Extension: Active / Inactive User Status Toggle
- In the **User Management** section of `admin.php`, each user displays an **Active** (green) or **Inactive** (red) status badge along with an instant toggle button (`🚫 Deactivate` or `✅ Activate`).
- Toggling updates both `students` and `users` tables and records the administrative action in `audit_logs`.
- If an inactive student attempts to log in, authentication is rejected with the message:
  *"Your account has been deactivated by the Administrator. Please contact support."* and the blocked attempt is saved in `audit_logs`.

### 2. Advanced Extension: Event Registrations Analytics Chart by Month
- Aggregates registrations by month from the database:
  ```sql
  SELECT MONTH(registration_date) as m_num,
         DATE_FORMAT(registration_date, '%b') as m_name,
         COUNT(*) as reg_count 
  FROM registrations 
  GROUP BY MONTH(registration_date), DATE_FORMAT(registration_date, '%b') 
  ORDER BY m_num ASC;
  ```
- Renders an interactive bar chart using **Chart.js**, color-coordinated with StudentHub's `#5265f9` and `#2faad6` theme.

---

## 🗄️ Dataset & Database Tables

Practical 14 manages the following 5 tables in `studenthub_db`:
1. **`students`**: User profile, contact info, hashed passwords, role (`admin`/`student`), status (`active`/`inactive`), and remember tokens.
2. **`users`**: Core RBAC authentication dataset (id, name, email, password hash, role, status, last login).
3. **`events`**: Scheduled events details (title, day, month, location, time, description).
4. **`registrations`**: Student event registrations with timestamps and statuses (`Confirmed`, `Pending`, `Cancelled`).
5. **`audit_logs`**: Complete audit trail of administrative and security events.

---

## 🔑 Login Credentials for Testing

| Role | Email | Password | Status | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | `admin@studenthub.com` | `thor%123` | **Active** | Full access to Admin Dashboard & Audit Logs |
| **Student** | `student@studenthub.com` | `iron%123` | **Active** | Regular student access |
| **Student** | `rohit@studenthub.com` | `rohit%123` | **Active** | Regular student access |
| **Student** | `priya@studenthub.com` | `priya%123` | **Active** | Regular student access |
| **Student (Inactive)** | `aman@studenthub.com` | `aman%123` | **Inactive** | Pre-configured to test inactive account block |

---

## 🧪 Step-by-Step Viva & Lab Demonstration

1. **Start Apache & MySQL in XAMPP Control Panel.**
2. **Open the Login Page in your browser:**
   ```
   http://localhost/StudentHub/practical%20-14/login.php
   ```
   *(or `http://localhost/StudentHub/Practical-14/login.php`)*

3. **Demo 1: RBAC Enforcement (Admin Only)**
   - Login with Student credentials: `student@studenthub.com` / `iron%123`.
   - Notice the dynamic menu shows **🎓 STUDENT** view and no Admin links.
   - Try typing `http://localhost/StudentHub/practical%20-14/admin.php` in the URL bar.
   - **Result:** Access is immediately blocked! You are redirected to `dashboard.php` with the red alert: *"Access Denied: Administrator role required"*.

4. **Demo 2: Dynamic Role-Based Menu**
   - Logout and login with Admin credentials: `admin@studenthub.com` / `thor%123`.
   - **Result:** You are redirected straight to `admin.php`. The navigation bar now dynamically shows **🛡️ ADMIN** links (`Admin Dashboard`, `Users`, `Events`, `Registrations`, `Audit Logs`, `Student View`, `Logout`).

5. **Demo 3: Dynamic Dashboard Statistics & SQL Aggregations**
   - In `admin.php`, verify the metric tiles: Total Users (Active vs Inactive breakdown), Registered Students, Scheduled Events, and Event Registrations.

6. **Demo 4: Monthly Registrations Analytics Chart (Advanced Extension)**
   - Scroll to **Event Registrations by Month**. Inspect the dynamic bar chart rendered from SQL aggregated registration counts.

7. **Demo 5: User Status Toggle (Intermediate Extension)**
   - In **User Management**, locate student **Yaksh Butani** (currently Active).
   - Click `🚫 Deactivate`.
   - The badge turns red (`● INACTIVE`) and button changes to `✅ Activate`.
   - Open a private/incognito window, go to `login.php` and attempt to login as `student@studenthub.com` / `iron%123`.
   - **Result:** Login is blocked! Error displayed: *"Your account is currently Inactive/Suspended."*
   - Return to Admin tab and click `✅ Activate` to restore access.

8. **Demo 6: Audit Log Verification**
   - Scroll to **Administrative Audit Logs**.
   - Notice all your recent logins, status toggle actions, and registrations appear in chronological order with exact timestamps, user names, roles, actions, and IP addresses!
