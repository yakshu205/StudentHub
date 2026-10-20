# Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload

## 📋 Problem Definition:
Create simple PHP JSON API endpoints for events and consume them using JavaScript **Fetch API**. Perform **List, Add, Update, and Delete** operations asynchronously without full page reload.

---

## 🎯 Key Questions & Verification (From Lab Curriculum):
1. **Do PHP endpoints return valid JSON responses?**
   - Yes, `api_events.php` sends `header('Content-Type: application/json; charset=UTF-8')` and returns formatted JSON objects.
2. **Are GET and POST requests handled properly?**
   - Yes:
     - `GET`: Returns list of all events, single event by ID, or debounced search query.
     - `POST (action=create)`: Creates a new event and returns HTTP `201 Created`.
     - `POST (action=update)`: Updates an event and returns HTTP `200 OK`.
     - `POST (action=delete)`: Deletes an event and returns HTTP `200 OK`.
3. **Are loading, success, and error states shown on the frontend?**
   - Yes:
     - **Loading State:** `⏳ Processing...` / `⏳ Searching...` indicators with disabled submit buttons.
     - **Success State:** Green notification alert (`.alert-success`) dynamically fading in.
     - **Error State:** Red notification alert (`.alert-error`) showing validation or network errors.
4. **Is input validation performed before database operations?**
   - Yes:
     - Client-side validation prevents empty form submissions.
     - Server-side validation in `api_events.php` verifies non-empty required fields and returns HTTP `400 Bad Request` with error details.

---

## 🚀 Extensions Implemented:
- **Intermediate Extension:** AJAX Search with **300ms Debounce** on both Admin (`admin.php`) and Student (`events.php`) pages.
- **Advanced Extension:** RESTful HTTP response status codes (`200 OK`, `201 Created`, `400 Bad Request`, `404 Not Found`, `405 Method Not Allowed`, `500 Server Error`) with centralized error handling.

---

## 📁 File Structure:
- **`api_events.php`**: RESTful JSON API endpoint handling asynchronous CRUD & search.
- **`api/events.php`**: Route proxy to `api_events.php`.
- **`admin.php`**: Admin panel with Fetch-based Add, Edit, Delete, and Debounced Search (Zero page reload!).
- **`events.php`**: Student events portal with Fetch-based dynamic card rendering & live debounced search.
- **`dashboard.php`, `home.php`, `about.php`, `contact.php`, `profile.php`, `edit.php`, `faq.php`, `feedback.php`**: Exact original StudentHub pages.
- **`login.php`, `logout.php`, `auth.php`, `db.php`**: Authentication and database connection.
- **All original CSS files**: `admin.css`, `events.css`, `dashboard.css`, `home.css`, etc.

---

## 🔑 Login Credentials:
- **Admin**: `admin@studenthub.com` / `thor%123`
- **Student**: `student@studenthub.com` / `iron%123`

---

## 🧪 Kaise Test Karein (Step-by-Step Viva & Lab Demo):

1. **XAMPP me Apache aur MySQL start karein.**
2. **Browser me open karein:**
   ```
   http://localhost/StudentHub/Practical-13/login.php
   ```
3. Login karein Admin account se:
   - Email: `admin@studenthub.com`
   - Password: `thor%123`
4. **Inspect / Browser Network Tab Open karein:**
   - Keyboard par `F12` dabayein aur **Network** tab select karein (Filter by `Fetch/XHR`).
5. **Test 1: AJAX Add Event (No Page Reload)**
   - Form me event details bharein aur **"➕ Add Event"** dabayein.
   - Page refresh **nahi** hoga! Network tab me `api_events.php` ka `POST` request (Status 201) dikhega, green notification aayega, aur naya event table me instant add ho jayega.
6. **Test 2: AJAX Edit Event (No Page Reload)**
   - Kisi bhi event ke **"✏️ Edit"** par click karein. Details form me load ho jayengi.
   - Detail change karke **"💾 Update Event"** dabayein. Table me instant update ho jayega.
7. **Test 3: AJAX Delete Event (No Page Reload)**
   - Event ke **"🗑️ Delete"** par click karein aur confirm karein.
   - Row smoothly remove ho jayegi bina page refresh ke.
8. **Test 4: Debounced Live Search**
   - Search input me type karein (e.g. `Hackathon`). Network tab me debounced `api_events.php?search=...` call dikhegi aur table filter ho jayega.
9. **Test 5: Student Events Page Check**
   - Student page open karein: `http://localhost/StudentHub/Practical-13/events.php`
   - Wahan bhi live debounced search aur same original card design me events dikhenge.
