# Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON File Storage

StudentHub Portal ka Registration Form process karne ke liye PHP backend implementation, jisme server-side input validation, sanitization, aur safe CSV / JSON file storage shamil hai.

---

## 🎯 Objectives & Features:
1. **Form Submission (POST)**: Form completely `POST` method se secure data transmit karta hai.
2. **Server-Side Sanitization**: `htmlspecialchars()`, `trim()`, `stripslashes()`, aur `filter_var()` ka use karke XSS aur malicious input injection ko prevent kiya gaya hai.
3. **Comprehensive Validation**:
   - **Full Name**: Required, sirf alphabets aur spaces allowed (2-50 characters).
   - **Email**: Required, `FILTER_VALIDATE_EMAIL` format check.
   - **Mobile**: Required, 10-digit number validation.
   - **Password**: Required, minimum 6 characters.
   - **Confirm Password**: Password ke sath exact match check.
   - **Date of Birth**: Required, valid date format aur future date validation.
   - **Gender & Course**: Whitelist validation (sirf allowed options accept honge).
   - **Skills**: Array whitelist validation.
   - **Terms & Conditions**: Mandatory checkbox.
   - **Duplicate Check**: Agar email pehle se JSON/CSV me registered hai toh duplicate submission block hoti hai.
4. **Safe File Writing (flock)**:
   - Data ko **JSON** (`students.json`) aur **CSV** (`students.csv`) dono me store kiya jata hai.
   - Concurrency aur race-conditions ko rokne ke liye PHP ka `flock(LOCK_EX)` (exclusive lock) use kiya gaya hai.
5. **Clear Success & Error Messages**:
   - StudentHub ke original UI popup notification alert (`#notification` with `.notification-success` / `.notification-error`) me messages aate hain aur 5 seconds me auto-dismiss hote hain.
6. **Intermediate Extension**:
   - `view_records.php` page banaya gaya hai jahan stored JSON aur CSV data ko clean StudentHub UI table me view kiya ja sakta hai.
7. **Advanced Extension**:
   - Cross-Site Request Forgery (CSRF) token generation (`bin2hex(random_bytes(32))`) aur server-side validation (`hash_equals()`) implement kiya gaya hai.

---

## 📁 Files in Practical-7:
- **`register.php`**: Original StudentHub Registration Form UI ke sath server-side validation, sanitization, CSRF token aur JSON/CSV file write logic.
- **`register.css`**: StudentHub Portal ki exact CSS styling (colors, header, card form, notification toasts, responsive design).
- **`view_records.php`**: Stored student records dekhne ke liye webpage (JSON & CSV view switch, download options).
- **`students.json`**: JSON format me stored student records.
- **`students.csv`**: CSV format me stored student records.

---

## 🚀 Kaise Run Karein (XAMPP):
1. XAMPP Control Panel me **Apache** server start karein.
2. Browser open karein aur type karein:
   ```
   http://localhost/StudentHub/Practical-7/register.php
   ```
3. Form bharein aur **Register** par click karein.
   - Success hone par message aayega: *"Registration Successful! Data saved to JSON & CSV files."*
   - Agar koi field galat ho (jaise password mismatch ya invalid email), error message popup hoga.
4. Stored records dekhne ke liye:
   ```
   http://localhost/StudentHub/Practical-7/view_records.php
   ```
   Yahan aap JSON aur CSV dono formats ke data ko live table me dekh sakte hain.
