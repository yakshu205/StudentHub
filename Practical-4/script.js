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