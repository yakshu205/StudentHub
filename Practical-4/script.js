function showNotification(message, type, duration) {
    let notification = document.getElementById("notification");

    if (!notification) {
        return;
    }

    notification.textContent = message;
    notification.classList.remove("notification-error", "notification-success");
    notification.classList.add(type);
    notification.style.display = "block";

    setTimeout(function() {
        notification.style.display = "none";
    }, duration);
}

let login = document.getElementById("login-form");

if (login) {
    login.addEventListener("submit", function(event) {
        event.preventDefault();

        let email = document.getElementById("email").value.trim();
        let password = document.getElementById("password").value.trim();

        let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

        if (email === "" || password === "") {
            showNotification(
                "Please fill in all fields.",
                "notification-error",
                3000
            );
            return;
        }

        if (!emailRegex.test(email)) {
            showNotification(
                "Please enter a valid email address.",
                "notification-error",
                3000
            );
            return;
        }

        if (!passwordRegex.test(password)) {
            showNotification(
                "Password must contain 8 characters, uppercase, lowercase, number and special character.",
                "notification-error",
                3000
            );
            return;
        }

        showNotification(
            "Login successful! Redirecting...",
            "notification-success",
            2000
        );

        setTimeout(function() {
            window.location.href = "dashboard.html";
        }, 2000);
    });
}

let register = document.getElementById("register-form");

if (register) {
    register.addEventListener("submit", function(event) {
        event.preventDefault();

        let name = document.getElementById("name").value.trim();
        let email = document.getElementById("email").value.trim();
        let mobile = document.getElementById("mobile").value.trim();
        let password = document.getElementById("password").value.trim();
        let confirmPassword = document.getElementById("confirm").value.trim();
        let dob = document.getElementById("dob").value;
        let course = document.getElementById("course").value;
        let address = document.getElementById("address").value.trim();
        let terms = document.getElementById("terms").checked;
        let gender = document.querySelector('input[name="gender"]:checked');

        let nameRegex = /^[A-Za-z ]{2,50}$/;
        let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        let mobileRegex = /^[6-9][0-9]{9}$/;
        let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

        if (
            name === "" ||
            email === "" ||
            mobile === "" ||
            password === "" ||
            confirmPassword === "" ||
            dob === "" ||
            course === "" ||
            address === ""
        ) {
            showNotification(
                "Please fill in all required fields.",
                "notification-error",
                3000
            );
            return;
        }

        if (!nameRegex.test(name)) {
            showNotification(
                "Name should contain only letters and spaces.",
                "notification-error",
                3000
            );
            return;
        }

        if (!emailRegex.test(email)) {
            showNotification(
                "Please enter a valid email address.",
                "notification-error",
                3000
            );
            return;
        }

        if (!mobileRegex.test(mobile)) {
            showNotification(
                "Please enter a valid 10-digit mobile number.",
                "notification-error",
                3000
            );
            return;
        }

        if (!passwordRegex.test(password)) {
            showNotification(
                "Password must contain 8 characters, uppercase, lowercase, number and special character.",
                "notification-error",
                3000
            );
            return;
        }

        if (password !== confirmPassword) {
            showNotification(
                "Password and Confirm Password do not match.",
                "notification-error",
                3000
            );
            return;
        }

        if (!gender) {
            showNotification(
                "Please select your gender.",
                "notification-error",
                3000
            );
            return;
        }

        if (!terms) {
            showNotification(
                "Please accept the Terms & Conditions.",
                "notification-error",
                3000
            );
            return;
        }

        showNotification(
            "Registration successful! Redirecting...",
            "notification-success",
            2000
        );

        setTimeout(function() {
            window.location.href = "login.html";
        }, 2000);
    });
}
// ========================
// DARK / LIGHT MODE TOGGLE
// ========================

var themeToggleBtn = document.getElementById("themeToggle");

// Load saved theme from localStorage
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    if (themeToggleBtn) themeToggleBtn.textContent = "☀️";
} else {
    if (themeToggleBtn) themeToggleBtn.textContent = "🌙";
}

if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        if (document.body.classList.contains("dark-mode")) {
            themeToggleBtn.textContent = "☀️";
            localStorage.setItem("theme", "dark");
        } else {
            themeToggleBtn.textContent = "🌙";
            localStorage.setItem("theme", "light");
        }
    });
}

// ========================
// CAMPUS SLIDER
// ========================

var currentSlide = 0;

function moveSlide(direction) {
    var slides = document.querySelectorAll(".slide");
    var dots = document.querySelectorAll(".dot");

    if (slides.length === 0) return;

    slides[currentSlide].classList.remove("active");
    dots[currentSlide].classList.remove("active");

    currentSlide = (currentSlide + direction + slides.length) % slides.length;

    slides[currentSlide].classList.add("active");
    dots[currentSlide].classList.add("active");
}

function goToSlide(index) {
    var slides = document.querySelectorAll(".slide");
    var dots = document.querySelectorAll(".dot");

    if (slides.length === 0) return;

    slides[currentSlide].classList.remove("active");
    dots[currentSlide].classList.remove("active");

    currentSlide = index;

    slides[currentSlide].classList.add("active");
    dots[currentSlide].classList.add("active");
}

// Auto-slide every 4 seconds
setInterval(function () {
    moveSlide(1);
}, 4000);

// ========================
// SHOW / HIDE PASSWORD
// ========================

var togglePasswordBtn = document.getElementById("togglePassword");
var passwordInput = document.getElementById("password");

if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener("click", function () {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            togglePasswordBtn.textContent = "🙈";
        } else {
            passwordInput.type = "password";
            togglePasswordBtn.textContent = "👁";
        }
    });
}

// Fetch data from students.json (only if students-container exists)
let studentContainer = document.getElementById("students-container");
if (studentContainer) {
    function displayStudents(data) {
        studentContainer.innerHTML = "";
        data.forEach(student => {
            let card = document.createElement("div");
            card.className = "student-card";
            card.innerHTML = `
                <h3>${student.name}</h3>
                <p><b>Course:</b> ${student.course}</p>
                <p><b>Semester:</b> ${student.semester}</p>
                <p><b>GPA:</b> ${student.gpa}</p>
                <p><b>Status:</b> ${student.status}</p>
            `;
            studentContainer.appendChild(card);
        });
    }


    fetch("../../Practical-5/students.json")
        .catch(() => fetch("../Practical-5/students.json"))
        .then(response => {
            if (!response.ok) throw new Error("HTTP error " + response.status);
            return response.json();
        })
        .then(data => {
            displayStudents(data);
        })
        .catch(error => {
            console.log("Fetch error (e.g. file:// protocol):", error);
            // Fallback for direct file:// open where browser blocks local fetch
            if (window.location.protocol === "file:") {
                let fallbackData = [
                    { name: "Aarav Sharma", course: "B.Tech Computer Science", semester: "5th Sem", gpa: 8.9, status: "Active" },
                    { name: "Priya Patel", course: "B.Tech Information Technology", semester: "3rd Sem", gpa: 9.2, status: "Active" },
                    { name: "Rohan Verma", course: "BCA", semester: "5th Sem", gpa: 8.1, status: "Inactive" },
                    { name: "Sneha Kulkarni", course: "MCA", semester: "1st Sem", gpa: 9.5, status: "Active" }
                ];
                displayStudents(fallbackData);
            }
        });
}