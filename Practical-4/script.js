let login = document.getElementById("login-form");

if (login) {

    login.addEventListener("submit", function(event) {

        event.preventDefault();

        let email = document.getElementById("email").value.trim();
        let password = document.getElementById("password").value.trim();

        let notification = document.getElementById("notification");

        if (email === "" || password === "") {

            notification.textContent = "Please fill in all fields.";

            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");

            notification.style.display = "block";

            setTimeout(function() {
                notification.style.display = "none";
            }, 3000);

        } else {

            notification.textContent = "Login successful! Redirecting...";

            notification.classList.remove("notification-error");
            notification.classList.add("notification-success");

            notification.style.display = "block";

            setTimeout(function() {
                window.location.href = "dashboard.html";
            }, 2000);

        }

    });

}

